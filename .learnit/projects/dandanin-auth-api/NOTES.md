# Dandanin Auth API

## Status

In Progress (28 step selesai)

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
- `GET /api/mitra` (daftar mitra + filter q/layanan/rating_min/buka, sort rating & jarak, paginasi)
- `GET /api/mitra/{id}` (detail + galeri + 3 ulasan terbaru)
- `GET /api/mitra/{id}/ulasan` (daftar ulasan, paginasi)
- Zona waktu `Asia/Jakarta` (BR-12) & `is_open` dihitung di model
- `POST /api/permintaan` (kode pesanan otomatis, pemilik dari token, BR-01)
- Upload lampiran permintaan (maks 4 gambar, 2 MB, disk `public`, URL publik)
- `GET /api/permintaan/{id}` (detail + lampiran + ringkasan penawaran + `status_kanonik`)
- `POST /api/permintaan/{id}/batalkan` (BR-06: ditolak bila sudah ada pesanan)
- `GET /api/permintaan/{id}/penawaran` (sort harga/rating, sembunyikan `Kedaluwarsa`)
- `POST /api/penawaran/{id}/setujui` (transaksi: pemenang, kedaluwarsa, pesanan, riwayat, notifikasi)
- `POST /api/penawaran/{id}/tolak` (BR-05: tanpa pesanan; semua ditolak → `rejected`)
- Unit test generator/status kanonik + feature test permintaan & penawaran (50 test lulus)

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
- Eloquent query builder: `when`, `whereHas`, `withCount`, `with`, `paginate`
- Haversine via `selectRaw` untuk sort jarak
- Api Resource: `MitraResource`, `MitraDetailResource`, `UlasanResource`, `PermintaanResource`, `PermintaanDetailResource`
- Form Request untuk validasi terpisah dari controller
- Service class (`KodePesananGenerator`, `LampiranService`) + transaksi database
- Helper status kanonik (`StatusPesanan`) + PHPUnit data provider
- Transaksi `DB::transaction` + `lockForUpdate` (setujui penawaran)
- Service class memusatkan transisi status (state machine)
- Feature/Unit test dengan `RefreshDatabase`

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
