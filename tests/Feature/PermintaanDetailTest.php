<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermintaanDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilik_bisa_melihat_detail_permintaan(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $permintaan = $this->buatPermintaan($pelanggan);
        // Satu penawaran supaya status kanonik & ringkasan ikut teruji.
        $this->buatPenawaran($permintaan);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permintaan/'.$permintaan->id_permintaan);

        $response->assertOk()
            ->assertJsonPath('data.id_permintaan', $permintaan->id_permintaan)
            ->assertJsonPath('data.kode_pesanan', $permintaan->kode_pesanan)
            // Ada penawaran menunggu keputusan -> status kanonik ini.
            ->assertJsonPath('data.status_kanonik', 'waiting_customer_approval')
            ->assertJsonPath('data.label_status', 'Menunggu Persetujuan Anda')
            ->assertJsonPath('data.jumlah_penawaran', 1)
            ->assertJsonPath('data.penawaran.0.mitra.nama_usaha', 'Mitra Uji')
            ->assertJsonCount(0, 'data.lampiran');
    }

    public function test_pelanggan_lain_ditolak_403(): void
    {
        $pemilik = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $lain = $this->buatPelanggan('lain@example.com', '081200000002');
        $token = $lain->createToken('tes')->plainTextToken;

        $permintaan = $this->buatPermintaan($pemilik);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permintaan/'.$permintaan->id_permintaan)
            ->assertForbidden();
    }

    public function test_permintaan_tidak_ditemukan_404(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permintaan/99999')
            ->assertNotFound();
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/permintaan/1')->assertUnauthorized();
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

    private function buatPermintaan(Pelanggan $pelanggan): PermintaanService
    {
        return PermintaanService::create([
            'kode_pesanan'        => 'SRV-'.now()->format('Y').'-00001',
            'id_pelanggan'        => $pelanggan->id_pelanggan,
            'merek_hp'            => 'Samsung',
            'model_hp'            => 'A54',
            'kategori_kerusakan'  => 'Layar',
            'deskripsi_kerusakan' => 'Layar retak',
            'status_permintaan'   => 'Mencari Penawaran',
            'waktu_dibuat'        => now(),
        ]);
    }

    private function buatPenawaran(PermintaanService $permintaan): PenawaranMitra
    {
        $mitra = MitraService::create([
            'nama_pemilik'      => 'Pemilik Uji',
            'nama_usaha'        => 'Mitra Uji',
            'email'             => 'mitra@example.com',
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '081200000009',
            'status_verifikasi' => 'Disetujui',
        ]);

        return PenawaranMitra::create([
            'id_permintaan'   => $permintaan->id_permintaan,
            'id_mitra'        => $mitra->id_mitra,
            'harga_penawaran' => 250000,
            'estimasi_hari'   => 2,
            'estimasi_waktu'  => '2 hari',
            'waktu_penawaran' => now(),
            'status_penawaran' => 'Menunggu Keputusan',
        ]);
    }
}
