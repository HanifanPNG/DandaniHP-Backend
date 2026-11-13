<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_lampiran_tersimpan_dan_url_dikembalikan(): void
    {
        // Disk palsu: file tidak benar-benar ditulis ke storage asli.
        Storage::fake('public');

        $pelanggan = $this->buatPelanggan();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/permintaan', $this->payload() + [
                'lampiran' => [
                    UploadedFile::fake()->image('retak-1.jpg'),
                    UploadedFile::fake()->image('retak-2.png'),
                ],
            ]);

        $response->assertCreated();

        $this->assertCount(2, $response->json('data.lampiran'));
        $this->assertDatabaseCount('lampiran_permintaan', 2);

        // Nama file diacak, jadi cukup cek jumlah file di foldernya.
        $this->assertCount(
            2,
            Storage::disk('public')->files('lampiran/'.$response->json('data.id_permintaan'))
        );
    }

    public function test_lampiran_lebih_dari_empat_ditolak_422(): void
    {
        Storage::fake('public');

        $pelanggan = $this->buatPelanggan();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/permintaan', $this->payload() + [
                'lampiran' => array_map(
                    fn ($i) => UploadedFile::fake()->image("foto-{$i}.jpg"),
                    range(1, 5)
                ),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lampiran');

        $this->assertDatabaseCount('lampiran_permintaan', 0);
    }

    public function test_lampiran_lebih_dari_dua_mb_ditolak_422(): void
    {
        Storage::fake('public');

        $pelanggan = $this->buatPelanggan();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        // size() dihitung dalam kilobyte: 3000 KB > 2048 KB.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/permintaan', $this->payload() + [
                'lampiran' => [UploadedFile::fake()->image('besar.jpg')->size(3000)],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lampiran.0');

        $this->assertDatabaseCount('lampiran_permintaan', 0);
    }

    public function test_lampiran_bukan_gambar_ditolak_422(): void
    {
        Storage::fake('public');

        $pelanggan = $this->buatPelanggan();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/permintaan', $this->payload() + [
                'lampiran' => [UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lampiran.0');
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
