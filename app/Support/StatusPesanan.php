<?php

namespace App\Support;

use App\Models\PermintaanService;

// Menerjemahkan status teknis di database menjadi kunci kanonik
// yang dipakai aplikasi Flutter (lihat peta status di tech spec 2.3).
//
// Kenapa perlu?
// - Satu permintaan bisa punya banyak penawaran dan satu pesanan.
//   Status yang ditampilkan belum tentu sama dengan status_permintaan.
// - Aplikasi cukup menampilkan satu status ringkas, bukan empat status teknis.
class StatusPesanan
{
    // --- Nilai status di database ---

    // permintaan_service.status_permintaan
    public const PERMINTAAN_MENCARI_PENAWARAN = 'Mencari Penawaran';
    public const PERMINTAAN_SELESAI_DILELANG = 'Selesai Dilelang';
    public const PERMINTAAN_DIBATALKAN = 'Dibatalkan';

    // penawaran_mitra.status_penawaran
    public const PENAWARAN_MENUNGGU = 'Menunggu Keputusan';
    public const PENAWARAN_DISETUJUI = 'Disetujui';
    public const PENAWARAN_DITOLAK = 'Ditolak Pelanggan';
    public const PENAWARAN_KEDALUWARSA = 'Kedaluwarsa';

    // pesanan_aktif.status_pengerjaan
    public const PESANAN_MASUK_ANTREAN = 'Masuk Antrean';
    public const PESANAN_SEDANG_DIPROSES = 'Sedang Diproses';
    public const PESANAN_SERVICE_SELESAI = 'Service Selesai';
    public const PESANAN_MENUNGGU_PEMBAYARAN = 'Menunggu Pembayaran Tunai';
    public const PESANAN_PEMBAYARAN_DITERIMA = 'Pembayaran Diterima';
    public const PESANAN_SELESAI = 'Pesanan Selesai';

    // pembayaran_tunai.status_pembayaran
    public const PEMBAYARAN_MENUNGGU = 'Menunggu Konfirmasi';
    public const PEMBAYARAN_DITERIMA = 'Diterima';

    // --- Kunci kanonik yang dikirim ke aplikasi ---

    public const WAITING_OFFER = 'waiting_offer';
    public const WAITING_CUSTOMER_APPROVAL = 'waiting_customer_approval';
    public const QUEUED = 'queued';
    public const IN_PROGRESS = 'in_progress';
    public const SERVICE_COMPLETED = 'service_completed';
    public const WAITING_CASH_PAYMENT = 'waiting_cash_payment';
    public const PAYMENT_RECEIVED = 'payment_received';
    public const COMPLETED = 'completed';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    // Label Bahasa Indonesia untuk ditampilkan di aplikasi.
    private const LABEL = [
        self::WAITING_OFFER             => 'Menunggu Penawaran',
        self::WAITING_CUSTOMER_APPROVAL => 'Menunggu Persetujuan Anda',
        self::QUEUED                    => 'Masuk Antrean',
        self::IN_PROGRESS               => 'Sedang Diproses',
        self::SERVICE_COMPLETED         => 'Service Selesai',
        self::WAITING_CASH_PAYMENT      => 'Menunggu Pembayaran Tunai',
        self::PAYMENT_RECEIVED          => 'Pembayaran Diterima',
        self::COMPLETED                 => 'Pesanan Selesai',
        self::REJECTED                  => 'Semua Penawaran Ditolak',
        self::CANCELLED                 => 'Dibatalkan',
    ];

    // Menentukan status kanonik sebuah permintaan.
    //
    // Urutan pengecekan penting:
    // 1. Dibatalkan menang atas semua kondisi.
    // 2. Pesanan (kalau ada) menentukan tahap pengerjaan.
    // 3. Tanpa pesanan: tergantung ada/tidaknya penawaran.
    public static function kanonik(PermintaanService $permintaan): string
    {
        if ($permintaan->status_permintaan === self::PERMINTAAN_DIBATALKAN) {
            return self::CANCELLED;
        }

        // Relasi hasOne: null kalau penawaran belum disetujui.
        $pesanan = $permintaan->pesanan;

        if ($pesanan !== null) {
            return match ($pesanan->status_pengerjaan) {
                self::PESANAN_MASUK_ANTREAN       => self::QUEUED,
                self::PESANAN_SEDANG_DIPROSES     => self::IN_PROGRESS,
                self::PESANAN_SERVICE_SELESAI     => self::SERVICE_COMPLETED,
                self::PESANAN_MENUNGGU_PEMBAYARAN => self::WAITING_CASH_PAYMENT,
                self::PESANAN_PEMBAYARAN_DITERIMA => self::PAYMENT_RECEIVED,
                self::PESANAN_SELESAI             => self::COMPLETED,
                // Nilai tak dikenal jangan sampai membuat error 500.
                default                           => self::IN_PROGRESS,
            };
        }

        $penawaran = $permintaan->penawaran;

        if ($penawaran->isEmpty()) {
            return self::WAITING_OFFER;
        }

        if ($penawaran->contains(
            fn ($item) => $item->status_penawaran === self::PENAWARAN_MENUNGGU
        )) {
            return self::WAITING_CUSTOMER_APPROVAL;
        }

        // Semua ditolak pelanggan dan tidak ada pesanan => dianggap gugur.
        if ($penawaran->every(
            fn ($item) => $item->status_penawaran === self::PENAWARAN_DITOLAK
        )) {
            return self::REJECTED;
        }

        return self::WAITING_OFFER;
    }

    public static function label(string $kanonik): string
    {
        // Fallback: ubah kunci menjadi kalimat agar tetap bisa ditampilkan.
        return self::LABEL[$kanonik] ?? ucfirst(str_replace('_', ' ', $kanonik));
    }
}
