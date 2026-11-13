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

class RiwayatListTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_semua_menampilkan_permintaan_milik_sendiri(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $lain = $this->buatPelanggan('lain@example.com', '081200000002');
        $mitra = $this->buatMitra();

        // 5 riwayat pelanggan: ditolak, dibatalkan, berlangsung, selesai,
        // dan masih mencari penawaran.
        $rejected = $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            now()->subDays(5),
            'SRV-2026-00001'
        );
        $this->buatPenawaran($rejected, $mitra, StatusPesanan::PENAWARAN_DITOLAK);

        $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_DIBATALKAN,
            now()->subDays(4),
            'SRV-2026-00002'
        );

        $berlangsung = $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
            now()->subDays(3),
            'SRV-2026-00003'
        );
        $penawaranBerlangsung = $this->buatPenawaran(
            $berlangsung,
            $mitra,
            StatusPesanan::PENAWARAN_DISETUJUI,
            300000
        );
        $this->buatPesanan(
            $berlangsung,
            $penawaranBerlangsung,
            StatusPesanan::PESANAN_SEDANG_DIPROSES
        );

        $selesai = $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
            now()->subDays(2),
            'SRV-2026-00004'
        );
        $penawaranSelesai = $this->buatPenawaran(
            $selesai,
            $mitra,
            StatusPesanan::PENAWARAN_DISETUJUI,
            250000
        );
        $this->buatPesanan(
            $selesai,
            $penawaranSelesai,
            StatusPesanan::PESANAN_SELESAI
        );

        $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            now(),
            'SRV-2026-00005'
        );

        // Permintaan pelanggan lain tidak boleh ikut.
        $this->buatPermintaan(
            $lain,
            StatusPesanan::PERMINTAAN_DIBATALKAN,
            now(),
            'SRV-2026-00006'
        );

        $response = $this->withHeader(
            'Authorization',
            'Bearer '.$pelanggan->createToken('tes')->plainTextToken
        )->getJson('/api/riwayat?filter=semua');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 5)
            // Terbaru di atas: permintaan yang dibuat sekarang.
            ->assertJsonPath('data.0.kode_pesanan', 'SRV-2026-00005')
            ->assertJsonPath('data.1.kode_pesanan', 'SRV-2026-00004')
            // Item selesai membawa mitra, biaya, dan badge status.
            ->assertJsonPath('data.1.mitra.nama_usaha', 'Mitra Uji')
            ->assertJsonPath('data.1.biaya', 250000)
            ->assertJsonPath('data.1.status_kanonik', StatusPesanan::COMPLETED)
            ->assertJsonPath('data.1.label_status', 'Pesanan Selesai')
            ->assertJsonPath('data.1.tipe_hp', 'Samsung A54');

        // Rejected dan cancelled tidak punya mitra/biaya.
        $response->assertJsonPath('data.4.mitra', null);
        $response->assertJsonPath('data.4.biaya', null);
        $response->assertJsonPath('data.4.status_kanonik', StatusPesanan::REJECTED);
    }

    public function test_filter_berlangsung_hanya_pesanan_belum_selesai(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $mitra = $this->buatMitra();

        $aktif = $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
            now(),
            'SRV-2026-00001'
        );
        $this->buatPesanan(
            $aktif,
            $this->buatPenawaran($aktif, $mitra, StatusPesanan::PENAWARAN_DISETUJUI),
            StatusPesanan::PESANAN_MENUNGGU_PEMBAYARAN
        );

        $selesai = $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
            now(),
            'SRV-2026-00002'
        );
        $this->buatPesanan(
            $selesai,
            $this->buatPenawaran($selesai, $mitra, StatusPesanan::PENAWARAN_DISETUJUI),
            StatusPesanan::PESANAN_SELESAI
        );

        $this->withHeader(
            'Authorization',
            'Bearer '.$pelanggan->createToken('tes')->plainTextToken
        )->getJson('/api/riwayat?filter=berlangsung')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode_pesanan', 'SRV-2026-00001')
            ->assertJsonPath('data.0.status_kanonik', StatusPesanan::WAITING_CASH_PAYMENT);
    }

    public function test_filter_selesai_hanya_pesanan_selesai(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $mitra = $this->buatMitra();

        $selesai = $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_SELESAI_DILELANG,
            now(),
            'SRV-2026-00001'
        );
        $this->buatPesanan(
            $selesai,
            $this->buatPenawaran($selesai, $mitra, StatusPesanan::PENAWARAN_DISETUJUI),
            StatusPesanan::PESANAN_SELESAI
        );

        $this->withHeader(
            'Authorization',
            'Bearer '.$pelanggan->createToken('tes')->plainTextToken
        )->getJson('/api/riwayat?filter=selesai')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status_kanonik', StatusPesanan::COMPLETED);
    }

    public function test_filter_ditolak_hanya_permintaan_tanpa_pesanan_yang_semua_penawarannya_ditolak(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');
        $mitra = $this->buatMitra();

        // Rejected sejati.
        $rejected = $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            now(),
            'SRV-2026-00001'
        );
        $this->buatPenawaran($rejected, $mitra, StatusPesanan::PENAWARAN_DITOLAK);

        // Masih menunggu keputusan -> bukan rejected.
        $menunggu = $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            now(),
            'SRV-2026-00002'
        );
        $this->buatPenawaran($menunggu, $mitra, StatusPesanan::PENAWARAN_MENUNGGU);

        $this->withHeader(
            'Authorization',
            'Bearer '.$pelanggan->createToken('tes')->plainTextToken
        )->getJson('/api/riwayat?filter=ditolak')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode_pesanan', 'SRV-2026-00001');
    }

    public function test_filter_dibatalkan(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');

        $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_DIBATALKAN,
            now(),
            'SRV-2026-00001'
        );
        $this->buatPermintaan(
            $pelanggan,
            StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
            now(),
            'SRV-2026-00002'
        );

        $this->withHeader(
            'Authorization',
            'Bearer '.$pelanggan->createToken('tes')->plainTextToken
        )->getJson('/api/riwayat?filter=dibatalkan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status_kanonik', StatusPesanan::CANCELLED);
    }

    public function test_paginasi_sepuluh_per_halaman(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');

        for ($i = 1; $i <= 11; $i++) {
            $this->buatPermintaan(
                $pelanggan,
                StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN,
                now()->subMinutes(11 - $i),
                'SRV-2026-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT)
            );
        }

        $token = $pelanggan->createToken('tes')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/riwayat')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 11)
            ->assertJsonPath('meta.last_page', 2);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/riwayat?page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_tidak_dikenal_ditolak_422(): void
    {
        $pelanggan = $this->buatPelanggan('pemilik@example.com', '081200000001');

        $this->withHeader(
            'Authorization',
            'Bearer '.$pelanggan->createToken('tes')->plainTextToken
        )->getJson('/api/riwayat?filter=ngawur')
            ->assertStatus(422)
            ->assertJsonValidationErrors('filter');
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/riwayat')->assertUnauthorized();
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

    private function buatMitra(): MitraService
    {
        return MitraService::create([
            'nama_pemilik'      => 'Pemilik Uji',
            'nama_usaha'        => 'Mitra Uji',
            'email'             => 'mitra@example.com',
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '081200000009',
            'status_verifikasi' => 'Disetujui',
        ]);
    }

    private function buatPermintaan(
        Pelanggan $pelanggan,
        string $status,
        \DateTimeInterface $waktu,
        string $kode
    ): PermintaanService {
        return PermintaanService::create([
            'kode_pesanan'        => $kode,
            'id_pelanggan'        => $pelanggan->id_pelanggan,
            'merek_hp'            => 'Samsung',
            'model_hp'            => 'A54',
            'kategori_kerusakan'  => 'Layar',
            'deskripsi_kerusakan' => 'Layar retak',
            'status_permintaan'   => $status,
            'waktu_dibuat'        => $waktu,
        ]);
    }

    private function buatPenawaran(
        PermintaanService $permintaan,
        MitraService $mitra,
        string $status,
        int $harga = 250000
    ): PenawaranMitra {
        return PenawaranMitra::create([
            'id_permintaan'    => $permintaan->id_permintaan,
            'id_mitra'         => $mitra->id_mitra,
            'harga_penawaran'  => $harga,
            'estimasi_hari'    => 2,
            'estimasi_waktu'   => '2 hari',
            'waktu_penawaran'  => now(),
            'status_penawaran' => $status,
        ]);
    }

    private function buatPesanan(
        PermintaanService $permintaan,
        PenawaranMitra $penawaran,
        string $status
    ): PesananAktif {
        return PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $permintaan->id_pelanggan,
            'id_mitra'          => $penawaran->id_mitra,
            'status_pengerjaan' => $status,
            'waktu_dibuat'      => now(),
            'waktu_update_terakhir' => now(),
        ]);
    }
}
