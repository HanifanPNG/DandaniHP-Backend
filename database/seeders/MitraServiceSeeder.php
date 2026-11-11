<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MitraServiceSeeder extends Seeder
{
    public function run(): void
    {
        $kataSandi = Hash::make('mitra12345');

        // Catatan: foto_toko null dan path galeri berupa placeholder karena
        // file gambar belum diunggah pada tahap backend ini.
        $mitra = [
            [
                'id_mitra' => 1,
                'nama_pemilik' => 'Amanah',
                'nama_usaha' => 'Amanah Cell',
                'email' => 'amanah@dandanin.id',
                'nomor_telepon' => '081523456001',
                'alamat_usaha' => 'Jl. Ahmad Yani No. 04, Karangpoh Kulon, Purbalingga',
                'deskripsi_usaha' => 'Melayani perbaikan HP berbagai merek dengan teknisi berpengalaman.',
                'jam_operasional' => '09.00-17.00',
                'jam_buka' => '09:00:00',
                'jam_tutup' => '17:00:00',
                'latitude' => -7.3880000,
                'longitude' => 109.3660000,
                'rating_rata_rata' => 4.40,
                'layanan' => ['Ganti LCD', 'Ganti Baterai', 'Service Mesin'],
            ],
            [
                'id_mitra' => 2,
                'nama_pemilik' => 'Qila',
                'nama_usaha' => 'Qila Cell',
                'email' => 'qila@dandanin.id',
                'nomor_telepon' => '081523456002',
                'alamat_usaha' => 'Jl. Ahmad Yani, Kali Kabong, Purbalingga',
                'deskripsi_usaha' => 'Spesialis penggantian LCD dan baterai original.',
                'jam_operasional' => '08.30-19.00',
                'jam_buka' => '08:30:00',
                'jam_tutup' => '19:00:00',
                'latitude' => -7.3895000,
                'longitude' => 109.3640000,
                'rating_rata_rata' => 4.60,
                'layanan' => ['Ganti LCD', 'Ganti Baterai', 'Software'],
            ],
            [
                'id_mitra' => 3,
                'nama_pemilik' => 'Rizky',
                'nama_usaha' => 'Dandanin Cell Purwokerto',
                'email' => 'dandanin.purwokerto@dandanin.id',
                'nomor_telepon' => '081523456003',
                'alamat_usaha' => 'Jl. Jend. Sudirman No. 12, Purwokerto',
                'deskripsi_usaha' => 'Mitra rekomendasi dengan rating tertinggi di Purwokerto.',
                'jam_operasional' => '09.00-20.00',
                'jam_buka' => '09:00:00',
                'jam_tutup' => '20:00:00',
                'latitude' => -7.4245000,
                'longitude' => 109.2390000,
                'rating_rata_rata' => 4.80,
                'layanan' => ['Ganti LCD', 'Ganti Baterai', 'Kamera', 'Software'],
            ],
            [
                'id_mitra' => 4,
                'nama_pemilik' => 'Sari',
                'nama_usaha' => 'Terang HP Service',
                'email' => 'terang@dandanin.id',
                'nomor_telepon' => '081523456004',
                'alamat_usaha' => 'Jl. Pahlawan No. 8, Purwokerto',
                'deskripsi_usaha' => 'Perbaikan cepat dengan estimasi pengerjaan yang jelas.',
                'jam_operasional' => '09.00-18.00',
                'jam_buka' => '09:00:00',
                'jam_tutup' => '18:00:00',
                'latitude' => -7.4260000,
                'longitude' => 109.2410000,
                'rating_rata_rata' => 4.70,
                'layanan' => ['Ganti LCD', 'Charging Port', 'Audio'],
            ],
            [
                'id_mitra' => 5,
                'nama_pemilik' => 'Dwi',
                'nama_usaha' => 'Sinar Cell',
                'email' => 'sinar@dandanin.id',
                'nomor_telepon' => '081523456005',
                'alamat_usaha' => 'Jl. Raya Banyumas No. 21, Banyumas',
                'deskripsi_usaha' => 'Melayani service baterai dan software untuk semua merek.',
                'jam_operasional' => '08.00-17.00',
                'jam_buka' => '08:00:00',
                'jam_tutup' => '17:00:00',
                'latitude' => -7.5140000,
                'longitude' => 109.2940000,
                'rating_rata_rata' => 4.30,
                'layanan' => ['Ganti Baterai', 'Charging Port', 'Software'],
            ],
            [
                'id_mitra' => 6,
                'nama_pemilik' => 'Fajar',
                'nama_usaha' => 'Mitra Digital Purbalingga',
                'email' => 'digital@dandanin.id',
                'nomor_telepon' => '081523456006',
                'alamat_usaha' => 'Jl. Mayjen Sungkono No. 5, Purbalingga',
                'deskripsi_usaha' => 'Service kamera, audio, dan software dengan garansi 30 hari.',
                'jam_operasional' => '09.00-21.00',
                'jam_buka' => '09:00:00',
                'jam_tutup' => '21:00:00',
                'latitude' => -7.3905000,
                'longitude' => 109.3700000,
                'rating_rata_rata' => 4.50,
                'layanan' => ['Ganti LCD', 'Kamera', 'Software', 'Audio'],
            ],
        ];

        foreach ($mitra as $item) {
            $layanan = $item['layanan'];
            unset($item['layanan']);

            DB::table('mitra_service')->insert($item + [
                'kata_sandi' => $kataSandi,
                'foto_toko' => null,
                'status_verifikasi' => 'Disetujui',
            ]);

            foreach ($layanan as $namaLayanan) {
                DB::table('layanan_mitra')->insert([
                    'id_mitra' => $item['id_mitra'],
                    'nama_layanan' => $namaLayanan,
                ]);
            }

            for ($i = 1; $i <= 2; $i++) {
                DB::table('galeri_mitra')->insert([
                    'id_mitra' => $item['id_mitra'],
                    'path' => "mitra/{$item['id_mitra']}/galeri-{$i}.jpg",
                ]);
            }
        }
    }
}
