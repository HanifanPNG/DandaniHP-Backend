<?php

namespace Tests\Unit;

use App\Models\Pelanggan;
use App\Models\PermintaanService;
use App\Services\KodePesananGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KodePesananGeneratorTest extends TestCase
{
    // Memakai database asli (SQLite in-memory) karena generator membaca tabel.
    use RefreshDatabase;

    public function test_dua_permintaan_berurutan_menghasilkan_kode_berbeda(): void
    {
        $pelanggan = $this->buatPelanggan();
        $generator = new KodePesananGenerator();
        $tahun = now()->format('Y');

        // Permintaan pertama.
        $kodePertama = $generator->buat();
        $this->simpanPermintaan($pelanggan, $kodePertama);

        // Permintaan kedua harus melanjutkan nomor, bukan mengulang.
        $kodeKedua = $generator->buat();

        $this->assertSame("SRV-{$tahun}-00001", $kodePertama);
        $this->assertSame("SRV-{$tahun}-00002", $kodeKedua);
        $this->assertNotSame($kodePertama, $kodeKedua);
    }

    private function buatPelanggan(): Pelanggan
    {
        return Pelanggan::create([
            'nama_lengkap'      => 'Pelanggan Uji',
            'email'             => 'uji@example.com',
            'nomor_telepon'     => '081200000001',
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => 'Disetujui',
        ]);
    }

    private function simpanPermintaan(Pelanggan $pelanggan, string $kode): void
    {
        PermintaanService::create([
            'kode_pesanan'        => $kode,
            'id_pelanggan'        => $pelanggan->id_pelanggan,
            'merek_hp'            => 'Samsung',
            'model_hp'            => 'A54',
            'kategori_kerusakan'  => 'Layar',
            'deskripsi_kerusakan' => 'Layar retak',
            'status_permintaan'   => 'Mencari Penawaran',
            'waktu_dibuat'        => now(),
        ]);
    }
}
