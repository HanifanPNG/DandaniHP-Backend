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

class PesananStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_mitra_bisa_mengubah_status_satu_langkah(): void
    {
        $mitra = $this->buatMitra('Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;

        // Buat permintaan & penawaran dulu.
        $permintaan = $this->buatPermintaan();
        $penawaran = $this->buatPenawaran($permintaan, $mitra);

        // Pesanan dimulai dari Masuk Antrean.
        $pesanan = PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $permintaan->id_pelanggan,
            'id_mitra'          => $mitra->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_MASUK_ANTREAN,
            'waktu_dibuat'      => now(),
            'waktu_update_terakhir' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/pesanan/'.$pesanan->id_pesanan.'/status', [
                'status_pengerjaan' => StatusPesanan::PESANAN_SEDANG_DIPROSES,
                'catatan_perkembangan' => 'Mulai dikerjakan hari ini.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status_pengerjaan', StatusPesanan::PESANAN_SEDANG_DIPROSES)
            ->assertJsonPath('data.catatan_perkembangan', 'Mulai dikerjakan hari ini.')
            // Riwayat dibuat dan actor Mitra.
            ->assertJsonCount(1, 'data.timeline')
            ->assertJsonPath('data.timeline.0.aktor', 'Mitra')
            ->assertJsonPath('data.timeline.0.status', StatusPesanan::PESANAN_SEDANG_DIPROSES);

        $this->assertDatabaseHas('riwayat_status_pesanan', [
            'id_pesanan' => $pesanan->id_pesanan,
            'status'     => StatusPesanan::PESANAN_SEDANG_DIPROSES,
            'aktor'      => 'Mitra',
        ]);
    }

    public function test_transisi_status_salah_ditolak_422(): void
    {
        $mitra = $this->buatMitra('Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;

        $permintaan = $this->buatPermintaan();
        $penawaran = $this->buatPenawaran($permintaan, $mitra);

        // Pesanan dimulai dari Masuk Antrean.
        $pesanan = PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $permintaan->id_pelanggan,
            'id_mitra'          => $mitra->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_MASUK_ANTREAN,
            'waktu_dibuat'      => now(),
            'waktu_update_terakhir' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/pesanan/'.$pesanan->id_pesanan.'/status', [
                'status_pengerjaan' => StatusPesanan::PESANAN_SERVICE_SELESAI,
                'catatan_perkembangan' => 'Lompat status.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status_pengerjaan');
    }

    public function test_mitra_lain_ditolak_403(): void
    {
        $mitra1 = $this->buatMitra('Disetujui');
        $mitra2 = $this->buatMitra('Disetujui');
        $token = $mitra2->createToken('tes')->plainTextToken;

        $permintaan = $this->buatPermintaan();
        $penawaran = $this->buatPenawaran($permintaan, $mitra1);

        $pesanan = PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $permintaan->id_pelanggan,
            'id_mitra'          => $mitra1->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_MASUK_ANTREAN,
            'waktu_dibuat'      => now(),
            'waktu_update_terakhir' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/pesanan/'.$pesanan->id_pesanan.'/status', [
                'status_pengerjaan' => StatusPesanan::PESANAN_SEDANG_DIPROSES,
            ])
            ->assertForbidden();
    }

    public function test_pesanan_tidak_ditemukan_404(): void
    {
        $mitra = $this->buatMitra('Disetujui');
        $token = $mitra->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mitra/pesanan/99999/status', [
                'status_pengerjaan' => StatusPesanan::PESANAN_SEDANG_DIPROSES,
            ])
            ->assertNotFound();
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->postJson('/api/mitra/pesanan/1/status')->assertUnauthorized();
    }

    private function buatMitra(string $status): MitraService
    {
        $suffix = rand(1000, 9999);
        return MitraService::create([
            'nama_pemilik'      => 'Pemilik Mitra',
            'nama_usaha'        => 'Mitra Uji',
            'email'             => 'mitra'.$suffix.'@example.com',
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '08120000'.($suffix % 1000),
            'status_verifikasi' => $status,
        ]);
    }

    private function buatPermintaan(): PermintaanService
    {
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
            'status_permintaan'   => StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
            'waktu_dibuat'        => now(),
        ]);
    }

    private function buatPenawaran(PermintaanService $permintaan, MitraService $mitra): PenawaranMitra
    {
        return PenawaranMitra::create([
            'id_permintaan'    => $permintaan->id_permintaan,
            'id_mitra'         => $mitra->id_mitra,
            'harga_penawaran'  => 250000,
            'estimasi_hari'    => 2,
            'estimasi_waktu'   => '2 hari',
            'waktu_penawaran'  => now(),
            'status_penawaran' => StatusPesanan::PENAWARAN_DISETUJUI,
        ]);
    }
}