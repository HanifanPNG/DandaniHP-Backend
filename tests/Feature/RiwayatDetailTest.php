<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PembayaranTunai;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Models\PesananAktif;
use App\Models\RiwayatStatusPesanan;
use App\Models\Ulasan;
use App\Support\StatusPesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiwayatDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilik_melihat_detail_riwayat_selesai_dengan_ulasan(): void
    {
        [$pelanggan, $permintaan, $pesanan] = $this->siapkanPesananSelesai();

        // Ulasan sudah ada -> tidak boleh menulis ulasan lagi.
        Ulasan::create([
            'id_pesanan'       => $pesanan->id_pesanan,
            'id_pelanggan'     => $pelanggan->id_pelanggan,
            'id_mitra'         => $pesanan->id_mitra,
            'rating_bintang'   => 5,
            'komentar_ulasan'  => 'Servis cepat dan rapi.',
            'waktu_ulasan'     => now(),
        ]);

        $response = $this->withHeader(
            'Authorization',
            'Bearer '.$pelanggan->createToken('tes')->plainTextToken
        )->getJson('/api/riwayat/'.$permintaan->id_permintaan);

        $response->assertOk()
            ->assertJsonPath('data.status_kanonik', StatusPesanan::COMPLETED)
            ->assertJsonPath('data.label_status', 'Pesanan Selesai')
            ->assertJsonPath('data.pesanan.status_pengerjaan', StatusPesanan::PESANAN_SELESAI)
            ->assertJsonCount(2, 'data.timeline')
            ->assertJsonPath('data.timeline.0.status', StatusPesanan::PESANAN_MASUK_ANTREAN)
            ->assertJsonPath('data.timeline.1.status', StatusPesanan::PESANAN_SELESAI)
            // Pembayaran diterima + waktu konfirmasi mitra.
            ->assertJsonPath('data.pembayaran.status_pembayaran', 'Diterima')
            ->assertJsonPath('data.ulasan.rating_bintang', 5)
            ->assertJsonPath('data.ulasan.komentar_ulasan', 'Servis cepat dan rapi.')
            // Sudah ada ulasan -> flag false.
            ->assertJsonPath('data.bisa_ulasan', false);

        // tanggal_selesai diambil dari riwayat status Pesanan Selesai.
        $waktuSelesai = RiwayatStatusPesanan::where('id_pesanan', $pesanan->id_pesanan)
            ->where('status', StatusPesanan::PESANAN_SELESAI)
            ->value('waktu');

        $this->assertNotNull($response->json('data.tanggal_selesai'));
        $this->assertSame(
            $waktuSelesai->toIso8601String(),
            $response->json('data.tanggal_selesai')
        );
    }

    public function test_bisa_ulasan_true_saat_selesai_dan_belum_diulas(): void
    {
        [$pelanggan, $permintaan] = $this->siapkanPesananSelesai();

        $this->withHeader(
            'Authorization',
            'Bearer '.$pelanggan->createToken('tes')->plainTextToken
        )->getJson('/api/riwayat/'.$permintaan->id_permintaan)
            ->assertOk()
            ->assertJsonPath('data.ulasan', null)
            ->assertJsonPath('data.bisa_ulasan', true);
    }

    public function test_permintaan_ditolak_tanpa_pesanan_tetap_bisa_dilihat(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $mitra = $this->buatMitra();

        $permintaan = $this->buatPermintaan($pelanggan, StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN);
        $this->buatPenawaran($permintaan, $mitra, StatusPesanan::PENAWARAN_DITOLAK);

        $this->withHeader(
            'Authorization',
            'Bearer '.$pelanggan->createToken('tes')->plainTextToken
        )->getJson('/api/riwayat/'.$permintaan->id_permintaan)
            ->assertOk()
            ->assertJsonPath('data.status_kanonik', StatusPesanan::REJECTED)
            ->assertJsonPath('data.pesanan', null)
            ->assertJsonPath('data.pembayaran', null)
            ->assertJsonPath('data.tanggal_selesai', null)
            ->assertJsonPath('data.bisa_ulasan', false)
            ->assertJsonCount(0, 'data.timeline');
    }

    public function test_pelanggan_lain_ditolak_403(): void
    {
        [, $permintaan] = $this->siapkanPesananSelesai();

        $lain = $this->buatPelanggan('lain@example.com', '081200000002');

        $this->withHeader(
            'Authorization',
            'Bearer '.$lain->createToken('tes')->plainTextToken
        )->getJson('/api/riwayat/'.$permintaan->id_permintaan)
            ->assertForbidden();
    }

    public function test_riwayat_tidak_ditemukan_404(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');

        $this->withHeader(
            'Authorization',
            'Bearer '.$pelanggan->createToken('tes')->plainTextToken
        )->getJson('/api/riwayat/99999')
            ->assertNotFound();
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/riwayat/1')->assertUnauthorized();
    }

    /**
     * @return array{0: Pelanggan, 1: PermintaanService, 2: PesananAktif}
     */
    private function siapkanPesananSelesai(): array
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $mitra = $this->buatMitra();

        $permintaan = $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_SELESAI_DILELANG
        );
        $penawaran = $this->buatPenawaran(
            $permintaan,
            $mitra,
            StatusPesanan::PENAWARAN_DISETUJUI
        );

        $pesanan = PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $pelanggan->id_pelanggan,
            'id_mitra'          => $mitra->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_SELESAI,
            'waktu_dibuat'      => now()->subDays(3),
            'waktu_update_terakhir' => now(),
        ]);

        RiwayatStatusPesanan::create([
            'id_pesanan' => $pesanan->id_pesanan,
            'status'     => StatusPesanan::PESANAN_MASUK_ANTREAN,
            'catatan'    => 'Pesanan dibuat.',
            'aktor'      => 'Sistem',
            'waktu'      => now()->subDays(3),
        ]);

        RiwayatStatusPesanan::create([
            'id_pesanan' => $pesanan->id_pesanan,
            'status'     => StatusPesanan::PESANAN_SELESAI,
            'catatan'    => 'Pesanan selesai.',
            'aktor'      => 'Mitra',
            'waktu'      => now()->subHour(),
        ]);

        PembayaranTunai::create([
            'id_pesanan'             => $pesanan->id_pesanan,
            'jumlah_pembayaran'      => 250000,
            'waktu_dibuat'           => now()->subHours(2),
            'waktu_konfirmasi_mitra' => now()->subHour(),
            'status_pembayaran'      => 'Diterima',
        ]);

        return [$pelanggan, $permintaan, $pesanan];
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

    private function buatMitra(): MitraService
    {
        return MitraService::create([
            'nama_pemilik'      => 'Pemilik Uji',
            'nama_usaha'        => 'Mitra Uji',
            'email'             => 'mitra@example.com',
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '081200000009',
            'status_verifikasi' => 'Disetujui',
        ]);
    }

    private function buatPermintaan(
        Pelanggan $pelanggan,
        string $status
    ): PermintaanService {
        return PermintaanService::create([
            'kode_pesanan'        => 'SRV-'.now()->format('Y').'-00001',
            'id_pelanggan'        => $pelanggan->id_pelanggan,
            'merek_hp'            => 'Samsung',
            'model_hp'            => 'A54',
            'kategori_kerusakan'  => 'Layar',
            'deskripsi_kerusakan' => 'Layar retak',
            'status_permintaan'   => $status,
            'waktu_dibuat'        => now(),
        ]);
    }

    private function buatPenawaran(
        PermintaanService $permintaan,
        MitraService $mitra,
        string $status
    ): PenawaranMitra {
        return PenawaranMitra::create([
            'id_permintaan'    => $permintaan->id_permintaan,
            'id_mitra'         => $mitra->id_mitra,
            'harga_penawaran'  => 250000,
            'estimasi_hari'    => 2,
            'estimasi_waktu'   => '2 hari',
            'waktu_penawaran'  => now(),
            'status_penawaran' => $status,
        ]);
    }
}
