# Dandanin Auth API

## Status

In Progress

## Tujuan Project

Membangun autentikasi API untuk 3 aktor (pelanggan, mitra, administrator)
memakai Laravel Sanctum, lalu membatasi akses dengan middleware dan policy.

## Definition of Done

- [ ] Sanctum terpasang dan route `api` aktif
- [ ] Guard & provider untuk pelanggan, mitra, admin
- [ ] Registrasi pelanggan menyimpan status `Menunggu`
- [ ] Login pelanggan bisa via email atau nomor telepon
- [ ] Login mitra dan admin menghasilkan token terpisah
- [ ] Middleware menolak akun yang belum terverifikasi
- [ ] Policy menolak akses ke resource milik pelanggan lain
- [ ] Feature test untuk aturan di atas lulus

## Yang Sudah Dilakukan

- (belum ada)

## Konsep

### Mastered
- (belum ada)

### Understood
- (belum ada)

### Used
- (belum ada)

### Needs Review
- (belum ada)

## Kesulitan

- (belum ada)

## Catatan Belajar

- Mulai 2026-11-11, mode normal (satu action per giliran).
