<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Models\PesananAktif;
use App\Support\StatusPesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesananListTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_pesanan_aktif_milik_sendiri_urut_terbaru(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $mitra = $this->buatMitra();

        // Tiga pesanan milik pelanggan: antrean (lama), diproses (baru),
        // dan selesai (harus tidak muncul).
        $this->buatPesanan(
            $pelanggan,
            $mitra,
            StatusPesanan::PESANAN_MASUK_ANTREAN,
            now()->subDays(2)
        );
        $this->buatPesanan(
            $pelanggan,
            $mitra,
            StatusPesanan::PESANAN_SEDANG_DIPROSES,
            now(),
            'SRV-'.now()->format('Y').'-00002'
        );
        $this->buatPesanan(
            $pelanggan,
            $mitra,
            StatusPesanan::PESANAN_SELESAI,
            now()->addDay(),
            'SRV-'.now()->format('Y').'-00003'
        );

        // Pesanan milik pelanggan lain tidak boleh ikut.
        $lain = $this->buatPelanggan('lain@example.com', '081200000002');
        $this->buatPesanan(
            $lain,
            $mitra,
            StatusPesanan::PESANAN_SEDANG_DIPROSES,
            now()->addDays(2),
            'SRV-'.now()->format('Y').'-00004'
        );

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/pesanan');

        $response->assertOk()
            // Hanya 2 pesanan aktif milik sendiri.
            ->assertJsonCount(2, 'data')
            // Urut waktu_update_terakhir desc: sedang diproses lebih dulu.
            ->assertJsonPath('data.0.status_pengerjaan', StatusPesanan::PESANAN_SEDANG_DIPROSES)
            ->assertJsonPath('data.1.status_pengerjaan', StatusPesanan::PESANAN_MASUK_ANTREAN)
            // Ringkasan untuk kartu beranda.
            ->assertJsonPath('data.0.tipe_hp', 'Samsung A54')
            ->assertJsonPath('data.0.mitra.nama_usaha', 'Mitra Uji')
            ->assertJsonPath('data.0.harga_penawaran', 250000)
            ->assertJsonPath('data.0.status_kanonik', StatusPesanan::IN_PROGRESS);
    }

    public function test_tanpa_pesanan_aktif_mengembalikan_array_kosong(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/pesanan')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/pesanan')->assertUnauthorized();
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

    private function buatPesanan(
        Pelanggan $pelanggan,
        MitraService $mitra,
        string $status,
        \DateTimeInterface $waktuUpdate,
        ?string $kode = null
    ): PesananAktif {
        // Kode unik per permintaan supaya generator tidak ikut diuji di sini.
        $kode ??= 'SRV-'.now()->format('Y').'-'.str_pad(
            (string) (PermintaanService::count() + 1),
            5,
            '0',
            STR_PAD_LEFT
        );

        $permintaan = PermintaanService::create([
            'kode_pesanan'        => $kode,
            'id_pelanggan'        => $pelanggan->id_pelanggan,
            'merek_hp'            => 'Samsung',
            'model_hp'            => 'A54',
            'kategori_kerusakan'  => 'Layar',
            'deskripsi_kerusakan' => 'Layar retak',
            'status_permintaan'   => StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
            'waktu_dibuat'        => now(),
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

        return PesananAktif::create([
            'id_penawaran'          => $penawaran->id_penawaran,
            'id_permintaan'         => $permintaan->id_permintaan,
            'id_pelanggan'          => $pelanggan->id_pelanggan,
            'id_mitra'              => $mitra->id_mitra,
            'status_pengerjaan'     => $status,
            'waktu_dibuat'          => now(),
            'waktu_update_terakhir' => $waktuUpdate,
        ]);
    }
}
