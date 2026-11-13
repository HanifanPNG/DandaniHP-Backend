<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PembayaranTunai;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Models\PesananAktif;
use App\Models\RiwayatStatusPesanan;
use App\Support\StatusPesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesananDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilik_melihat_detail_pesanan_lengkap(): void
    {
        [$pelanggan, $pesanan] = $this->siapkanPesanan();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/pesanan/'.$pesanan->id_pesanan);

        $response->assertOk()
            ->assertJsonPath('data.status_pengerjaan', StatusPesanan::PESANAN_MASUK_ANTREAN)
            ->assertJsonPath('data.status_kanonik', StatusPesanan::QUEUED)
            ->assertJsonPath('data.label_status', 'Masuk Antrean')
            ->assertJsonPath('data.merek_hp', 'Samsung')
            ->assertJsonPath('data.model_hp', 'A54')
            ->assertJsonPath('data.kategori_kerusakan', 'Layar')
            ->assertJsonPath('data.mitra.nama_usaha', 'Mitra Uji')
            ->assertJsonPath('data.mitra.nomor_telepon', '081200000009')
            ->assertJsonPath('data.harga_penawaran', 250000)
            ->assertJsonPath('data.estimasi_hari', 2)
            // Timeline urut waktu (riwayat pertama lebih awal).
            ->assertJsonCount(2, 'data.timeline')
            ->assertJsonPath('data.timeline.0.status', StatusPesanan::PESANAN_MASUK_ANTREAN)
            ->assertJsonPath('data.timeline.1.status', StatusPesanan::PESANAN_SEDANG_DIPROSES)
            ->assertJsonPath('data.timeline.1.aktor', 'Mitra')
            // Info pembayaran hanya baca.
            ->assertJsonPath('data.pembayaran.jumlah_pembayaran', 250000)
            ->assertJsonPath('data.pembayaran.status_pembayaran', 'Menunggu Konfirmasi');

        // estimasi_selesai = waktu_dibuat + 2 hari.
        $this->assertSame(
            $pesanan->waktu_dibuat->copy()->addDays(2)->toIso8601String(),
            $response->json('data.estimasi_selesai')
        );
    }

    public function test_pesanan_tanpa_pembayaran_mengembalikan_null(): void
    {
        [$pelanggan, $pesanan] = $this->siapkanPesanan(buatPembayaran: false);
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/pesanan/'.$pesanan->id_pesanan)
            ->assertOk()
            ->assertJsonPath('data.pembayaran', null);
    }

    public function test_status_selesai_menghasilkan_kanonik_completed(): void
    {
        [$pelanggan, $pesanan] = $this->siapkanPesanan(
            statusPengerjaan: StatusPesanan::PESANAN_SELESAI
        );
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/pesanan/'.$pesanan->id_pesanan)
            ->assertOk()
            ->assertJsonPath('data.status_kanonik', StatusPesanan::COMPLETED)
            ->assertJsonPath('data.label_status', 'Pesanan Selesai');
    }

    public function test_pelanggan_lain_ditolak_403(): void
    {
        [, $pesanan] = $this->siapkanPesanan();

        $lain = Pelanggan::create([
            'nama_lengkap'      => 'Pelanggan Lain',
            'email'             => 'lain@example.com',
            'nomor_telepon'     => '081200000002',
            'alamat'            => 'Jl. Lain No. 2',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);
        $token = $lain->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/pesanan/'.$pesanan->id_pesanan)
            ->assertForbidden();
    }

    public function test_pesanan_tidak_ditemukan_404(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/pesanan/99999')
            ->assertNotFound();
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/pesanan/1')->assertUnauthorized();
    }

    /**
     * @return array{0: Pelanggan, 1: PesananAktif}
     */
    private function siapkanPesanan(
        string $statusPengerjaan = StatusPesanan::PESANAN_MASUK_ANTREAN,
        bool $buatPembayaran = true
    ): array {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');

        $permintaan = PermintaanService::create([
            'kode_pesanan'        => 'SRV-'.now()->format('Y').'-00001',
            'id_pelanggan'        => $pelanggan->id_pelanggan,
            'merek_hp'            => 'Samsung',
            'model_hp'            => 'A54',
            'kategori_kerusakan'  => 'Layar',
            'deskripsi_kerusakan' => 'Layar retak',
            'status_permintaan'   => StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
            'waktu_dibuat'        => now(),
        ]);

        $mitra = MitraService::create([
            'nama_pemilik'      => 'Pemilik Uji',
            'nama_usaha'        => 'Mitra Uji',
            'email'             => 'mitra@example.com',
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '081200000009',
            'alamat_usaha'      => 'Jl. Mitra No. 9',
            'status_verifikasi' => 'Disetujui',
        ]);

        $penawaran = PenawaranMitra::create([
            'id_permintaan'    => $permintaan->id_permintaan,
            'id_mitra'         => $mitra->id_mitra,
            'harga_penawaran'  => 250000,
            'estimasi_hari'    => 2,
            'estimasi_waktu'   => '2 hari',
            'waktu_penawaran'  => now(),
            'status_penawaran' => StatusPesanan::PENAWARAN_DISETUJUI,
        ]);

        // waktu_dibuat sengaja tetap supaya estimasi_selesai mudah dihitung.
        $pesanan = PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $pelanggan->id_pelanggan,
            'id_mitra'          => $mitra->id_mitra,
            'status_pengerjaan' => $statusPengerjaan,
            'waktu_dibuat'      => now(),
            'waktu_update_terakhir' => now(),
        ]);

        RiwayatStatusPesanan::create([
            'id_pesanan' => $pesanan->id_pesanan,
            'status'     => StatusPesanan::PESANAN_MASUK_ANTREAN,
            'catatan'    => 'Pesanan dibuat.',
            'aktor'      => 'Sistem',
            'waktu'      => now()->subHour(),
        ]);

        RiwayatStatusPesanan::create([
            'id_pesanan' => $pesanan->id_pesanan,
            'status'     => StatusPesanan::PESANAN_SEDANG_DIPROSES,
            'catatan'    => 'HP mulai dikerjakan.',
            'aktor'      => 'Mitra',
            'waktu'      => now(),
        ]);

        if ($buatPembayaran) {
            PembayaranTunai::create([
                'id_pesanan'         => $pesanan->id_pesanan,
                'jumlah_pembayaran'  => 250000,
                'waktu_dibuat'       => now(),
                'status_pembayaran'  => 'Menunggu Konfirmasi',
            ]);
        }

        return [$pelanggan, $pesanan];
    }

    private function buatPelanggan(string $email, string $telepon): Pelanggan
    {
        return Pelanggan::create([
            'nama_lengkap'      => 'Pelanggan Uji',
            'email'             => $email,
            'nomor_telepon'     => $telepon,
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);
    }
}
