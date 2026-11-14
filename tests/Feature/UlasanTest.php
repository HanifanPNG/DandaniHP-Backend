<?php

namespace Tests\Feature;

use App\Models\MitraService;
use App\Models\Pelanggan;
use App\Models\PenawaranMitra;
use App\Models\PermintaanService;
use App\Models\PesananAktif;
use App\Models\PembayaranTunai;
use App\Models\Ulasan;
use App\Support\StatusPesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UlasanTest extends TestCase
{
    use RefreshDatabase;

    public function test_pelanggan_bisa_kirim_ulasan_kepesanan_selesai(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $mitra = $this->buatMitra('Disetujui');

        $permintaan = $this->buatPermintaan($pelanggan);
        $penawaran = $this->buatPenawaran($permintaan, $mitra);

        $pesanan = PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $pelanggan->id_pelanggan,
            'id_mitra'          => $mitra->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_SELESAI,
            'waktu_dibuat'      => now(),
            'waktu_update_terakhir' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/pesanan/'.$pesanan->id_pesanan.'/ulasan', [
                'rating_bintang'   => 5,
                'komentar_ulasan'  => 'Service sangat memuaskan, cepat!',
            ])
            ->assertCreated()
            ->assertJsonPath('data.rating_bintang', 5)
            ->assertJsonPath('data.label_rating', 'Sempurna')
            ->assertJsonPath('data.komentar_ulasan', 'Service sangat memuaskan, cepat!')
            ->assertJsonPath('data.mitra.nama_usaha', 'Mitra Uji');

        $this->assertDatabaseHas('ulasan', [
            'id_pesanan'  => $pesanan->id_pesanan,
            'id_pelanggan'=> $pelanggan->id_pelanggan,
            'id_mitra'    => $mitra->id_mitra,
            'rating_bintang' => 5,
        ]);

        // Rating mitra otomatis terupdate.
        $mitra->refresh();
        $this->assertEquals(5.0, (float) $mitra->rating_rata_rata);
    }

    public function test_ulasan_kepesanan_belum_selesai_ditolak_422(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $mitra = $this->buatMitra('Disetujui');

        $permintaan = $this->buatPermintaan($pelanggan);
        $penawaran = $this->buatPenawaran($permintaan, $mitra);

        $pesanan = PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $pelanggan->id_pelanggan,
            'id_mitra'          => $mitra->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_MASUK_ANTREAN,
            'waktu_dibuat'      => now(),
            'waktu_update_terakhir' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/pesanan/'.$pesanan->id_pesanan.'/ulasan', [
                'rating_bintang' => 5,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Pesanan ini belum selesai, tidak dapat memberi ulasan.');

        $this->assertDatabaseMissing('ulasan', [
            'id_pesanan' => $pesanan->id_pesanan,
        ]);
    }

    public function test_ulasan_ganda_ditolak_422(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $mitra = $this->buatMitra('Disetujui');

        $permintaan = $this->buatPermintaan($pelanggan);
        $penawaran = $this->buatPenawaran($permintaan, $mitra);

        $pesanan = PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $pelanggan->id_pelanggan,
            'id_mitra'          => $mitra->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_SELESAI,
            'waktu_dibuat'      => now(),
            'waktu_update_terakhir' => now(),
        ]);

        // Kirim ulasan pertama.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/pesanan/'.$pesanan->id_pesanan.'/ulasan', [
                'rating_bintang' => 5,
            ])
            ->assertCreated();

        // Kirim ulasan lagi → 422.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/pesanan/'.$pesanan->id_pesanan.'/ulasan', [
                'rating_bintang' => 4,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Anda sudah memberi ulasan untuk pesanan ini.');

        // Rating mitra tetap 5 (ulasan kedua tidak tersimpan).
        $mitra->refresh();
        $this->assertEquals(5.0, (float) $mitra->rating_rata_rata);
    }

    public function test_rating_lebih_dari_5_ditolak_422(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $mitra = $this->buatMitra('Disetujui');

        $permintaan = $this->buatPermintaan($pelanggan);
        $penawaran = $this->buatPenawaran($permintaan, $mitra);

        $pesanan = PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $pelanggan->id_pelanggan,
            'id_mitra'          => $mitra->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_SELESAI,
            'waktu_dibuat'      => now(),
            'waktu_update_terakhir' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/pesanan/'.$pesanan->id_pesanan.'/ulasan', [
                'rating_bintang' => 10,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rating_bintang');
    }

    public function test_ulasan_rating_rata_rata_dikelompok(): void
    {
        // Ulasan 5 + 4 = 9, rata-rata 4.5.
        $pelanggan1 = $this->buatPelanggan('Disetujui');
        $pelanggan2 = $this->buatPelanggan('Disetujui');

        $mitra = $this->buatMitra('Disetujui');
        $token1 = $pelanggan1->createToken('tes')->plainTextToken;
        $token2 = $pelanggan2->createToken('tes')->plainTextToken;

        $permintaan1 = $this->buatPermintaan($pelanggan1);
        $penawaran1 = $this->buatPenawaran($permintaan1, $mitra);
        $pesanan1 = PesananAktif::create([
            'id_penawaran' => $penawaran1->id_penawaran,
            'id_permintaan' => $permintaan1->id_permintaan,
            'id_pelanggan' => $pelanggan1->id_pelanggan,
            'id_mitra' => $mitra->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_SELESAI,
            'waktu_dibuat' => now(),
        ]);

        $permintaan2 = $this->buatPermintaan($pelanggan2);
        $penawaran2 = $this->buatPenawaran($permintaan2, $mitra);
        $pesanan2 = PesananAktif::create([
            'id_penawaran' => $penawaran2->id_penawaran,
            'id_permintaan' => $permintaan2->id_permintaan,
            'id_pelanggan' => $pelanggan2->id_pelanggan,
            'id_mitra' => $mitra->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_SELESAI,
            'waktu_dibuat' => now(),
        ]);

        // Pelanggan 1 beri rating 5.
        $this->withHeader('Authorization', 'Bearer '.$token1)
            ->postJson('/api/pesanan/'.$pesanan1->id_pesanan.'/ulasan', ['rating_bintang' => 5])
            ->assertCreated();

        // Pelanggan 2 beri rating 4.
        $this->withHeader('Authorization', 'Bearer '.$token2)
            ->postJson('/api/pesanan/'.$pesanan2->id_pesanan.'/ulasan', ['rating_bintang' => 4])
            ->assertCreated();

        // Rating mitra menjadi 4.5.
        $mitra->refresh();
        $this->assertEquals(4.5, (float) $mitra->rating_rata_rata);
    }

    public function test_rating_setelah_ulasan_dihapus(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $mitra = $this->buatMitra('Disetujui');

        // Buat ulasan secara langsung (lewat model bukan API).
        Ulasan::create([
            'id_pesanan' => 1,
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_mitra' => $mitra->id_mitra,
            'rating_bintang' => 5,
            'waktu_ulasan' => now(),
        ]);

        // Rating harusnya 5.
        $mitra->refresh();
        $this->assertEquals(5.0, (float) $mitra->rating_rata_rata);

        // Hapus ulasan.
        Ulasan::first()->delete();

        // Rating harusnya 0 (tidak ada ulasan).
        $mitra->refresh();
        $this->assertEquals(0.0, (float) $mitra->rating_rata_rata);
    }

    public function test_ulasan_dengan_foto(): void
    {
        $pelanggan = $this->buatPelanggan('Disetujui');
        $token = $pelanggan->createToken('tes')->plainTextToken;

        $mitra = $this->buatMitra('Disetujui');
        $permintaan = $this->buatPermintaan($pelanggan);
        $penawaran = $this->buatPenawaran($permintaan, $mitra);

        $pesanan = PesananAktif::create([
            'id_penawaran'      => $penawaran->id_penawaran,
            'id_permintaan'     => $permintaan->id_permintaan,
            'id_pelanggan'      => $pelanggan->id_pelanggan,
            'id_mitra'          => $mitra->id_mitra,
            'status_pengerjaan' => StatusPesanan::PESANAN_SELESAI,
            'waktu_dibuat'      => now(),
            'waktu_update_terakhir' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/pesanan/'.$pesanan->id_pesanan.'/ulasan', [
                'rating_bintang'  => 4,
                'komentar_ulasan' => 'Hasil rapi, tapi agak lama.',
                'foto_ulasan'     => UploadedFile::fake()->image('hasil.jpg', 400, 400),
            ])
            ->assertCreated()
            ->assertJsonPath('data.rating_bintang', 4)
            ->assertJsonPath('data.label_rating', 'Sangat Baik')
            ->assertJsonPath('data.warna_rating', '#2563EB');

        // Foto tersimpan di storage.
        $this->assertNotNull(Ulasan::first()->foto_ulasan);
    }

    public function test_label_rating_sesuai_bintang(): void
    {
        $this->assertEquals('Buruk', \App\Support\LabelRating::label(1));
        $this->assertEquals('Cukup', \App\Support\LabelRating::label(2));
        $this->assertEquals('Baik', \App\Support\LabelRating::label(3));
        $this->assertEquals('Sangat Baik', \App\Support\LabelRating::label(4));
        $this->assertEquals('Sempurna', \App\Support\LabelRating::label(5));
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

    private function buatPermintaan(Pelanggan $pelanggan): PermintaanService
    {
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
