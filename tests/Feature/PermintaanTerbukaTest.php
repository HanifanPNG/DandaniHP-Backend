<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Support\StatusPesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermintaanTerbukaTest extends TestCase
{
    use RefreshDatabase;

    public function test_mitra_melihat_permintaan_terbuka_yang_belum_ditawar(): void
    {
        $mitra = $this->buatMitra('mitra@example.com', 'Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;
        $pelanggan = $this->buatPelanggan();

        // Terbuka dan belum ditawar -> tampil.
        $terbuka = $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            'SRV-2026-00001',
            'Layar'
        );

        // Terbuka tapi sudah ditawar mitra ini -> sembunyi.
        $sudahDitawar = $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            'SRV-2026-00002',
            'Layar'
        );
        $this->buatPenawaran($sudahDitawar, $mitra);

        // Sudah selesai dilelang -> sembunyi.
        $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
            'SRV-2026-00003',
            'Layar'
        );

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mitra/permintaan');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_permintaan', $terbuka->id_permintaan)
            ->assertJsonPath('data.0.kategori_kerusakan', 'Layar')
            // Nama pelanggan disamarkan, kontak tidak dikirim.
            ->assertJsonPath('data.0.nama_pelanggan', 'Budi S.');

        $this->assertArrayNotHasKey('nomor_telepon', $response->json('data.0'));
        $this->assertArrayNotHasKey('id_pelanggan', $response->json('data.0'));
    }

    public function test_filter_kategori(): void
    {
        $mitra = $this->buatMitra('mitra@example.com', 'Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;
        $pelanggan = $this->buatPelanggan();

        $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            'SRV-2026-00001',
            'Layar'
        );
        $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            'SRV-2026-00002',
            'Baterai'
        );

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mitra/permintaan?kategori=Baterai')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kategori_kerusakan', 'Baterai');
    }

    public function test_paginasi_sepuluh_per_halaman(): void
    {
        $mitra = $this->buatMitra('mitra@example.com', 'Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;
        $pelanggan = $this->buatPelanggan();

        for ($i = 1; $i <= 11; $i++) {
            $this->buatPermintaan(
                $pelanggan,
                StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
                'SRV-2026-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'Layar'
            );
        }

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mitra/permintaan')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 11)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_mitra_belum_verifikasi_ditolak_403(): void
    {
        $mitra = $this->buatMitra('mitra@example.com', 'Menunggu');
        $token = $mitra->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mitra/permintaan')
            ->assertForbidden();
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/mitra/permintaan')->assertUnauthorized();
    }

    private function buatPelanggan(): Pelanggan
    {
        return Pelanggan::create([
            'nama_lengkap'      => 'Budi Santoso',
            'email'             => 'budi@example.com',
            'nomor_telepon'     => '081200000001',
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);
    }

    private function buatMitra(string $email, string $status): MitraService
    {
        return MitraService::create([
            'nama_pemilik'      => 'Pemilik Mitra',
            'nama_usaha'        => 'Mitra Uji',
            'email'             => $email,
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '081200000009',
            'status_verifikasi' => $status,
        ]);
    }

    private function buatPermintaan(
        Pelanggan $pelanggan,
        string $status,
        string $kode,
        string $kategori
    ): PermintaanService {
        return PermintaanService::create([
            'kode_pesanan'        => $kode,
            'id_pelanggan'        => $pelanggan->id_pelanggan,
            'merek_hp'            => 'Samsung',
            'model_hp'            => 'A54',
            'kategori_kerusakan'  => $kategori,
            'deskripsi_kerusakan' => 'Kerusakan '.$kategori,
            'status_permintaan'   => $status,
            'waktu_dibuat'        => now(),
        ]);
    }

    private function buatPenawaran(
        PermintaanService $permintaan,
        MitraService $mitra
    ): PenawaranMitra {
        return PenawaranMitra::create([
            'id_permintaan'    => $permintaan->id_permintaan,
            'id_mitra'         => $mitra->id_mitra,
            'harga_penawaran'  => 250000,
            'estimasi_hari'    => 2,
            'estimasi_waktu'   => '2 hari',
            'waktu_penawaran'  => now(),
            'status_penawaran' => StatusPesanan::PENAWARAN_MENUNGGU,
        ]);
    }
}
