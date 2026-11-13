<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermintaanStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_pelanggan_terverifikasi_bisa_membuat_permintaan(): void
    {
        $pelanggan = $this->buatPelanggan();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/permintaan', $this->payload());

        $response->assertCreated()
            // Format kode dari generator T-21.
            ->assertJsonPath('data.kode_pesanan', 'SRV-'.now()->format('Y').'-00001')
            // Status awal selalu ditentukan server.
            ->assertJsonPath('data.status_permintaan', 'Mencari Penawaran');

        // Pemilik permintaan berasal dari token, bukan dari body.
        $this->assertDatabaseHas('permintaan_service', [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'merek_hp'     => 'Samsung',
        ]);
    }

    public function test_pelanggan_belum_verifikasi_ditolak_403(): void
    {
        $pelanggan = $this->buatPelanggan('Menunggu');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/permintaan', $this->payload())
            ->assertForbidden();

        // Tidak ada data yang tersimpan saat ditolak.
        $this->assertDatabaseCount('permintaan_service', 0);
    }

    public function test_id_pelanggan_di_body_ditolak_422(): void
    {
        $pelanggan = $this->buatPelanggan();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/permintaan', $this->payload() + ['id_pelanggan' => 999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('id_pelanggan');
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->postJson('/api/permintaan', $this->payload())
            ->assertUnauthorized();
    }

    private function buatPelanggan(string $status = 'Disetujui'): Pelanggan
    {
        return Pelanggan::create([
            'nama_lengkap'      => 'Pelanggan Uji',
            'email'             => 'uji@example.com',
            'nomor_telepon'     => '081200000001',
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => $status,
        ]);
    }

    private function payload(): array
    {
        return [
            'merek_hp'            => 'Samsung',
            'model_hp'            => 'A54',
            'kategori_kerusakan'  => 'Layar',
            'deskripsi_kerusakan' => 'Layar retak setelah jatuh',
            'lama_kerusakan'      => '2 hari',
        ];
    }
}
