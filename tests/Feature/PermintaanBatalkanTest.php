<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Models\PesananAktif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PermintaanBatalkanTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilik_bisa_membatalkan_permintaan(): void
    {
        // Disk palsu supaya bisa dipastikan file ikut terhapus.
        Storage::fake('public');

        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        // Buat lewat endpoint supaya lampiran benar-benar tersimpan.
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/permintaan', [
                'merek_hp'            => 'Samsung',
                'model_hp'            => 'A54',
                'kategori_kerusakan'  => 'Layar',
                'deskripsi_kerusakan' => 'Layar retak',
                'lampiran'            => [UploadedFile::fake()->image('retak.jpg')],
            ]);

        $response->assertCreated();

        $id = $response->json('data.id_permintaan');
        $this->assertCount(1, Storage::disk('public')->files('lampiran/'.$id));

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/permintaan/'.$id.'/batalkan')
            ->assertOk()
            ->assertJsonPath('message', 'Permintaan berhasil dibatalkan.')
            // Dibatalkan selalu menjadi status kanonik cancelled.
            ->assertJsonPath('data.status_kanonik', 'cancelled')
            ->assertJsonPath('data.status_permintaan', 'Dibatalkan')
            ->assertJsonCount(0, 'data.lampiran');

        $this->assertDatabaseHas('permintaan_service', [
            'id_permintaan'     => $id,
            'status_permintaan' => 'Dibatalkan',
        ]);

        // File fisik dan baris lampiran harus ikut terhapus.
        $this->assertDatabaseCount('lampiran_permintaan', 0);
        $this->assertCount(0, Storage::disk('public')->files('lampiran/'.$id));
    }

    public function test_pelanggan_lain_ditolak_403(): void
    {
        $pemilik = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $lain = $this->buatPelanggan('lain@example.com', '081200000002');
        $token = $lain->createToken('tes')->plainTextToken;

        $permintaan = $this->buatPermintaan($pemilik);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/permintaan/'.$permintaan->id_permintaan.'/batalkan')
            ->assertForbidden();

        // Status tidak berubah saat ditolak.
        $this->assertDatabaseHas('permintaan_service', [
            'id_permintaan'     => $permintaan->id_permintaan,
            'status_permintaan' => 'Mencari Penawaran',
        ]);
    }

    public function test_sudah_dibatalkan_ditolak_422(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $permintaan = $this->buatPermintaan($pelanggan, 'Dibatalkan');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/permintaan/'.$permintaan->id_permintaan.'/batalkan')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status_permintaan');
    }

    // BR-06: setelah penawaran disetujui (ada pesanan aktif),
    // pembatalan harus ditolak.
    public function test_sudah_ada_pesanan_ditolak_422(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $permintaan = $this->buatPermintaan($pelanggan);

        $mitra = MitraService::create([
            'nama_pemilik'      => 'Pemilik Uji',
            'nama_usaha'        => 'Mitra Uji',
            'email'             => 'mitra@example.com',
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '081200000009',
            'status_verifikasi' => 'Disetujui',
        ]);

        $penawaran = PenawaranMitra::create([
            'id_permintaan'   => $permintaan->id_permintaan,
            'id_mitra'        => $mitra->id_mitra,
            'harga_penawaran' => 250000,
            'estimasi_hari'   => 2,
            'estimasi_waktu'  => '2 hari',
            'waktu_penawaran' => now(),
            'status_penawaran' => 'Disetujui',
        ]);

        PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $pelanggan->id_pelanggan,
            'id_mitra'          => $mitra->id_mitra,
            'status_pengerjaan' => 'Masuk Antrean',
            'waktu_dibuat'      => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/permintaan/'.$permintaan->id_permintaan.'/batalkan')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status_permintaan');

        $this->assertDatabaseHas('permintaan_service', [
            'id_permintaan'     => $permintaan->id_permintaan,
            'status_permintaan' => 'Mencari Penawaran',
        ]);
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->postJson('/api/permintaan/1/batalkan')->assertUnauthorized();
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

    private function buatPermintaan(
        Pelanggan $pelanggan,
        string $status = 'Mencari Penawaran'
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
}
