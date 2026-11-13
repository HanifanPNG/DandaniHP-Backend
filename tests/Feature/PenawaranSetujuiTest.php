<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Notifikasi;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Models\PesananAktif;
use App\Models\RiwayatStatusPesanan;
use App\Support\StatusPesanan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenawaranSetujuiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilik_menyetujui_penawaran_dan_pesanan_dibuat(): void
    {
        [$pelanggan, $permintaan, $pilihan, $lain] = $this->siapkanDuaPenawaran();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/'.$pilihan->id_penawaran.'/setujui');

        $response->assertCreated()
            ->assertJsonPath('data.status_pengerjaan', StatusPesanan::PESANAN_MASUK_ANTREAN)
            ->assertJsonPath('data.status_kanonik', StatusPesanan::QUEUED)
            ->assertJsonPath('data.label_status', 'Masuk Antrean')
            ->assertJsonPath('data.kode_pesanan', $permintaan->kode_pesanan)
            ->assertJsonPath('data.mitra.id_mitra', $pilihan->id_mitra);

        // Penawaran terpilih Disetujui, pesaingnya Kedaluwarsa.
        $this->assertDatabaseHas('penawaran_mitra', [
            'id_penawaran'     => $pilihan->id_penawaran,
            'status_penawaran' => StatusPesanan::PENAWARAN_DISETUJUI,
        ]);
        $this->assertDatabaseHas('penawaran_mitra', [
            'id_penawaran'     => $lain->id_penawaran,
            'status_penawaran' => StatusPesanan::PENAWARAN_KEDALUWARSA,
        ]);

        // Pesanan aktif memakai FK dari penawaran, bukan input client.
        $this->assertDatabaseHas('pesanan_aktif', [
            'id_penawaran'      => $pilihan->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $pelanggan->id_pelanggan,
            'id_mitra'          => $pilihan->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_MASUK_ANTREAN,
        ]);

        // Permintaan tidak lagi mencari penawaran.
        $this->assertDatabaseHas('permintaan_service', [
            'id_permintaan'     => $permintaan->id_permintaan,
            'status_permintaan' => StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
        ]);

        // Riwayat awal dicatat sistem.
        $pesanan = PesananAktif::where('id_permintaan', $permintaan->id_permintaan)->first();
        $this->assertDatabaseHas('riwayat_status_pesanan', [
            'id_pesanan' => $pesanan->id_pesanan,
            'status'     => StatusPesanan::PESANAN_MASUK_ANTREAN,
            'aktor'      => 'Sistem',
        ]);

        // Notifikasi untuk pelanggan ikut dibuat.
        $this->assertDatabaseHas('notifikasi', [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tipe'         => 'Penawaran',
            'id_pesanan'   => $pesanan->id_pesanan,
        ]);

        // Detail permintaan sekarang berstatus queued.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/permintaan/'.$permintaan->id_permintaan)
            ->assertOk()
            ->assertJsonPath('data.status_kanonik', StatusPesanan::QUEUED);
    }

    public function test_menyetujui_dua_kali_ditolak_422(): void
    {
        [$pelanggan, , $pilihan] = $this->siapkanDuaPenawaran();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/'.$pilihan->id_penawaran.'/setujui')
            ->assertCreated();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/'.$pilihan->id_penawaran.'/setujui')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status_penawaran');

        // Tetap hanya satu pesanan.
        $this->assertDatabaseCount('pesanan_aktif', 1);
    }

    public function test_menyetujui_penawaran_yang_sudah_kedaluwarsa_ditolak_422(): void
    {
        [$pelanggan, , $pilihan, $lain] = $this->siapkanDuaPenawaran();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        // Menang dulu dengan penawaran pilihan.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/'.$pilihan->id_penawaran.'/setujui')
            ->assertCreated();

        // Penawaran lain sudah Kedaluwarsa, tidak bisa disetujui.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/'.$lain->id_penawaran.'/setujui')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status_penawaran');

        $this->assertDatabaseCount('pesanan_aktif', 1);
    }

    public function test_pelanggan_lain_ditolak_403(): void
    {
        [, , $pilihan] = $this->siapkanDuaPenawaran();

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
            ->postJson('/api/penawaran/'.$pilihan->id_penawaran.'/setujui')
            ->assertForbidden();

        $this->assertDatabaseCount('pesanan_aktif', 0);
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->postJson('/api/penawaran/1/setujui')->assertUnauthorized();
    }

    public function test_penawaran_tidak_ditemukan_404(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/99999/setujui')
            ->assertNotFound();
    }

    // Lapis kedua: walaupun validasi service dilewati, database tetap
    // menolak pesanan kedua untuk permintaan yang sama.
    public function test_unique_constraint_menjaga_satu_pesanan_per_permintaan(): void
    {
        [$pelanggan, $permintaan, $pilihan] = $this->siapkanDuaPenawaran();
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/penawaran/'.$pilihan->id_penawaran.'/setujui')
            ->assertCreated();

        $pesananPertama = PesananAktif::firstOrFail();

        $this->expectException(QueryException::class);

        // id_permintaan sama -> unique constraint menolak.
        PesananAktif::create([
            'id_penawaran'      => $pilihan->id_penawaran,
            'id_permintaan'     => $pesananPertama->id_permintaan,
            'id_pelanggan'      => $pelanggan->id_pelanggan,
            'id_mitra'          => $pilihan->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_MASUK_ANTREAN,
            'waktu_dibuat'      => now(),
        ]);
    }

    /**
     * Membuat pelanggan, permintaan, dan dua penawaran menunggu.
     *
     * @return array{0: Pelanggan, 1: PermintaanService, 2: PenawaranMitra, 3: PenawaranMitra}
     */
    private function siapkanDuaPenawaran(): array
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');

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

        $pilihan = $this->buatPenawaran($permintaan, $mitraA, 250000);
        $lain = $this->buatPenawaran($permintaan, $mitraB, 300000);

        return [$pelanggan, $permintaan, $pilihan, $lain];
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
