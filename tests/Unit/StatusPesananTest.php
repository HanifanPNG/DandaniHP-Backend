<?php

namespace Tests\Unit;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Models\PesananAktif;
use App\Support\StatusPesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StatusPesananTest extends TestCase
{
    // Helper membaca relasi (penawaran, pesanan), jadi butuh database asli.
    use RefreshDatabase;

    public function test_tanpa_penawaran_menghasilkan_waiting_offer(): void
    {
        $permintaan = $this->buatPermintaan();

        $this->assertSame(StatusPesanan::WAITING_OFFER, StatusPesanan::kanonik($permintaan));
    }

    public function test_ada_penawaran_menunggu_keputusan(): void
    {
        $permintaan = $this->buatPermintaan();
        $this->buatPenawaran($permintaan, StatusPesanan::PENAWARAN_MENUNGGU);

        $this->assertSame(
            StatusPesanan::WAITING_CUSTOMER_APPROVAL,
            StatusPesanan::kanonik($permintaan)
        );
    }

    public function test_semua_penawaran_ditolak_menghasilkan_rejected(): void
    {
        $permintaan = $this->buatPermintaan();
        // Dua mitra berbeda, keduanya ditolak pelanggan.
        $this->buatPenawaran($permintaan, StatusPesanan::PENAWARAN_DITOLAK);
        $this->buatPenawaran($permintaan, StatusPesanan::PENAWARAN_DITOLAK);

        $this->assertSame(StatusPesanan::REJECTED, StatusPesanan::kanonik($permintaan));
    }

    // Dibatalkan harus menang atas semua kondisi lain, termasuk saat
    // sebenarnya sudah ada pesanan.
    public function test_status_dibatalkan_menghasilkan_cancelled(): void
    {
        $permintaan = $this->buatPermintaan(StatusPesanan::PERMINTAAN_DIBATALKAN);

        $this->assertSame(StatusPesanan::CANCELLED, StatusPesanan::kanonik($permintaan));
    }

    /**
     * Satu kasus untuk setiap status pengerjaan pesanan.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function statusPesananProvider(): array
    {
        return [
            'masuk antrean'            => [StatusPesanan::PESANAN_MASUK_ANTREAN, StatusPesanan::QUEUED],
            'sedang diproses'          => [StatusPesanan::PESANAN_SEDANG_DIPROSES, StatusPesanan::IN_PROGRESS],
            'service selesai'          => [StatusPesanan::PESANAN_SERVICE_SELESAI, StatusPesanan::SERVICE_COMPLETED],
            'menunggu pembayaran'      => [StatusPesanan::PESANAN_MENUNGGU_PEMBAYARAN, StatusPesanan::WAITING_CASH_PAYMENT],
            'pembayaran diterima'      => [StatusPesanan::PESANAN_PEMBAYARAN_DITERIMA, StatusPesanan::PAYMENT_RECEIVED],
            'pesanan selesai'          => [StatusPesanan::PESANAN_SELESAI, StatusPesanan::COMPLETED],
        ];
    }

    #[DataProvider('statusPesananProvider')]
    public function test_status_pesanan_diterjemahkan_ke_kunci_kanonik(
        string $statusPengerjaan,
        string $kanonik
    ): void {
        $permintaan = $this->buatPermintaan();

        // Pesanan hanya ada kalau salah satu penawaran disetujui.
        $penawaran = $this->buatPenawaran($permintaan, StatusPesanan::PENAWARAN_DISETUJUI);

        PesananAktif::create([
            'id_penawaran'    => $penawaran->id_penawaran,
            'id_permintaan'   => $permintaan->id_permintaan,
            'id_pelanggan'    => $permintaan->id_pelanggan,
            'id_mitra'        => $penawaran->id_mitra,
            'status_pengerjaan' => $statusPengerjaan,
            'waktu_dibuat'    => now(),
        ]);

        // Relasi perlu dimuat ulang karena model di memori belum tahu
        // ada pesanan yang baru dibuat.
        $permintaan->load('pesanan');

        $this->assertSame($kanonik, StatusPesanan::kanonik($permintaan));
    }

    public function test_label_tersedia_dalam_bahasa_indonesia(): void
    {
        $this->assertSame(
            'Menunggu Persetujuan Anda',
            StatusPesanan::label(StatusPesanan::WAITING_CUSTOMER_APPROVAL)
        );
        $this->assertSame('Dibatalkan', StatusPesanan::label(StatusPesanan::CANCELLED));
    }

    private function buatPermintaan(
        string $status = StatusPesanan::PERMINTAAN_MENCARI_PENAWARAN
    ): PermintaanService {
        $pelanggan = Pelanggan::create([
            'nama_lengkap'      => 'Pelanggan Uji',
            'email'             => 'uji@example.com',
            'nomor_telepon'     => '081200000001',
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);

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

    private function buatPenawaran(
        PermintaanService $permintaan,
        string $status
    ): PenawaranMitra {
        // Email harus unik per mitra, jadi dihitung dari jumlah data.
        $nomor = MitraService::count() + 1;

        $mitra = MitraService::create([
            'nama_pemilik'  => 'Pemilik '.$nomor,
            'nama_usaha'    => 'Mitra '.$nomor,
            'email'         => "mitra{$nomor}@example.com",
            'kata_sandi'    => 'password123',
            'nomor_telepon' => '0812000000'.$nomor,
            'status_verifikasi' => 'Disetujui',
        ]);

        return PenawaranMitra::create([
            'id_permintaan'   => $permintaan->id_permintaan,
            'id_mitra'        => $mitra->id_mitra,
            'harga_penawaran' => 250000,
            'estimasi_hari'   => 2,
            'estimasi_waktu'  => '2 hari',
            'waktu_penawaran' => now(),
            'status_penawaran' => $status,
        ]);
    }
}
