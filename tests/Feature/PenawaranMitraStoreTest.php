<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Support\StatusPesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenawaranMitraStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_mitra_terverifikasi_bisa_mengirim_penawaran(): void
    {
        $mitra = $this->buatMitra('Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;
        $permintaan = $this->buatPermintaan();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/permintaan/'.$permintaan->id_permintaan.'/penawaran', [
                'harga_penawaran'     => 275000,
                'estimasi_hari'       => 2,
                'estimasi_waktu'      => '2 hari',
                'catatan_pemeriksaan' => 'LCD harus diganti.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.harga_penawaran', 275000)
            ->assertJsonPath('data.estimasi_hari', 2)
            ->assertJsonPath('data.status_penawaran', StatusPesanan::PENAWARAN_MENUNGGU)
            ->assertJsonPath('data.mitra.nama_usaha', 'Mitra Uji');

        // id_mitra dari token, bukan dari body.
        $this->assertDatabaseHas('penawaran_mitra', [
            'id_permintaan'   => $permintaan->id_permintaan,
            'id_mitra'        => $mitra->id_mitra,
            'harga_penawaran' => 275000,
            'status_penawaran' => StatusPesanan::PENAWARAN_MENUNGGU,
        ]);

        // Pelanggan mendapat notifikasi penawaran baru.
        $this->assertDatabaseHas('notifikasi', [
            'id_pelanggan' => $permintaan->id_pelanggan,
            'tipe'         => 'Penawaran',
            'judul'        => 'Penawaran baru',
        ]);
    }

    public function test_penawaran_ganda_ditolak_422(): void
    {
        $mitra = $this->buatMitra('Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;
        $permintaan = $this->buatPermintaan();

        $payload = [
            'harga_penawaran' => 250000,
            'estimasi_waktu'  => '2 hari',
        ];

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/permintaan/'.$permintaan->id_permintaan.'/penawaran', $payload)
            ->assertCreated();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/permintaan/'.$permintaan->id_permintaan.'/penawaran', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('id_mitra');

        $this->assertDatabaseCount('penawaran_mitra', 1);
    }

    public function test_permintaan_yang_sudah_dilelang_ditolak_422(): void
    {
        $mitra = $this->buatMitra('Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;
        $permintaan = $this->buatPermintaan(StatusPesanan::PERMINTAAN_SELESAI_DILELANG);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/permintaan/'.$permintaan->id_permintaan.'/penawaran', [
                'harga_penawaran' => 250000,
                'estimasi_waktu'  => '2 hari',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('permintaan');

        $this->assertDatabaseCount('penawaran_mitra', 0);
    }

    public function test_mitra_belum_verifikasi_ditolak_403(): void
    {
        $mitra = $this->buatMitra('Menunggu');
        $token = $mitra->createToken('tes')->plainTextToken;
        $permintaan = $this->buatPermintaan();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/permintaan/'.$permintaan->id_permintaan.'/penawaran', [
                'harga_penawaran' => 250000,
                'estimasi_waktu'  => '2 hari',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('penawaran_mitra', 0);
    }

    public function test_validasi_harga_dan_estimasi(): void
    {
        $mitra = $this->buatMitra('Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;
        $permintaan = $this->buatPermintaan();

        // Harga 0 dan estimasi_waktu kosong.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/permintaan/'.$permintaan->id_permintaan.'/penawaran', [
                'harga_penawaran' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['harga_penawaran', 'estimasi_waktu']);
    }

    public function test_id_mitra_di_body_ditolak_422(): void
    {
        $mitra = $this->buatMitra('Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;
        $permintaan = $this->buatPermintaan();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/permintaan/'.$permintaan->id_permintaan.'/penawaran', [
                'harga_penawaran' => 250000,
                'estimasi_waktu'  => '2 hari',
                'id_mitra'        => 999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('id_mitra');
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->postJson('/api/mitra/permintaan/1/penawaran')->assertUnauthorized();
    }

    public function test_permintaan_tidak_ditemukan_404(): void
    {
        $mitra = $this->buatMitra('Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/permintaan/99999/penawaran', [
                'harga_penawaran' => 250000,
                'estimasi_waktu'  => '2 hari',
            ])
            ->assertNotFound();
    }

    private function buatMitra(string $status): MitraService
    {
        return MitraService::create([
            'nama_pemilik'      => 'Pemilik Mitra',
            'nama_usaha'        => 'Mitra Uji',
            'email'             => 'mitra@example.com',
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '081200000009',
            'status_verifikasi' => $status,
        ]);
    }

    private function buatPermintaan(
        string $status = StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN
    ): PermintaanService {
        $pelanggan = Pelanggan::create([
            'nama_lengkap'      => 'Budi Santoso',
            'email'             => 'budi@example.com',
            'nomor_telepon'     => '081200000001',
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);

        return PermintaanService::create([
            'kode_pesanan'        => 'SRV-2026-00001',
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
