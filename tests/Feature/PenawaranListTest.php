<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Support\StatusPesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenawaranListTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilik_melihat_penawaran_urut_harga_terendah(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;
        $permintaan = $this->buatPermintaan($pelanggan);

        // Mitra mahal, murah, dan satu yang sudah kedaluwarsa.
        $mahal = $this->buatMitra('Mitra Mahal', 'mahal@example.com', 4.5);
        $murah = $this->buatMitra('Mitra Murah', 'murah@example.com', 3.0);
        $kedaluwarsa = $this->buatMitra('Mitra Lama', 'lama@example.com', 5.0);

        $this->buatPenawaran($permintaan, $mahal, 300000, StatusPesanan::PENAWARAN_MENUNGGU);
        $this->buatPenawaran($permintaan, $murah, 200000, StatusPesanan::PENAWARAN_MENUNGGU);
        $this->buatPenawaran($permintaan, $kedaluwarsa, 250000, StatusPesanan::PENAWARAN_KEDALUWARSA);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permintaan/'.$permintaan->id_permintaan.'/penawaran');

        $response->assertOk()
            // Penawaran kedaluwarsa tidak ditampilkan.
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.mitra.nama_usaha', 'Mitra Murah')
            ->assertJsonPath('data.1.mitra.nama_usaha', 'Mitra Mahal')
            // Field yang dibutuhkan aplikasi ikut terkirim.
            ->assertJsonPath('data.0.harga_penawaran', 200000)
            ->assertJsonPath('data.0.status_penawaran', StatusPesanan::PENAWARAN_MENUNGGU)
            ->assertJsonPath('data.0.mitra.rating', 3)
            ->assertJsonStructure([
                'data' => [[
                    'id_penawaran',
                    'mitra' => ['id_mitra', 'nama_usaha', 'rating'],
                    'harga_penawaran',
                    'estimasi_hari',
                    'estimasi_waktu',
                    'catatan_pemeriksaan',
                    'status_penawaran',
                    'waktu_penawaran',
                ]],
            ]);
    }

    public function test_sort_rating_mengurutkan_rating_tertinggi(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;
        $permintaan = $this->buatPermintaan($pelanggan);

        $ratingTinggi = $this->buatMitra('Rating Tinggi', 'tinggi@example.com', 4.9);
        $ratingRendah = $this->buatMitra('Rating Rendah', 'rendah@example.com', 3.1);

        $this->buatPenawaran($permintaan, $ratingRendah, 150000, StatusPesanan::PENAWARAN_MENUNGGU);
        $this->buatPenawaran($permintaan, $ratingTinggi, 400000, StatusPesanan::PENAWARAN_MENUNGGU);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permintaan/'.$permintaan->id_permintaan.'/penawaran?sort=rating')
            ->assertOk()
            ->assertJsonPath('data.0.mitra.nama_usaha', 'Rating Tinggi')
            ->assertJsonPath('data.1.mitra.nama_usaha', 'Rating Rendah');
    }

    public function test_pelanggan_lain_ditolak_403(): void
    {
        $pemilik = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $lain = $this->buatPelanggan('lain@example.com', '081200000002');
        $token = $lain->createToken('tes')->plainTextToken;

        $permintaan = $this->buatPermintaan($pemilik);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permintaan/'.$permintaan->id_permintaan.'/penawaran')
            ->assertForbidden();
    }

    public function test_permintaan_tidak_ditemukan_404(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permintaan/99999/penawaran')
            ->assertNotFound();
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/permintaan/1/penawaran')->assertUnauthorized();
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
            'status_permintaan'   => StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            'waktu_dibuat'        => now(),
        ]);
    }

    private function buatMitra(string $namaUsaha, string $email, float $rating): MitraService
    {
        return MitraService::create([
            'nama_pemilik'      => 'Pemilik '.$namaUsaha,
            'nama_usaha'        => $namaUsaha,
            'email'             => $email,
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '08120000'.random_int(1000, 9999),
            'rating_rata_rata'  => $rating,
            'status_verifikasi' => 'Disetujui',
        ]);
    }

    private function buatPenawaran(
        PermintaanService $permintaan,
        MitraService $mitra,
        int $harga,
        string $status
    ): PenawaranMitra {
        return PenawaranMitra::create([
            'id_permintaan'      => $permintaan->id_permintaan,
            'id_mitra'           => $mitra->id_mitra,
            'harga_penawaran'    => $harga,
            'estimasi_hari'      => 2,
            'estimasi_waktu'     => '2 hari',
            'catatan_pemeriksaan' => 'Layar harus diganti',
            'waktu_penawaran'    => now(),
            'status_penawaran'   => $status,
        ]);
    }
}
