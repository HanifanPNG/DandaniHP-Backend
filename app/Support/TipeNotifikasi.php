<?php

namespace App\Support;

// Konstanta tipe notifikasi sesuai PRD.
// Dipakai untuk validasi input dan pencarian.
class TipeNotifikasi
{
    public const PENAWARAN = 'Penawaran';
    public const PROGRES   = 'Progres';
    public const PEMBAYARAN = 'Pembayaran';
    public const SELESAI   = 'Selesai';
    public const ULASAN    = 'Ulasan';

    public const SEMUA = [
        self::PENAWARAN,
        self::PROGRES,
        self::PEMBAYARAN,
        self::SELESAI,
        self::ULASAN,
    ];
}