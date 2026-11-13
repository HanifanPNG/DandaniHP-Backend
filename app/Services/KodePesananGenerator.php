<?php

namespace App\Services;

use App\Models\PermintaanService;
use RuntimeException;

// Membuat kode pesanan unik berformat SRV-{YYYY}-{5 digit urut}.
// Contoh: SRV-2026-00125.
//
// Kenapa dipisah ke Service?
// - Dipakai di lebih dari satu tempat nanti (permintaan, pesanan, dsb).
// - Mudah diuji sendiri tanpa harus memanggil HTTP.
class KodePesananGenerator
{
    // Batas percobaan supaya tidak looping tanpa akhir kalau data aneh.
    private const MAKS_PERCOBAAN = 3;

    public function buat(): string
    {
        // Tahun mengikuti zona waktu aplikasi (Asia/Jakarta, BR-12).
        $tahun = now()->format('Y');

        for ($percobaan = 1; $percobaan <= self::MAKS_PERCOBAAN; $percobaan++) {
            $kode = sprintf('SRV-%s-%05d', $tahun, $this->nomorUrutBerikutnya($tahun));

            // exists() mencegah bentrok dengan data lama atau request yang
            // berjalan hampir bersamaan. Kalau bentrok, ulangi hitungan.
            if (! PermintaanService::where('kode_pesanan', $kode)->exists()) {
                return $kode;
            }
        }

        // Kalau sampai di sini, ada yang tidak wajar (mis. data penuh).
        // Lebih baik gagal jelas daripada menyimpan kode duplikat.
        throw new RuntimeException(
            'Gagal membuat kode pesanan unik setelah '.self::MAKS_PERCOBAAN.' percobaan.'
        );
    }

    // Mengambil nomor urut terakhir tahun ini lalu menambah 1.
    private function nomorUrutBerikutnya(string $tahun): int
    {
        $terakhir = PermintaanService::where('kode_pesanan', 'like', "SRV-{$tahun}-%")
            // Kode selalu 5 digit, jadi urutan teks = urutan angka.
            ->orderByDesc('kode_pesanan')
            ->value('kode_pesanan');

        if ($terakhir === null) {
            return 1;
        }

        // Lima karakter terakhir adalah nomor urut: "00125" -> 125.
        return ((int) substr($terakhir, -5)) + 1;
    }
}
