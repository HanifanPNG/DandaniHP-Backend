<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Models\PesananAktif;
use App\Models\Notifikasi;
use App\Models\PembayaranTunai;
use App\Models\Ulasan;
use App\Support\StatusPesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pelanggan_melihat_daftar_notifikasi_terkelompok(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $mitra = $this->buatMitra('Disetujui');

        // Buat notifikasi untuk hari ini
        Notifikasi::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tipe'         => 'Penawaran',
            'judul'        => 'Penawaran baru',
            'pesan'        => 'Ada penawaran baru.',
            'dibaca'       => false,
            'waktu_dibuat' => now(),
        ]);

        // Buat notifikasi untuk kemarin
        Notifikasi::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tipe'         => 'Progres',
            'judul'        => 'Progres',
            'pesan'        => 'Status diperbarui.',
            'dibaca'       => true,
            'waktu_dibuat' => now()->subDay(),
        ]);

        // Buat notifikasi untuk sebelumnya
        Notifikasi::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tipe'         => 'Selesai',
            'judul'        => 'Selesai',
            'pesan'        => 'Pesanan selesai.',
            'dibaca'       => false,
            'waktu_dibuat' => now()->subDays(3),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/notifikasi')
            ->assertOk()
            ->assertJsonCount(1, 'data.hari_ini')
            ->assertJsonCount(1, 'data.kemarin')
            ->assertJsonCount(1, 'data.sebelumnya')
            ->assertJsonPath('data.jumlah_belum_dibaca', 2);
    }

    public function test_pelanggan_tandai_satu_notifikasi_dibaca(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $notifikasi = Notifikasi::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tipe'         => 'Penawaran',
            'judul'        => 'Penawaran baru',
            'pesan'        => 'Ada penawaran baru.',
            'dibaca'       => false,
            'waktu_dibuat' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/notifikasi/'.$notifikasi->id_notifikasi.'/baca')
            ->assertOk()
            ->assertJsonPath('data.dibaca', true);

        $this->assertDatabaseHas('notifikasi', [
            'id_notifikasi' => $notifikasi->id_notifikasi,
            'dibaca'        => true,
        ]);
    }

    public function test_pelanggan_tidak_bisa_tandai_notifikasi_orang_lain(): void
    {
        $pelanggan1 = $this->buatPelanggan('Disetujui');
        $pelanggan2 = $this->buatPelanggan('Disetujui');
        $token = $pelanggan2->createToken('tes')->plainTextToken;

        $notifikasi = Notifikasi::create([
            'id_pelanggan' => $pelanggan1->id_pelanggan,
            'tipe'         => 'Penawaran',
            'judul'        => 'Penawaran baru',
            'pesan'        => 'Ada penawaran baru.',
            'dibaca'       => false,
            'waktu_dibuat' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/notifikasi/'.$notifikasi->id_notifikasi.'/baca')
            ->assertNotFound();
    }

    public function test_pelanggan_tandai_semua_notifikasi_dibaca(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        Notifikasi::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tipe'         => 'Penawaran',
            'judul'        => 'Penawaran 1',
            'pesan'        => 'Pesan 1',
            'dibaca'       => false,
            'waktu_dibuat' => now(),
        ]);

        Notifikasi::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tipe'         => 'Progres',
            'judul'        => 'Progres 1',
            'pesan'        => 'Pesan 2',
            'dibaca'       => false,
            'waktu_dibuat' => now()->subDay(),
        ]);

        Notifikasi::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tipe'         => 'Selesai',
            'judul'        => 'Selesai 1',
            'pesan'        => 'Pesan 3',
            'dibaca'       => true,
            'waktu_dibuat' => now()->subDays(3),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/notifikasi/baca-semua')
            ->assertOk()
            ->assertJsonPath('data.jumlah_ditandai', 2);

        $this->assertDatabaseCount('notifikasi', 3);
        $this->assertDatabaseHas('notifikasi', [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'dibaca'       => true,
        ]);
    }

    public function test_pelanggan_tanpa_notifikasi_belum_dibaca(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        Notifikasi::create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tipe'         => 'Penawaran',
            'judul'        => 'Penawaran 1',
            'pesan'        => 'Pesan 1',
            'dibaca'       => true,
            'waktu_dibuat' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/notifikasi')
            ->assertOk()
            ->assertJsonPath('data.jumlah_belum_dibaca', 0)
            ->assertJsonCount(1, 'data.hari_ini');
    }

    public function test_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/notifikasi')->assertUnauthorized();
        $this->postJson('/api/notifikasi/1/baca')->assertUnauthorized();
        $this->postJson('/api/notifikasi/baca-semua')->assertUnauthorized();
    }

    private function buatPelanggan(string $status): Pelanggan
    {
        $suffix = rand(1000, 9999);
        return Pelanggan::create([
            'nama_lengkap'      => 'Budi Santoso',
            'email'             => 'budi'.$suffix.'@example.com',
            'nomor_telepon'     => '0812'.($suffix),
            'alamat'            => 'Jl. Uji No. 1',
            'kata_sandi'        => 'password123',
            'status_verifikasi' => $status,
        ]);
    }

    private function buatMitra(string $status): MitraService
    {
        $suffix = rand(1000, 9999);
        return MitraService::create([
            'nama_pemilik'      => 'Pemilik Mitra',
            'nama_usaha'        => 'Mitra Uji',
            'email'             => 'mitra'.$suffix.'@example.com',
            'kata_sandi'        => 'password123',
            'nomor_telepon'     => '0812'.($suffix % 100),
            'status_verifikasi' => $status,
        ]);
    }
}