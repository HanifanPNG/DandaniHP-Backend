# Index Task — Dandanin HP Backend

Sumber acuan: `.agents/2-TECH-SPEC.md` v1.0

| File | Tanggal | Modul | Task | Status | Total estimasi |
|---|---|---|---|---|---|
| [`3-TASKS/2026-11-11_backend-dandanin-hp.md`](3-TASKS/2026-11-11_backend-dandanin-hp.md) | 2026-11-11 | M0–M10 | T-01 … T-47 | M0 selesai (T-01–T-08); M1–M10 Todo | ± 98 jam |

## Urutan Eksekusi yang Disarankan

1. **M0 (T-01 … T-08)** — skema, model, seeder. Semua task lain bergantung pada ini.
2. **M1 (T-09 … T-17)** — Sanctum, auth 3 aktor, policy.
3. **M2 (T-18 … T-20)** — penemuan mitra.
4. **M3 (T-21 … T-25)** — permintaan service.
5. **M4 (T-26 … T-28)** — penawaran & pemilihan pemenang.
6. **M5 (T-29 … T-33)** — pesanan, status kanonik, riwayat.
7. **M6 (T-34 … T-38)** — aksi mitra.
8. **M7 (T-39 … T-40)** — ulasan & rating.
9. **M8 (T-41 … T-42)** — notifikasi.
10. **M9 (T-43 … T-44)** — profil.
11. **M10 (T-45 … T-47)** — factory, feature test, dokumentasi.

## Prasyarat

Sebelum mengeksekusi T-01, konfirmasi:

- D1 model lelang dipertahankan.
- D2 mitra punya akun & login.
- 6 deviasi PRD disetujui.
- Database `Dandani-HP` boleh di-`migrate:fresh` (data lama hilang).
- Hapus `foto_kerusakan` & `metode_pembayaran`; `nomor_telepon` pelanggan unique.
