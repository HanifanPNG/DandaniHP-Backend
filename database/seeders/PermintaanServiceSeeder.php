<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermintaanServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Skenario 1: permintaan dengan dua penawaran menunggu keputusan.
        // Skenario 2: permintaan baru tanpa penawaran.
        // Skenario 3: permintaan yang sudah selesai, dibayar tunai, dan diulas.
        DB::table('permintaan_service')->insert([
            [
                'id_permintaan' => 1,
                'kode_pesanan' => 'SRV-2026-00125',
                'id_pelanggan' => 1,
                'merek_hp' => 'Samsung',
                'model_hp' => 'Galaxy A54',
                'kategori_kerusakan' => 'Layar HP',
                'deskripsi_kerusakan' => 'LCD pecah dan layar tidak merespons.',
                'lama_kerusakan' => '2 hari',
                'kondisi_hp' => 'Menyala normal, sentuhan tidak berfungsi',
                'catatan_tambahan' => 'HP pernah terjatuh dari meja.',
                'waktu_dibuat' => '2026-11-09 08:46:02',
                'status_permintaan' => 'Mencari Penawaran',
            ],
            [
                'id_permintaan' => 2,
                'kode_pesanan' => 'SRV-2026-00126',
                'id_pelanggan' => 1,
                'merek_hp' => 'Xiaomi',
                'model_hp' => 'Redmi Note 12',
                'kategori_kerusakan' => 'Baterai',
                'deskripsi_kerusakan' => 'Baterai cepat habis meski baru diisi penuh.',
                'lama_kerusakan' => '1 minggu',
                'kondisi_hp' => 'Menyala normal',
                'catatan_tambahan' => null,
                'waktu_dibuat' => '2026-11-10 08:00:00',
                'status_permintaan' => 'Mencari Penawaran',
            ],
            [
                'id_permintaan' => 3,
                'kode_pesanan' => 'SRV-2026-00098',
                'id_pelanggan' => 1,
                'merek_hp' => 'Apple',
                'model_hp' => 'iPhone 11',
                'kategori_kerusakan' => 'Kamera',
                'deskripsi_kerusakan' => 'Kamera belakang tidak bisa fokus.',
                'lama_kerusakan' => '3 hari',
                'kondisi_hp' => 'Menyala normal',
                'catatan_tambahan' => null,
                'waktu_dibuat' => '2026-11-01 09:00:00',
                'status_permintaan' => 'Selesai Dilelang',
            ],
        ]);

        DB::table('lampiran_permintaan')->insert([
            [
                'id_lampiran' => 1,
                'id_permintaan' => 1,
                'path' => 'lampiran/1/kerusakan-1.jpg',
                'waktu_unggah' => '2026-11-09 08:46:02',
            ],
            [
                'id_lampiran' => 2,
                'id_permintaan' => 1,
                'path' => 'lampiran/1/kerusakan-2.jpg',
                'waktu_unggah' => '2026-11-09 08:46:02',
            ],
        ]);

        DB::table('penawaran_mitra')->insert([
            [
                'id_penawaran' => 1,
                'id_permintaan' => 1,
                'id_mitra' => 1,
                'harga_penawaran' => 350000,
                'estimasi_hari' => 3,
                'estimasi_waktu' => '2-3 hari kerja',
                'catatan_pemeriksaan' => 'LCD perlu indent terlebih dahulu, kurang lebih 2 hari.',
                'alasan_penolakan' => null,
                'waktu_penawaran' => '2026-11-09 09:00:00',
                'status_penawaran' => 'Menunggu Keputusan',
            ],
            [
                'id_penawaran' => 2,
                'id_permintaan' => 1,
                'id_mitra' => 2,
                'harga_penawaran' => 325000,
                'estimasi_hari' => 2,
                'estimasi_waktu' => '2 hari',
                'catatan_pemeriksaan' => 'LCD Grade B+',
                'alasan_penolakan' => null,
                'waktu_penawaran' => '2026-11-09 09:10:00',
                'status_penawaran' => 'Menunggu Keputusan',
            ],
            [
                'id_penawaran' => 3,
                'id_permintaan' => 3,
                'id_mitra' => 3,
                'harga_penawaran' => 450000,
                'estimasi_hari' => 2,
                'estimasi_waktu' => '2 hari',
                'catatan_pemeriksaan' => 'Modul kamera diganti dengan unit baru.',
                'alasan_penolakan' => null,
                'waktu_penawaran' => '2026-11-01 10:00:00',
                'status_penawaran' => 'Disetujui',
            ],
            [
                'id_penawaran' => 4,
                'id_permintaan' => 3,
                'id_mitra' => 4,
                'harga_penawaran' => 475000,
                'estimasi_hari' => 3,
                'estimasi_waktu' => '3 hari',
                'catatan_pemeriksaan' => 'Kamera akan dipasang setelah unit datang.',
                'alasan_penolakan' => null,
                'waktu_penawaran' => '2026-11-01 10:15:00',
                'status_penawaran' => 'Kedaluwarsa',
            ],
            [
                'id_penawaran' => 5,
                'id_permintaan' => 3,
                'id_mitra' => 1,
                'harga_penawaran' => 500000,
                'estimasi_hari' => 2,
                'estimasi_waktu' => '2 hari',
                'catatan_pemeriksaan' => 'Kamera original.',
                'alasan_penolakan' => null,
                'waktu_penawaran' => '2026-11-01 11:00:00',
                'status_penawaran' => 'Kedaluwarsa',
            ],
        ]);

        DB::table('pesanan_aktif')->insert([
            'id_pesanan' => 1,
            'id_penawaran' => 3,
            'id_permintaan' => 3,
            'id_pelanggan' => 1,
            'id_mitra' => 3,
            'status_pengerjaan' => 'Pesanan Selesai',
            'catatan_perkembangan' => 'Pesanan telah selesai dan sudah diambil pelanggan.',
            'waktu_dibuat' => '2026-11-01 10:30:00',
            'waktu_update_terakhir' => '2026-11-02 10:05:00',
        ]);

        DB::table('pembayaran_tunai')->insert([
            'id_pembayaran' => 1,
            'id_pesanan' => 1,
            'jumlah_pembayaran' => 450000,
            'waktu_dibuat' => '2026-11-02 09:05:00',
            'waktu_konfirmasi_mitra' => '2026-11-02 10:00:00',
            'status_pembayaran' => 'Diterima',
        ]);

        DB::table('riwayat_status_pesanan')->insert([
            [
                'id_riwayat' => 1,
                'id_pesanan' => 1,
                'status' => 'Masuk Antrean',
                'catatan' => 'Penawaran disetujui pelanggan.',
                'aktor' => 'Sistem',
                'waktu' => '2026-11-01 10:30:00',
            ],
            [
                'id_riwayat' => 2,
                'id_pesanan' => 1,
                'status' => 'Sedang Diproses',
                'catatan' => 'Perangkat mulai diperiksa teknisi.',
                'aktor' => 'Mitra',
                'waktu' => '2026-11-01 11:00:00',
            ],
            [
                'id_riwayat' => 3,
                'id_pesanan' => 1,
                'status' => 'Service Selesai',
                'catatan' => 'Modul kamera sudah diganti dan diuji.',
                'aktor' => 'Mitra',
                'waktu' => '2026-11-02 09:00:00',
            ],
            [
                'id_riwayat' => 4,
                'id_pesanan' => 1,
                'status' => 'Menunggu Pembayaran Tunai',
                'catatan' => 'HP siap diambil di lokasi mitra.',
                'aktor' => 'Mitra',
                'waktu' => '2026-11-02 09:05:00',
            ],
            [
                'id_riwayat' => 5,
                'id_pesanan' => 1,
                'status' => 'Pembayaran Diterima',
                'catatan' => 'Pembayaran tunai diterima mitra.',
                'aktor' => 'Mitra',
                'waktu' => '2026-11-02 10:00:00',
            ],
            [
                'id_riwayat' => 6,
                'id_pesanan' => 1,
                'status' => 'Pesanan Selesai',
                'catatan' => 'Pesanan ditutup.',
                'aktor' => 'Sistem',
                'waktu' => '2026-11-02 10:05:00',
            ],
        ]);

        DB::table('ulasan')->insert([
            'id_ulasan' => 1,
            'id_pesanan' => 1,
            'id_pelanggan' => 1,
            'id_mitra' => 3,
            'rating_bintang' => 5,
            'komentar_ulasan' => 'Kamera sudah normal dan pelayanannya cepat.',
            'waktu_ulasan' => '2026-11-02 10:10:00',
        ]);

        // Mitra 3 memiliki satu ulasan bintang 5; samakan agregatnya.
        DB::table('mitra_service')
            ->where('id_mitra', 3)
            ->update(['rating_rata_rata' => 5.00]);
    }
}
