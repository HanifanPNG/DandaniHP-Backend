<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotifikasiSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('notifikasi')->insert([
            [
                'id_notifikasi' => 1,
                'id_pelanggan' => 1,
                'tipe' => 'Penawaran',
                'judul' => 'Penawaran baru',
                'pesan' => 'Amanah Cell mengirim penawaran Rp350.000 untuk SRV-2026-00125.',
                'id_pesanan' => null,
                'dibaca' => false,
                'waktu_dibuat' => '2026-11-09 09:00:00',
            ],
            [
                'id_notifikasi' => 2,
                'id_pelanggan' => 1,
                'tipe' => 'Progres',
                'judul' => 'Service sedang diproses',
                'pesan' => 'Dandanin Cell Purwokerto mulai mengerjakan perangkat Anda.',
                'id_pesanan' => 1,
                'dibaca' => true,
                'waktu_dibuat' => '2026-11-01 11:00:00',
            ],
            [
                'id_notifikasi' => 3,
                'id_pelanggan' => 1,
                'tipe' => 'Selesai',
                'judul' => 'Pesanan selesai',
                'pesan' => 'Pesanan SRV-2026-00098 telah selesai. Terima kasih.',
                'id_pesanan' => 1,
                'dibaca' => true,
                'waktu_dibuat' => '2026-11-02 10:05:00',
            ],
            [
                'id_notifikasi' => 4,
                'id_pelanggan' => 1,
                'tipe' => 'Ulasan',
                'judul' => 'Terima kasih atas ulasan Anda',
                'pesan' => 'Ulasan Anda untuk Dandanin Cell Purwokerto sudah tersimpan.',
                'id_pesanan' => 1,
                'dibaca' => false,
                'waktu_dibuat' => '2026-11-02 10:11:00',
            ],
        ]);
    }
}
