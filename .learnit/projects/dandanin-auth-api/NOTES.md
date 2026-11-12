# Dandanin Auth API

## Status

In Progress (14 step selesai)

## Tujuan Project

Membangun autentikasi API untuk 3 aktor (pelanggan, mitra, administrator)
memakai Laravel Sanctum, lalu membatasi akses dengan middleware dan policy.

## Definition of Done

- [x] Sanctum terpasang dan route `api` aktif
- [x] Guard & provider untuk pelanggan, mitra, admin
- [x] Registrasi pelanggan menyimpan status `Menunggu`
- [x] Login pelanggan bisa via email atau nomor telepon
- [x] Login mitra dan admin menghasilkan token terpisah
- [x] Middleware menolak akun yang belum terverifikasi
- [x] Policy menolak akses ke resource milik pelanggan lain
- [ ] Feature test untuk aturan di atas lulus

## Yang Sudah Dilakukan

- Install Sanctum + aktifkan `routes/api.php`
- Guard & provider: `pelanggan`, `mitra`, `admin`
- Trait `HasApiTokens` di 3 model
- Register, login, me, logout pelanggan
- Register, login, me, logout mitra
- Login admin (`/api/admin/auth/login`)
- Verifikasi pelanggan & mitra oleh admin (BR-01)
- Middleware `verified.pelanggan` / `verified.mitra` (403 untuk akun belum disetujui)
- Uji HTTP manual: 9 skenario lulus
- Uji verifikasi admin: 11 skenario lulus
- Uji middleware verifikasi: 9 skenario lulus
- Policy kepemilikan resource (5 policy) diterapkan dan diuji

## Konsep

### Mastered
- (belum ada)

### Understood
- mass assignment & kenapa status verifikasi tidak boleh dari client

### Used
- Sanctum token
- guard & provider
- middleware `auth:pelanggan` / `auth:mitra`
- `createToken()` dan `currentAccessToken()->delete()`
- route model binding manual (`findOrFail`)
- Policy `view`, `update`, `delete` untuk 5 model
- Gate::policy terdaftar di AppServiceProvider

### Needs Review
- struktur array bersarang di `config/auth.php`

## Kesulitan

- Guard tidak terdaftar karena kurung kurawal `web` terlalu bawah.
- Nama tabel pada aturan `unique:` sempat salah (`mitra` vs `mitra_service`).
- Variabel `$data` vs `$mitra` pada register mitra.

## Catatan Belajar

- Status verifikasi selalu di-set server, tidak pernah dari request.
- Satu tabel = satu model = satu provider = satu guard.
- Token pelanggan ditolak di guard mitra (sudah diuji).
