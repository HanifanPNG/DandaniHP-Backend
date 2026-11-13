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

class PesananMitraListTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_pesanan_milik_mitra_sendiri_urut_terbaru(): void
    {
        $mitra = $this->buatMitra('mitra@example.com');
        $mitraLain = $this->buatMitra('mitra.lain@example.com');
        $token = $mitra->createToken('tes')->plainTextToken;

        // Dua pesanan milik mitra: antrean (lama) dan diproses (baru).
        $this->buatPesanan(
            $mitra,
            StatusPesanan::PESANAN_MASUK_ANTREAN,
            now()->subDay(),
            'SRV-2026-00001'
        );
        $this->buatPesanan(
            $mitra,
            StatusPesanan::PESANAN_SEDANG_DIPROSES,
            now(),
            'SRV-2026-00002'
        );

        // Pesanan mitra lain tidak boleh ikut.
        $this->buatPesanan(
            $mitraLain,
            StatusPesanan::PESANAN_SEDANG_DIPROSES,
            now()->addDay(),
            'SRV-2026-00003'
        );

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mitra/pesanan');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            // Urut waktu_update_terakhir desc.
            ->assertJsonPath('data.0.status_pengerjaan', StatusPesanan::PESANAN_SEDANG_DIPROSES)
            ->assertJsonPath('data.1.status_pengerjaan', StatusPesanan::PESANAN_MASUK_ANTREAN)
            // Ringkasan kartu.
            ->assertJsonPath('data.0.kode_pesanan', 'SRV-2026-00002')
            ->assertJsonPath('data.0.tipe_hp', 'Samsung A54')
            ->assertJsonPath('data.0.status_kanonik', StatusPesanan::IN_PROGRESS)
            ->assertJsonPath('data.0.harga_penawaran', 250000);
    }

    public function test_filter_status(): void
    {
        $mitra = $this->buatMitra('mitra@example.com');
        $token = $mitra->createToken('tes')->plainTextToken;

        $this->buatPesanan(
            $mitra,
            StatusPesanan::PESANAN_MASUK_ANTREAN,
            now(),
            'SRV-2026-00001'
        );
        $this->buatPesanan(
            $mitra,
            StatusPesanan::PESANAN_SELESAI,
            now(),
            'SRV-2026-00002'
        );

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mitra/pesanan?status='.urlencode(StatusPesanan::PESANAN_SELESAI))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode_pesanan', 'SRV-2026-00002');
    }

    public function test_filter_status_tidak_dikenal_ditolak_422(): void
    {
        $mitra = $this->buatMitra('mitra@example.com');
        $token = $mitra->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mitra/pesanan?status=ngawur')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/mitra/pesanan')->assertUnauthorized();
    }

    private function buatMitra(string $email): MitraService
    {
        return MitraService::create([
            'nama_pemilik'      => 'Pemilik Mitra',
            'nama_usaha'        => 'Mitra Uji',
            'email'             => $email,
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '081200000009',
            'status_verifikasi' => 'Disetujui',
        ]);
    }

    private function buatPesanan(
        MitraService $mitra,
        string $status,
        \DateTimeInterface $waktuUpdate,
        string $kode
    ): PesananAktif {
        $pelanggan = Pelanggan::firstOrCreate(
            ['email' => 'budi@example.com'],
            [
                'nama_lengkap'      => 'Budi Santoso',
                'nomor_telepon'     => '081200000001',
                'alamat'            => 'Jl. Uji No. 1',
                'kata_sandi'        => 'password123',
                'status_verifikasi' => 'Disetujui',
            ]
        );

        $permintaan = PermintaanService::create([
            'kode_pesanan'        => $kode,
            'id_pelanggan'        => $pelanggan->id_pelanggan,
            'merek_hp'            => 'Samsung',
            'model_hp'            => 'A54',
            'kategori_kerusakan'  => 'Layar',
            'deskripsi_kerusakan' => 'Layar retak',
            'status_permintaan'   => StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
            'waktu_dibuat'        => now(),
        ]);

        $penawaran = PenawaranMitra::create([
            'id_permintaan'    => $permintaan->id_permintaan,
            'id_mitra'         => $mitra->id_mitra,
            'harga_penawaran'  => 250000,
            'estimasi_hari'    => 2,
            'estimasi_waktu'   => '2 hari',
            'waktu_penawaran'  => now(),
            'status_penawaran' => StatusPesanan::PENAWARAN_DISETUJUI,
        ]);

        return PesananAktif::create([
            'id_penawaran'          => $penawaran->id_penawaran,
            'id_permintaan'         => $permintaan->id_permintaan,
            'id_pelanggan'          => $pelanggan->id_pelanggan,
            'id_mitra'              => $mitra->id_mitra,
            'status_pengerjaan'     => $status,
            'waktu_dibuat'          => now(),
            'waktu_update_terakhir' => $waktuUpdate,
        ]);
    }
}
