<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_pelanggan_lihat_profil(): void
    {
        $pelanggan = Pelanggan::create([
            'nama_lengkap'      => 'Budi Santoso',
            'email'             => 'budi@example.com',
            'nomor_telepon'     => '081200000001',
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);

        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/profil')
            ->assertOk()
            ->assertJsonPath('data.nama_lengkap', 'Budi Santoso')
            ->assertJsonPath('data.email', 'budi@example.com')
            ->assertJsonPath('data.nomor_telepon', '081200000001')
            ->assertJsonPath('data.status_verifikasi', 'Disetujui');
    }

    public function test_update_nama_alamat(): void
    {
        $pelanggan = Pelanggan::create([
            'nama_lengkap'      => 'Budi Santoso',
            'email'             => 'budi@example.com',
            'nomor_telepon'     => '081200000001',
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);

        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/profil', [
                'nama_lengkap' => 'Budi Setiawan',
                'alamat'       => 'Jl. Baru No. 2',
            ])
            ->assertOk()
            ->assertJsonPath('data.nama_lengkap', 'Budi Setiawan')
            ->assertJsonPath('data.alamat', 'Jl. Baru No. 2');

        $this->assertDatabaseHas('pelanggan', [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'nama_lengkap' => 'Budi Setiawan',
            'alamat'       => 'Jl. Baru No. 2',
        ]);
    }

    public function test_update_nomor_telepon_dengan_penolakan_orang_lain(): void
    {
        $pelanggan1 = Pelanggan::create([
            'nama_lengkap'      => 'Budi Santoso',
            'email'             => 'budi@example.com',
            'nomor_telepon'     => '081200000001',
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);

        $pelanggan2 = Pelanggan::create([
            'nama_lengkap'      => 'Siti Aminah',
            'email'             => 'siti@example.com',
            'nomor_telepon'     => '081200000002',
            'alamat'            => 'Jl. Siti No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);

        $token = $pelanggan1->createToken('tes')->plainTextToken;

        // Coba pakai nomor telepon milik pelanggan lain → 422
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/profil', [
                'nomor_telepon' => '081200000002',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nomor_telepon');
    }

    public function test_update_nomor_telepon_sesuai_dengan_yang_dimiliki(): void
    {
        $pelanggan = Pelanggan::create([
            'nama_lengkap'      => 'Budi Santoso',
            'email'             => 'budi@example.com',
            'nomor_telepon'     => '081200000001',
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);

        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/profil', [
                'nomor_telepon' => '081200000099',
            ])
            ->assertOk()
            ->assertJsonPath('data.nomor_telepon', '081200000099');
    }

    public function test_status_verifikasi_tidak_bisa_diubah(): void
    {
        $pelanggan = Pelanggan::create([
            'nama_lengkap'      => 'Budi Santoso',
            'email'             => 'budi@example.com',
            'nomor_telepon'     => '081200000001',
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Menunggu',
        ]);

        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/profil', [
                'status_verifikasi' => 'Disetujui',
            ])
            ->assertOk();

        // Status verifikasi tetap Menunggu (tidak berubah)
        $pelanggan->refresh();
        $this->assertEquals('Menunggu', $pelanggan->status_verifikasi);
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/profil')->assertUnauthorized();
        $this->putJson('/api/profil', ['nama_lengkap' => 'Baru'])->assertUnauthorized();
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
}