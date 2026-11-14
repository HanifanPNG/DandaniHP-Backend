<?php

namespace App\Support;

// Label dinamis untuk rating bintang.
// Dipakai di resource ulasan dan penawaran agar frontend
// tidak perlu memetakan angka ke teks sendiri.
class LabelRating
{
    // Peta angka bintang ke label Bahasa Indonesia.
    public const LABEL = [
        1 => 'Buruk',
        2 => 'Cukup',
        3 => 'Baik',
        4 => 'Sangat Baik',
        5 => 'Sempurna',
    ];

    // Warna aksen per rating (dipakai untuk badge/chip).
    public const WARNA = [
        1 => '#DC2626', // merah
        2 => '#F59E0B', // amber
        3 => '#0D9488', // teal
        4 => '#2563EB', // biru
        5 => '#16A34A', // hijau
    ];

    /**
     * Ambil label untuk rating tertentu.
     * Fallback ke "Tidak Diketahui" untuk nilai di luar 1-5.
     */
    public static function label(int $bintang): string
    {
        return self::LABEL[$bintang] ?? 'Tidak Diketahui';
    }

    /**
     * Ambil warna aksen untuk rating tertentu.
     */
    public static function warna(int $bintang): string
    {
        return self::WARNA[$bintang] ?? '#64748B';
    }
}