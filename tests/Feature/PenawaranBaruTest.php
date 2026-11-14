<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Support\StatusPesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenawaranBaruTest extends TestCase
{
    use RefreshDatabase;

    public function test_pelanggan_melihat_tawaran_baru(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $mitra = $this->buatMitra('Disetujui');

        // Permintaan yang masih mencari penawaran.
        $permintaan = $this->buatPermintaan($pelanggan, StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN);

        // Penawaran yang menunggu keputusan.
        PenawaranMitra::create([
            'id_permintaan'    => $permintaan->id_permintaan,
            'id_mitra'         => $mitra->id_mitra,
            'harga_penawaran'  => 250000,
            'estimasi_hari'    => 2,
            'estimasi_waktu'   => '2 hari',
            'waktu_penawaran'  => now(),
            'status_penawaran' => StatusPesanan::PENAWARAN_MENUNGGU,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/penawaran/baru')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.harga_penawaran', 250000)
            ->assertJsonPath('data.0.permintaan.kode_pesanan', $permintaan->kode_pesanan)
            ->assertJsonPath('data.0.mitra.nama_usaha', 'Mitra Uji');
    }

    public function test_penawaran_disetujui_tidak_muncul_di_tawaran_baru(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $mitra = $this->buatMitra('Disetujui');
        $permintaan = $this->buatPermintaan($pelanggan, StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN);

        // Penawaran yang sudah disetujui.
        PenawaranMitra::create([
            'id_permintaan'    => $permintaan->id_permintaan,
            'id_mitra'         => $mitra->id_mitra,
            'harga_penawaran'  => 250000,
            'estimasi_hari'    => 2,
            'estimasi_waktu'   => '2 hari',
            'waktu_penawaran'  => now(),
            'status_penawaran' => StatusPesanan::PENAWARAN_DISETUJUI,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/penawaran/baru')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_penawaran_milik_pelanggan_lain_tidak_muncul(): void
    {
        $pelanggan1 = $this->buatPelanggan('Disetujui');
        $pelanggan2 = $this->buatPelanggan('Disetujui');
        $token = $pelanggan1->createToken('tes')->plainTextToken;

        $mitra = $this->buatMitra('Disetujui');
        $permintaan = $this->buatPermintaan($pelanggan2, StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN);

        PenawaranMitra::create([
            'id_permintaan'    => $permintaan->id_permintaan,
            'id_mitra'         => $mitra->id_mitra,
            'harga_penawaran'  => 250000,
            'estimasi_hari'    => 2,
            'estimasi_waktu'   => '2 hari',
            'waktu_penawaran'  => now(),
            'status_penawaran' => StatusPesanan::PENAWARAN_MENUNGGU,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/penawaran/baru')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/penawaran/baru')->assertUnauthorized();
    }

    private function buatPelanggan(string $status): Pelanggan
    {
        $suffix = rand(1000, 9999);
        return Pelanggan::create([
            'nama_lengkap'      => 'Budi Santoso',
            'email'             => 'budi'.$suffix.'@example.com',
            'nomor_telepon'     => '0812'.($suffix),
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => $status,
        ]);
    }

    private function buatMitra(string $status): MitraService
    {
        $suffix = rand(1000, 9999);
        return MitraService::create([
            'nama_pemilik'      => 'Pemilik Mitra',
            'nama_usaha'        => 'Mitra Uji',
            'email'             => 'mitra'.$suffix.'@example.com',
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '0812'.($suffix % 100),
            'status_verifikasi' => $status,
        ]);
    }

    private function buatPermintaan(Pelanggan $pelanggan, string $status): PermintaanService
    {
        return PermintaanService::create([
            'kode_pesanan'        => 'SRV-2026-'.rand(10000, 99999),
            'id_pelanggan'        => $pelanggan->id_pelanggan,
            'merek_hp'            => 'Samsung',
            'model_hp'            => 'A54',
            'kategori_kerusakan'  => 'Layar',
            'deskripsi_kerusakan' => 'Layar retak',
            'status_permintaan'   => $status,
            'waktu_dibuat'        => now(),
        ]);
    }
}