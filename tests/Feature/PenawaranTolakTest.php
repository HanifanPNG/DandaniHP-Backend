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

class PenawaranTolakTest extends TestCase
{
    use RefreshDatabase;

    public function test_menolak_satu_dari_dua_masih_menunggu_persetujuan(): void
    {
        [$pelanggan, $permintaan, $pertama, $kedua] = $this->siapkanDuaPenawaran();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/'.$pertama->id_penawaran.'/tolak', [
                'alasan_penolakan' => 'Harga terlalu mahal.',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Penawaran berhasil ditolak.')
            ->assertJsonPath('data.status_penawaran', StatusPesanan::PENAWARAN_DITOLAK)
            ->assertJsonPath('data.alasan_penolakan', 'Harga terlalu mahal.');

        $this->assertDatabaseHas('penawaran_mitra', [
            'id_penawaran'     => $pertama->id_penawaran,
            'status_penawaran' => StatusPesanan::PENAWARAN_DITOLAK,
            'alasan_penolakan' => 'Harga terlalu mahal.',
        ]);

        // Masih ada satu penawaran menunggu -> status belum rejected.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permintaan/'.$permintaan->id_permintaan)
            ->assertOk()
            ->assertJsonPath(
                'data.status_kanonik',
                StatusPesanan::WAITING_CUSTOMER_APPROVAL
            );

        // BR-05: tidak ada pesanan yang dibuat.
        $this->assertDatabaseCount('pesanan_aktif', 0);
    }

    public function test_semua_penawaran_ditolak_status_kanonik_rejected(): void
    {
        [$pelanggan, $permintaan, $pertama, $kedua] = $this->siapkanDuaPenawaran();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        foreach ([$pertama, $kedua] as $penawaran) {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->postJson('/api/penawaran/'.$penawaran->id_penawaran.'/tolak')
                ->assertOk();
        }

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permintaan/'.$permintaan->id_permintaan)
            ->assertOk()
            ->assertJsonPath('data.status_kanonik', StatusPesanan::REJECTED)
            ->assertJsonPath('data.label_status', 'Semua Penawaran Ditolak');

        $this->assertDatabaseCount('pesanan_aktif', 0);
    }

    public function test_alasan_lebih_dari_500_karakter_ditolak_422(): void
    {
        [$pelanggan, , $pertama] = $this->siapkanDuaPenawaran();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/'.$pertama->id_penawaran.'/tolak', [
                'alasan_penolakan' => str_repeat('a', 501),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('alasan_penolakan');

        // Status tidak berubah saat validasi gagal.
        $this->assertDatabaseHas('penawaran_mitra', [
            'id_penawaran'     => $pertama->id_penawaran,
            'status_penawaran' => StatusPesanan::PENAWARAN_MENUNGGU,
        ]);
    }

    public function test_menolak_penawaran_yang_sudah_ditolak_422(): void
    {
        [$pelanggan, , $pertama] = $this->siapkanDuaPenawaran();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/'.$pertama->id_penawaran.'/tolak')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/'.$pertama->id_penawaran.'/tolak')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status_penawaran');
    }

    public function test_pelanggan_lain_ditolak_403(): void
    {
        [, , $pertama] = $this->siapkanDuaPenawaran();

        $lain = Pelanggan::create([
            'nama_lengkap'      => 'Pelanggan Lain',
            'email'             => 'lain@example.com',
            'nomor_telepon'     => '081200000002',
            'alamat'            => 'Jl. Lain No. 2',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);
        $token = $lain->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/'.$pertama->id_penawaran.'/tolak')
            ->assertForbidden();

        $this->assertDatabaseHas('penawaran_mitra', [
            'id_penawaran'     => $pertama->id_penawaran,
            'status_penawaran' => StatusPesanan::PENAWARAN_MENUNGGU,
        ]);
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->postJson('/api/penawaran/1/tolak')->assertUnauthorized();
    }

    public function test_penawaran_tidak_ditemukan_404(): void
    {
        $pelanggan = $this->buatPelanggan();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/99999/tolak')
            ->assertNotFound();
    }

    /**
     * @return array{0: Pelanggan, 1: PermintaanService, 2: PenawaranMitra, 3: PenawaranMitra}
     */
    private function siapkanDuaPenawaran(): array
    {
        $pelanggan = $this->buatPelanggan();

        $permintaan = PermintaanService::create([
            'kode_pesanan'        => 'SRV-'.now()->format('Y').'-00001',
            'id_pelanggan'        => $pelanggan->id_pelanggan,
            'merek_hp'            => 'Samsung',
            'model_hp'            => 'A54',
            'kategori_kerusakan'  => 'Layar',
            'deskripsi_kerusakan' => 'Layar retak',
            'status_permintaan'   => StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            'waktu_dibuat'        => now(),
        ]);

        $mitraA = $this->buatMitra('Mitra A', 'mitra.a@example.com', 4.8);
        $mitraB = $this->buatMitra('Mitra B', 'mitra.b@example.com', 4.2);

        return [
            $pelanggan,
            $permintaan,
            $this->buatPenawaran($permintaan, $mitraA, 250000),
            $this->buatPenawaran($permintaan, $mitraB, 300000),
        ];
    }

    private function buatPelanggan(): Pelanggan
    {
        return Pelanggan::create([
            'nama_lengkap'      => 'Pelanggan Uji',
            'email'             => 'pemilik@example.com',
            'nomor_telepon'     => '081200000001',
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
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
        int $harga
    ): PenawaranMitra {
        return PenawaranMitra::create([
            'id_permintaan'    => $permintaan->id_permintaan,
            'id_mitra'         => $mitra->id_mitra,
            'harga_penawaran'  => $harga,
            'estimasi_hari'    => 2,
            'estimasi_waktu'   => '2 hari',
            'waktu_penawaran'  => now(),
            'status_penawaran' => StatusPesanan::PENAWARAN_MENUNGGU,
        ]);
    }
}
