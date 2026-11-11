# Tasks — Dandanin HP Backend

| Informasi | Detail |
|---|---|
| Sumber acuan | `.agents/2-TECH-SPEC.md` v1.0 |
| Tanggal dibuat | 2026-11-11 |
| Status | Belum dieksekusi |
| Total task | 47 task dalam 11 modul |
| Aturan | Kerjakan berurutan sesuai dependensi. Jangan menambah fitur di luar task. Satu task selesai → ubah `Status` jadi `Done`. |

## Ringkasan Modul

| Modul | Task | Fokus |
|---|---|---|
| M0 | T-01 … T-08 | Skema database, model, seeder |
| M1 | T-09 … T-17 | Sanctum, auth 3 aktor, otorisasi |
| M2 | T-18 … T-20 | Penemuan mitra |
| M3 | T-21 … T-25 | Permintaan service |
| M4 | T-26 … T-28 | Penawaran & pemilihan pemenang |
| M5 | T-29 … T-33 | Pesanan, status kanonik, riwayat |
| M6 | T-34 … T-38 | Aksi mitra |
| M7 | T-39 … T-40 | Ulasan & rating |
| M8 | T-41 … T-42 | Notifikasi |
| M9 | T-43 … T-44 | Profil pelanggan |
| M10 | T-45 … T-47 | Factory, feature test, dokumentasi |

> ⚠️ **Peringatan D4:** T-01 mengedit migration yang sudah jalan dan berakhir dengan `migrate:fresh --seed`. Seluruh data di database `Dandani-HP` akan hilang. Pastikan tidak ada data yang perlu disimpan.

---

## M0 — Skema Database, Model, Seeder

### T-01 — Kolom auth & verifikasi pada `pelanggan` dan `mitra_service`
- **Modul:** M0
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** —
- **Estimasi:** 3 jam
- **File:** `database/migrations/2026_11_11_000003_create_pelanggan_table.php`, `database/migrations/2026_11_11_000002_create_mitra_service_table.php`, `app/Models/Pelanggan.php`, `app/Models/MitraService.php`

**Deskripsi:** Menyesuaikan dua tabel aktor agar bisa login dan menyimpan status verifikasi sesuai BR-01.

- [x] `pelanggan.nomor_telepon` → `unique()`
- [x] `pelanggan.status_verifikasi` → hapus `nullable()`, default `Menunggu`
- [x] `pelanggan.alasan_penolakan` → `text()->nullable()`
- [x] `mitra_service.kata_sandi` → `string(255)`
- [x] `mitra_service.deskripsi_usaha` → `text()->nullable()`
- [x] `mitra_service.jam_buka`, `jam_tutup` → `time()->nullable()`
- [x] `mitra_service.latitude`, `longitude` → `decimal(10,7)->nullable()`
- [x] `mitra_service.rating_rata_rata` → hapus `nullable()`, default `0.00`
- [x] `mitra_service.status_verifikasi` → hapus `nullable()`, default `Menunggu`
- [x] Update `$fillable`; `MitraService` tambah `$hidden = ['kata_sandi']`, cast `hashed`, dan `getAuthPassword()`
- [x] `migrate:fresh` sukses di MySQL

### T-02 — Tabel `layanan_mitra` dan `galeri_mitra`
- **Modul:** M0
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-01
- **Estimasi:** 2 jam
- **File:** migration baru, `app/Models/LayananMitra.php`, `app/Models/GaleriMitra.php`, `app/Models/MitraService.php`

**Deskripsi:** Menyediakan daftar layanan dan galeri yang diminta FR-PARTNER-01/03.

- [x] Migration `layanan_mitra`: `id_layanan`, `id_mitra` (cascade), `nama_layanan(100)`, `unique(id_mitra, nama_layanan)`
- [x] Migration `galeri_mitra`: `id_galeri`, `id_mitra` (cascade), `path(255)`
- [x] Model + `$fillable`, `$timestamps = false`
- [x] Relasi `MitraService::layanan()` dan `::galeri()`; relasi balik `mitra()`
- [x] `migrate:fresh` sukses

### T-03 — `permintaan_service` + tabel `lampiran_permintaan`
- **Modul:** M0
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-01
- **Estimasi:** 3 jam
- **File:** `database/migrations/2026_11_11_000004_create_permintaan_service_table.php`, migration baru, `app/Models/PermintaanService.php`, `app/Models/LampiranPermintaan.php`

**Deskripsi:** Melengkapi data permintaan sesuai FR-REQUEST-01 dan mengganti satu foto menjadi multi-lampiran.

- [x] Tambah `kode_pesanan(30)` `unique()`
- [x] Tambah `lama_kerusakan(50)` nullable, `kondisi_hp(100)` nullable, `catatan_tambahan` text nullable
- [x] Hapus kolom `foto_kerusakan`
- [x] `status_permintaan` → hapus `nullable()`
- [x] Migration `lampiran_permintaan`: `id_lampiran`, `id_permintaan` (cascade), `path`, `waktu_unggah`
- [x] Model `LampiranPermintaan` + relasi `PermintaanService::lampiran()`
- [x] `migrate:fresh` sukses

### T-04 — Guardrail `penawaran_mitra`
- **Modul:** M0
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-03
- **Estimasi:** 2 jam
- **File:** `database/migrations/2026_11_11_000005_create_penawaran_mitra_table.php`, `app/Models/PenawaranMitra.php`

**Deskripsi:** Mencegah satu mitra mengirim dua penawaran untuk permintaan yang sama (D1) dan menyimpan alasan penolakan.

- [x] `harga_penawaran` → `unsignedBigInteger`
- [x] Tambah `estimasi_hari` `unsignedTinyInteger` nullable
- [x] Tambah `alasan_penolakan` text nullable
- [x] `status_penawaran` → tambah nilai `Kedaluwarsa`; hapus `nullable()`
- [x] Tambah `unique(['id_permintaan', 'id_mitra'])`
- [x] Update `$fillable`
- [x] Uji insert penawaran ganda mitra sama → ditolak DB

### T-05 — `pesanan_aktif`, `pembayaran_tunai`, `ulasan`
- **Modul:** M0
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-04
- **Estimasi:** 3 jam
- **File:** `database/migrations/2026_11_11_000006_create_pesanan_aktif_table.php`, `..._000007_create_pembayaran_tunai_table.php`, `..._000008_create_ulasan_table.php`, model terkait

**Deskripsi:** Menambah status PRD yang hilang, memperbaiki state pembayaran, dan menegakkan BR-10.

- [x] `pesanan_aktif`: `unique('id_penawaran')`, `unique('id_permintaan')`
- [x] `pesanan_aktif.status_pengerjaan` → enum 6 nilai (`Masuk Antrean`, `Sedang Diproses`, `Service Selesai`, `Menunggu Pembayaran Tunai`, `Pembayaran Diterima`, `Pesanan Selesai`), default `Masuk Antrean`, hapus `nullable()`
- [x] `pesanan_aktif`: tambah `waktu_dibuat` default now; hapus `useCurrentOnUpdate()` dari `waktu_update_terakhir`
- [x] `pembayaran_tunai`: hapus `metode_pembayaran`
- [x] `pembayaran_tunai.jumlah_pembayaran` → `unsignedBigInteger`
- [x] `pembayaran_tunai`: tambah `waktu_dibuat`; `waktu_konfirmasi_mitra` nullable **tanpa** default; `status_pembayaran` → enum(`Menunggu Konfirmasi`,`Diterima`) default `Menunggu Konfirmasi`; `unique('id_pesanan')`
- [x] `ulasan`: `unique('id_pesanan')`, `rating_bintang` → `unsignedTinyInteger`
- [x] `ulasan`: hapus `DB::statement(... CHECK ...)`
- [x] `migrate:fresh` sukses di MySQL

### T-06 — Tabel `riwayat_status_pesanan` dan `notifikasi`
- **Modul:** M0
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-05
- **Estimasi:** 2 jam
- **File:** 2 migration baru, `app/Models/RiwayatStatusPesanan.php`, `app/Models/Notifikasi.php`

**Deskripsi:** Fondasi timeline FR-TRACKING dan notifikasi FR-NOTIFICATION.

- [x] Migration `riwayat_status_pesanan`: `id_riwayat`, `id_pesanan` (cascade), `status(50)`, `catatan` nullable, `aktor` enum(`Sistem`,`Mitra`,`Pelanggan`) default `Sistem`, `waktu` default now, index(`id_pesanan`,`waktu`)
- [x] Migration `notifikasi`: `id_notifikasi`, `id_pelanggan` (cascade), `tipe` enum 5 nilai, `judul(100)`, `pesan`, `id_pesanan` nullable `nullOnDelete`, `dibaca` boolean default false, `waktu_dibuat` default now, index(`id_pelanggan`,`dibaca`,`waktu_dibuat`)
- [x] Model + relasi: `PesananAktif::riwayat()`, `Pelanggan::notifikasi()`, `Notifikasi::pesanan()`
- [x] `migrate:fresh` sukses

### T-07 — Seeder konsisten
- **Modul:** M0
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-06
- **Estimasi:** 4 jam
- **File:** `database/seeders/DatabaseSeeder.php` + seeder per entitas

**Deskripsi:** Data awal yang bisa dipakai demo Flutter tanpa melanggar aturan bisnis.

- [x] Pecah `DatabaseSeeder` menjadi seeder per entitas
- [x] 1 administrator
- [x] 1 pelanggan `Disetujui`
- [x] 5–8 mitra `Disetujui`, masing-masing punya layanan & galeri
- [x] 3 skenario permintaan: tanpa penawaran, banyak penawaran menunggu keputusan, pesanan selesai + ulasan
- [x] Penawaran yang kalah berstatus `Kedaluwarsa`
- [x] Urutan waktu konsisten: dibuat < penawaran < update pesanan < konfirmasi bayar < ulasan
- [x] Kolom opsional diisi `null`, bukan `'-'`
- [x] `php artisan db:seed` sukses

### T-08 — Verifikasi migrasi & kompatibilitas test
- **Modul:** M0
- **Prioritas:** High
- **Status:** Done
- **Dependensi:** T-07
- **Estimasi:** 1 jam
- **File:** —

**Deskripsi:** Memastikan skema baru jalan di MySQL dan tidak merusak test SQLite.

- [x] `php artisan migrate:fresh --seed` sukses di MySQL
- [x] `php artisan test` tidak gagal karena migrasi
- [x] Cek tidak ada `useCurrentOnUpdate` / `ALTER ... CHECK` tersisa
- [x] Catat hasil di komentar task ini

**Hasil eksekusi (2026-11-11):**
- `migrate:fresh --seed` sukses; 13 tabel domain dibuat, 5 seeder dijalankan.
- `php artisan test` → 2 test, 2 assertion, lulus (SQLite `:memory:`).
- Unique index terverifikasi: `pelanggan(email, nomor_telepon)`, `mitra_service(email)`, `permintaan_service(kode_pesanan)`, `penawaran_mitra(id_permintaan, id_mitra)`, `pesanan_aktif(id_penawaran, id_permintaan)`, `pembayaran_tunai(id_pesanan)`, `ulasan(id_pesanan)`.
- Enum terverifikasi sesuai tech spec (6 status pengerjaan, 4 status penawaran, 2 status pembayaran).
- Uji insert duplikat penawaran, ulasan, dan nomor telepon → ditolak DB.
- Relasi terverifikasi: 2 lampiran, 2 penawaran, timeline 6 status, pembayaran `Diterima`.
- `Hash::check` kata sandi mitra hasil seeder → valid; kolom `kata_sandi` tersembunyi.

---

## M1 — Autentikasi & Otorisasi

### T-09 — Install Sanctum & aktifkan `routes/api.php`
- **Modul:** M1
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-08
- **Estimasi:** 1 jam
- **File:** `composer.json`, `bootstrap/app.php`, `routes/api.php`

- [ ] `composer require laravel/sanctum`
- [ ] Publish & jalankan migration `personal_access_tokens`
- [ ] `bootstrap/app.php`: tambahkan `api: __DIR__.'/../routes/api.php'`
- [ ] `routes/api.php` berisi grup `/api` kosong yang bisa diakses
- [ ] `php artisan route:list` menampilkan route api

### T-10 — Guard & provider 3 aktor
- **Modul:** M1
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-09
- **Estimasi:** 1 jam
- **File:** `config/auth.php`

- [ ] Provider `pelanggan` → `App\Models\Pelanggan`
- [ ] Provider `mitra` → `App\Models\MitraService`
- [ ] Provider `admin` → `App\Models\Administrator`
- [ ] Guard `pelanggan`, `mitra`, `admin` dengan driver `sanctum`
- [ ] Guard default tetap `web` (tidak mengganggu route `/`)
- [ ] Uji `Auth::guard('mitra')->user()` mengembalikan model yang benar

### T-11 — Trait token & bersihkan `User`
- **Modul:** M1
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-10
- **Estimasi:** 1 jam
- **File:** `app/Models/Pelanggan.php`, `app/Models/MitraService.php`, `app/Models/Administrator.php`, `app/Models/User.php`, `database/factories/UserFactory.php`

- [ ] Tambah `HasApiTokens` di 3 model aktor
- [ ] `Pelanggan` & `Administrator` sudah punya `getAuthPassword()`; pastikan `MitraService` juga
- [ ] Hapus model `User` dan `UserFactory` jika tidak dirujuk test lain
- [ ] Sesuaikan `config/auth.php` agar tidak lagi memakai `User::class`
- [ ] `php artisan optimize:clear` tanpa error

### T-12 — Registrasi pelanggan
- **Modul:** M1
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-11
- **Estimasi:** 3 jam
- **File:** `app/Http/Controllers/Api/Auth/PelangganAuthController.php`, `app/Http/Requests/Auth/RegisterRequest.php`, `app/Http/Resources/PelangganResource.php`, `routes/api.php`

**Endpoint:** `POST /api/auth/register`

- [ ] Validasi: nama wajib, email format + unique, nomor telepon format wajar + unique, alamat wajib, kata sandi min 8 + `confirmed`, syarat & ketentuan wajib `accepted`
- [ ] Simpan status `Menunggu`
- [ ] Kembalikan token + data pelanggan (agar Flutter bisa membuka halaman verifikasi)
- [ ] Password ter-hash (cast `hashed`)
- [ ] Test: registrasi valid → 201; validasi gagal → 422 dengan pesan per field

### T-13 — Login, logout, `me` pelanggan
- **Modul:** M1
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-12
- **Estimasi:** 2 jam
- **File:** `app/Http/Controllers/Api/Auth/PelangganAuthController.php`, `app/Http/Requests/Auth/LoginRequest.php`, `routes/api.php`

- [ ] `POST /api/auth/login` menerima email **atau** nomor telepon + kata sandi
- [ ] Password salah → 422/401 dengan pesan umum (tidak membocorkan akun ada/tidak)
- [ ] `throttle:5,1` pada login
- [ ] `POST /api/auth/logout` menghapus token aktif saja
- [ ] `GET /api/auth/me` mengembalikan profil + `status_verifikasi` + `alasan_penolakan`
- [ ] Test: login via telepon berhasil; login via email berhasil

### T-14 — Auth mitra
- **Modul:** M1
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-13
- **Estimasi:** 2 jam
- **File:** `app/Http/Controllers/Api/Auth/MitraAuthController.php`, `routes/api.php`

- [ ] `POST /api/mitra/auth/login` (email + kata sandi)
- [ ] `POST /api/mitra/auth/logout`
- [ ] `GET /api/mitra/auth/me` (termasuk `status_verifikasi`)
- [ ] Login tetap boleh untuk mitra `Menunggu`, aksi dibatasi middleware
- [ ] Test: token mitra tidak bisa memakai endpoint pelanggan

### T-15 — Auth admin & verifikasi akun
- **Modul:** M1
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-14
- **Estimasi:** 3 jam
- **File:** `app/Http/Controllers/Api/Auth/AdminAuthController.php`, `app/Http/Controllers/Api/Admin/VerifikasiController.php`, `app/Http/Requests/Admin/VerifikasiRequest.php`, `routes/api.php`

**Catatan:** minimal untuk menegakkan BR-01, bukan dashboard.

- [ ] `POST /api/admin/auth/login`
- [ ] `PATCH /api/admin/pelanggan/{id}/verifikasi` — nilai `Disetujui`/`Ditolak`
- [ ] `PATCH /api/admin/mitra/{id}/verifikasi`
- [ ] Jika `Ditolak`, `alasan_penolakan` wajib diisi
- [ ] Test: pelanggan ditolak bisa melihat alasan lewat `/api/auth/me`

### T-16 — Middleware verifikasi
- **Modul:** M1
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-15
- **Estimasi:** 1 jam
- **File:** `app/Http/Middleware/EnsurePelangganVerified.php`, `app/Http/Middleware/EnsureMitraVerified.php`, `bootstrap/app.php`

- [ ] `EnsurePelangganVerified` → 403 `{ "message": "Akun belum terverifikasi." }` jika status ≠ `Disetujui`
- [ ] `EnsureMitraVerified` → sama untuk mitra
- [ ] Daftarkan alias middleware
- [ ] Test: akun `Menunggu` ditolak saat membuat permintaan / mengirim penawaran

### T-17 — Policy kepemilikan resource
- **Modul:** M1
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-16
- **Estimasi:** 3 jam
- **File:** `app/Policies/PermintaanPolicy.php`, `PenawaranPolicy.php`, `PesananPolicy.php`, `UlasanPolicy.php`, `NotifikasiPolicy.php`

- [ ] Pelanggan hanya bisa akses permintaan/penawaran/pesanan/ulasan/notifikasi miliknya
- [ ] Mitra hanya bisa akses pesanan di mana `id_mitra` miliknya
- [ ] Admin tidak memakai policy ini (guard sendiri)
- [ ] Test: akses resource pelanggan lain → 403

---

## M2 — Penemuan Mitra

### T-18 — Daftar mitra + filter
- **Modul:** M2
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-17
- **Estimasi:** 3 jam
- **File:** `app/Http/Controllers/Api/Mitra/MitraDiscoveryController.php`, `app/Http/Resources/MitraResource.php`, `routes/api.php`

**Endpoint:** `GET /api/mitra`

- [ ] Hanya mitra `Disetujui` yang tampil
- [ ] Filter `q` (nama usaha / alamat / nama layanan)
- [ ] Filter `layanan`, `rating_min`, `buka`
- [ ] Sort `rekomendasi` (rating desc), `rating`, `jarak` (dari lat/long bila ada)
- [ ] Paginasi + `with('layanan')` (hindari N+1)
- [ ] Response memuat: nama, badge verified, rating, `review_count`, alamat, jam operasional, `is_open`, layanan utama, `foto_toko`

### T-19 — Detail mitra
- **Modul:** M2
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-18
- **Estimasi:** 2 jam
- **File:** controller & resource yang sama, `routes/api.php`

**Endpoint:** `GET /api/mitra/{id}`

- [ ] Identitas + status verifikasi
- [ ] Deskripsi usaha, alamat, nomor telepon, jam operasional
- [ ] Daftar layanan + galeri
- [ ] Rating, jumlah ulasan, ulasan terbaru (maks 3)
- [ ] `is_open` dihitung dari `jam_buka`/`jam_tutup` + waktu `Asia/Jakarta`
- [ ] 404 jika mitra tidak ada / belum `Disetujui`

### T-20 — Ulasan mitra
- **Modul:** M2
- **Prioritas:** Mid
- **Status:** Todo
- **Dependensi:** T-19
- **Estimasi:** 1 jam
- **File:** controller & resource, `routes/api.php`

**Endpoint:** `GET /api/mitra/{id}/ulasan`

- [ ] Paginasi, urut terbaru
- [ ] Hanya menampilkan nama pelanggan (tanpa email/telepon)
- [ ] Menampilkan rating, komentar, waktu

---

## M3 — Permintaan Service

### T-21 — Generator `kode_pesanan`
- **Modul:** M3
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-08
- **Estimasi:** 2 jam
- **File:** `app/Services/KodePesananGenerator.php`

- [ ] Format `SRV-{YYYY}-{5 digit urut}` (contoh `SRV-2026-00125`)
- [ ] Unik; retry maksimal 3x bila bentrok
- [ ] Dipanggil di dalam transaksi pembuatan permintaan
- [ ] Unit test: 2 permintaan berurutan menghasilkan kode berbeda

### T-22 — Buat permintaan service
- **Modul:** M3
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-21, T-17
- **Estimasi:** 3 jam
- **File:** `app/Http/Controllers/Api/Pelanggan/PermintaanController.php`, `app/Http/Requests/Pelanggan/StorePermintaanRequest.php`, `routes/api.php`

**Endpoint:** `POST /api/permintaan` (middleware verifikasi)

- [ ] Validasi: merek, model, kategori, deskripsi wajib; `lama_kerusakan`, `kondisi_hp`, `catatan_tambahan` opsional
- [ ] Status awal `Mencari Penawaran`
- [ ] `kode_pesanan` dari T-21
- [ ] `id_pelanggan` dari token, **bukan** dari body
- [ ] Response memuat `kode_pesanan` + id
- [ ] Test: akun belum verifikasi → 403 (BR-01)

### T-23 — Upload lampiran permintaan
- **Modul:** M3
- **Prioritas:** Mid
- **Status:** Todo
- **Dependensi:** T-22
- **Estimasi:** 2 jam
- **File:** `StorePermintaanRequest.php`, `app/Services/LampiranService.php`, `config/filesystems.php`

- [ ] Terima `lampiran[]` maksimal 4 file
- [ ] Validasi `image`, `mimes:jpg,jpeg,png,webp`, maks 2 MB per file
- [ ] Simpan ke disk `public` (`lampiran/{id_permintaan}/`)
- [ ] Hapus file lama bila permintaan diubah/dibatalkan
- [ ] Response mengembalikan URL publik
- [ ] Test: file > 2 MB → 422

### T-24 — Detail permintaan
- **Modul:** M3
- **Prioritas:** Mid
- **Status:** Todo
- **Dependensi:** T-22
- **Estimasi:** 1 jam
- **File:** controller, `PermintaanResource.php`, `routes/api.php`

**Endpoint:** `GET /api/permintaan/{id}`

- [ ] Hanya pemilik (Policy)
- [ ] Memuat lampiran, data HP, kerusakan, status, daftar penawaran ringkas
- [ ] Menyertakan `status_kanonik` (bergantung T-29)

### T-25 — Batalkan permintaan
- **Modul:** M3
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-24
- **Estimasi:** 2 jam
- **File:** controller, `routes/api.php`

**Endpoint:** `POST /api/permintaan/{id}/batalkan`

- [ ] Hanya pemilik
- [ ] Ditolak bila sudah ada `pesanan_aktif` (BR-06)
- [ ] Ditolak bila status sudah `Dibatalkan`
- [ ] Set `status_permintaan = Dibatalkan`
- [ ] Test: setelah penawaran disetujui → 422

---

## M4 — Penawaran & Pemilihan Pemenang

### T-26 — Daftar penawaran
- **Modul:** M4
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-25
- **Estimasi:** 2 jam
- **File:** `app/Http/Controllers/Api/Pelanggan/PenawaranController.php`, `PenawaranResource.php`, `routes/api.php`

**Endpoint:** `GET /api/permintaan/{id}/penawaran`

- [ ] Hanya pemilik permintaan
- [ ] Menampilkan semua mitra yang menawar (D1)
- [ ] Data: nama mitra, rating, harga, estimasi, catatan pemeriksaan, status
- [ ] Sort default: harga terendah, bisa `?sort=rating`
- [ ] Menyembunyikan penawaran `Kedaluwarsa` dari daftar keputusan

### T-27 — Setujui penawaran (transaksi)
- **Modul:** M4
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-26
- **Estimasi:** 4 jam
- **File:** `app/Services/PenawaranService.php`, controller, `routes/api.php`

**Endpoint:** `POST /api/penawaran/{id}/setujui`

- [ ] Semua langkah di dalam `DB::transaction` + `lockForUpdate`
- [ ] Validasi: pemilik, penawaran `Menunggu Keputusan`, permintaan belum punya pesanan
- [ ] Penawaran terpilih → `Disetujui`
- [ ] Penawaran lain pada permintaan sama → `Kedaluwarsa`
- [ ] Buat `pesanan_aktif` status `Masuk Antrean`; FK diturunkan dari penawaran
- [ ] Permintaan → `Selesai Dilelang`
- [ ] Tulis `riwayat_status_pesanan` (aktor `Sistem`)
- [ ] Test: penawaran lain otomatis `Kedaluwarsa`; approve kedua kali ditolak
- [ ] Test: race dua approve bersamaan hanya menghasilkan 1 pesanan (unique constraint)

### T-28 — Tolak penawaran
- **Modul:** M4
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-27
- **Estimasi:** 2 jam
- **File:** `PenawaranService.php`, controller, request, `routes/api.php`

**Endpoint:** `POST /api/penawaran/{id}/tolak`

- [ ] Hanya pemilik, hanya penawaran `Menunggu Keputusan`
- [ ] `alasan_penolakan` opsional (maks 500 karakter)
- [ ] Status → `Ditolak Pelanggan`
- [ ] Jika semua penawaran ditolak → status kanonik permintaan menjadi `rejected`
- [ ] BR-05: tidak ada pesanan yang dibuat
- [ ] Test: tolak satu dari dua → masih `waiting_customer_approval`

---

## M5 — Pesanan, Status Kanonik, Riwayat

### T-29 — Helper status kanonik
- **Modul:** M5
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-06
- **Estimasi:** 2 jam
- **File:** `app/Support/StatusPesanan.php`

- [ ] Konstanta semua status DB (permintaan, penawaran, pesanan, pembayaran)
- [ ] Method `kanonik(PermintaanService $p): string` sesuai tabel 2.3 tech spec
- [ ] Method `label(string $kanonik): string` (Bahasa Indonesia)
- [ ] Unit test untuk 10 status kanonik, termasuk `rejected` vs `cancelled`

### T-30 — Detail pesanan + timeline
- **Modul:** M5
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-29, T-27
- **Estimasi:** 3 jam
- **File:** `app/Http/Controllers/Api/Pelanggan/PesananController.php`, `PesananResource.php`, `routes/api.php`

**Endpoint:** `GET /api/pesanan/{id}`

- [ ] Hanya pemilik atau mitra terkait (Policy)
- [ ] `status_kanonik` + label
- [ ] Timeline dari `riwayat_status_pesanan` (urut waktu, termasuk aktor & catatan)
- [ ] Data HP, kerusakan, mitra + kontak
- [ ] Harga disetujui dari penawaran, `estimasi_selesai` = `waktu_dibuat` + `estimasi_hari`
- [ ] Info pembayaran tunai: jumlah, status, waktu konfirmasi (tanpa tombol konfirmasi)
- [ ] Eager load semua relasi, tanpa N+1

### T-31 — Daftar pesanan aktif
- **Modul:** M5
- **Prioritas:** Mid
- **Status:** Todo
- **Dependensi:** T-30
- **Estimasi:** 1 jam
- **File:** controller, `routes/api.php`

**Endpoint:** `GET /api/pesanan`

- [ ] Hanya pesanan milik pelanggan dengan status belum `Pesanan Selesai`
- [ ] Urut `waktu_update_terakhir` desc
- [ ] Response ringkas untuk kartu beranda

### T-32 — Riwayat service
- **Modul:** M5
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-31
- **Estimasi:** 2 jam
- **File:** `app/Http/Controllers/Api/Pelanggan/RiwayatController.php`, `routes/api.php`

**Endpoint:** `GET /api/riwayat?filter=`

- [ ] Filter `semua`, `berlangsung`, `selesai`, `ditolak`, `dibatalkan` sesuai PRD
- [ ] `rejected` = permintaan tanpa pesanan & semua penawaran ditolak
- [ ] Item memuat kode pesanan, mitra, tipe HP, tanggal, biaya, badge status
- [ ] Paginasi + empty state ditangani Flutter (API cukup mengembalikan array kosong)
- [ ] Test tiap filter

### T-33 — Detail riwayat
- **Modul:** M5
- **Prioritas:** Mid
- **Status:** Todo
- **Dependensi:** T-32
- **Estimasi:** 1 jam
- **File:** controller, resource, `routes/api.php`

**Endpoint:** `GET /api/riwayat/{id}`

- [ ] Read-only, hanya pemilik
- [ ] Seluruh perubahan status + status pembayaran + tanggal selesai
- [ ] Menyertakan ulasan bila ada
- [ ] Flag `bisa_ulasan` = status `Pesanan Selesai` && belum ada ulasan

---

## M6 — Aksi Mitra

### T-34 — Permintaan terbuka untuk mitra
- **Modul:** M6
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-17
- **Estimasi:** 2 jam
- **File:** `app/Http/Controllers/Api/Mitra/PermintaanTerbukaController.php`, `routes/api.php`

**Endpoint:** `GET /api/mitra/permintaan`

- [ ] Hanya `status_permintaan = Mencari Penawaran`
- [ ] Kecualikan permintaan yang sudah ditawar mitra ini
- [ ] Hanya untuk mitra `Disetujui`
- [ ] Paginasi + filter kategori
- [ ] Tidak menampilkan data pribadi pelanggan (nama boleh disamarkan)

### T-35 — Kirim penawaran
- **Modul:** M6
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-34
- **Estimasi:** 2 jam
- **File:** `app/Http/Controllers/Api/Mitra/PenawaranMitraController.php`, `StorePenawaranRequest.php`, `routes/api.php`

**Endpoint:** `POST /api/mitra/permintaan/{id}/penawaran`

- [ ] Mitra harus `Disetujui` (middleware)
- [ ] Permintaan harus `Mencari Penawaran`
- [ ] Harga > 0, estimasi wajib, catatan opsional
- [ ] `id_mitra` dari token, bukan body
- [ ] Duplikat → 422 dengan pesan jelas (guard unique DB)
- [ ] Membuat notifikasi `Penawaran` untuk pelanggan
- [ ] Test: mitra `Menunggu` → 403; penawaran ganda → 422

### T-36 — Daftar pesanan mitra
- **Modul:** M6
- **Prioritas:** Mid
- **Status:** Todo
- **Dependensi:** T-35
- **Estimasi:** 1 jam
- **File:** controller, `routes/api.php`

**Endpoint:** `GET /api/mitra/pesanan`

- [ ] Hanya pesanan `id_mitra` = mitra login
- [ ] Filter status opsional
- [ ] Ringkas: kode, HP, status, waktu update

### T-37 — Update status pesanan (mitra)
- **Modul:** M6
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-36
- **Estimasi:** 3 jam
- **File:** `app/Services/PesananService.php`, controller, `UpdateStatusRequest.php`, `routes/api.php`

**Endpoint:** `POST /api/mitra/pesanan/{id}/status`

- [ ] Whitelist transisi: `Masuk Antrean`→`Sedang Diproses`→`Service Selesai`→`Menunggu Pembayaran Tunai`→`Pembayaran Diterima`→`Pesanan Selesai`
- [ ] Transisi melompat / mundur → 422
- [ ] Setiap perubahan menulis `riwayat_status_pesanan` (aktor `Mitra`) + update `waktu_update_terakhir` + `catatan_perkembangan`
- [ ] Saat masuk `Menunggu Pembayaran Tunai`: buat baris `pembayaran_tunai` (`Menunggu Konfirmasi`, nominal = harga penawaran)
- [ ] Notifikasi `Progres`/`Selesai` ke pelanggan
- [ ] Semua dalam satu transaksi
- [ ] Test: skip status → 422

### T-38 — Konfirmasi pembayaran tunai
- **Modul:** M6
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-37
- **Estimasi:** 2 jam
- **File:** `app/Services/PembayaranService.php`, controller, `routes/api.php`

**Endpoint:** `POST /api/mitra/pesanan/{id}/pembayaran`

- [ ] Hanya mitra pemilik pesanan (BR-08)
- [ ] Pesanan harus `Menunggu Pembayaran Tunai`
- [ ] `status_pembayaran = Diterima`, `waktu_konfirmasi_mitra = now()`
- [ ] Pesanan → `Pembayaran Diterima` + riwayat + notifikasi `Pembayaran`
- [ ] Tidak menerima nominal/metode dari klien (BR-07)
- [ ] Test: pelanggan memanggil endpoint ini → 403

---

## M7 — Ulasan & Rating

### T-39 — Kirim ulasan
- **Modul:** M7
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-38
- **Estimasi:** 2 jam
- **File:** `app/Http/Controllers/Api/Pelanggan/UlasanController.php`, `StoreUlasanRequest.php`, `routes/api.php`

**Endpoint:** `POST /api/pesanan/{id}/ulasan`

- [ ] Hanya pemilik pesanan
- [ ] Status pesanan harus `Pesanan Selesai` (BR-09)
- [ ] Belum ada ulasan (BR-10; unique DB sebagai lapis kedua)
- [ ] Rating 1–5 wajib, komentar maks 1000 karakter
- [ ] `id_pelanggan` & `id_mitra` diisi dari pesanan
- [ ] Notifikasi `Ulasan` (terima kasih) bila perlu
- [ ] Test: ulasan ganda → 422; pesanan belum selesai → 422

### T-40 — Rekalkulasi rating mitra
- **Modul:** M7
- **Prioritas:** Mid
- **Status:** Todo
- **Dependensi:** T-39
- **Estimasi:** 1 jam
- **File:** `app/Observers/UlasanObserver.php`, `app/Providers/AppServiceProvider.php`

- [ ] Observer `created`, `updated`, `deleted`
- [ ] `mitra.rating_rata_rata = round(avg(rating_bintang), 2)`
- [ ] Daftarkan observer
- [ ] `review_count` dihitung via `withCount('ulasan')`, tidak disimpan
- [ ] Test: ulasan 5 & 3 → rata-rata 4.00

---

## M8 — Notifikasi

### T-41 — `NotifikasiService` + titik pembuatan
- **Modul:** M8
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-38
- **Estimasi:** 3 jam
- **File:** `app/Services/NotifikasiService.php`, `app/Support/TipeNotifikasi.php`

**Deskripsi:** Satu tempat pembuatan notifikasi; jangan menulis `DB::table('notifikasi')->insert` tersebar.

- [ ] Method `kirim(Pelanggan $p, string $tipe, string $judul, string $pesan, ?int $idPesanan)`
- [ ] Tipe sesuai PRD: `Penawaran`, `Progres`, `Pembayaran`, `Selesai`, `Ulasan`
- [ ] Dipanggil saat: penawaran masuk (T-35), penawaran disetujui (T-27), status berubah (T-37), service selesai (T-37), pembayaran diterima (T-38), pesanan selesai (T-37)
- [ ] Tidak membuat notifikasi duplikat untuk event yang sama
- [ ] Unit test: setiap event menghasilkan 1 notifikasi dengan tipe benar

### T-42 — Endpoint notifikasi
- **Modul:** M8
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-41
- **Estimasi:** 2 jam
- **File:** `app/Http/Controllers/Api/Pelanggan/NotifikasiController.php`, `routes/api.php`

- [ ] `GET /api/notifikasi` — grup `hari_ini`, `kemarin`, `sebelumnya` + `jumlah_belum_dibaca`
- [ ] `POST /api/notifikasi/{id}/baca` — hanya milik sendiri
- [ ] `POST /api/notifikasi/baca-semua` — idempoten
- [ ] Test: menandai satu notifikasi tidak mengubah jumlah lainnya

---

## M9 — Profil Pelanggan

### T-43 — Lihat & ubah profil
- **Modul:** M9
- **Prioritas:** Mid
- **Status:** Todo
- **Dependensi:** T-17
- **Estimasi:** 2 jam
- **File:** `app/Http/Controllers/Api/Pelanggan/ProfilController.php`, `UpdateProfilRequest.php`, `routes/api.php`

- [ ] `GET /api/profil`
- [ ] `PUT /api/profil` — nama, nomor telepon, alamat, foto profil
- [ ] Unique telepon dengan pengecualian id sendiri
- [ ] Upload foto: `image`, maks 2 MB
- [ ] Status verifikasi tidak bisa diubah dari endpoint ini
- [ ] Test: telepon milik pelanggan lain → 422

### T-44 — Ubah kata sandi
- **Modul:** M9
- **Prioritas:** Mid
- **Status:** Todo
- **Dependensi:** T-43
- **Estimasi:** 1 jam
- **File:** `ProfilController.php`, `UpdateKataSandiRequest.php`, `routes/api.php`

**Endpoint:** `PUT /api/profil/kata-sandi`

- [ ] Wajib `kata_sandi_lama`, `kata_sandi_baru` min 8 + `confirmed`
- [ ] Kata sandi lama salah → 422
- [ ] Setelah berhasil, opsi: cabut semua token lain
- [ ] Test: verifikasi kata sandi baru bisa dipakai login

---

## M10 — Factory, Test, Dokumentasi

### T-45 — Factory semua model
- **Modul:** M10
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-07
- **Estimasi:** 3 jam
- **File:** `database/factories/*.php`

- [ ] Factory: Pelanggan, MitraService, PermintaanService, PenawaranMitra, PesananAktif, PembayaranTunai, Ulasan, Notifikasi, RiwayatStatusPesanan, LampiranPermintaan
- [ ] State helper: `mitraTerverifikasi()`, `pelangganTerverifikasi()`, `pesananSelesai()`
- [ ] Factory tidak melanggar unique constraint saat dipakai berulang

### T-46 — Feature test aturan bisnis
- **Modul:** M10
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-45, T-44
- **Estimasi:** 5 jam
- **File:** `tests/Feature/*.php`

- [ ] BR-01: pelanggan `Menunggu` gagal membuat permintaan
- [ ] BR-04: pesanan hanya lahir dari penawaran disetujui
- [ ] BR-05: penawaran ditolak tidak membuat pesanan
- [ ] BR-06: pembatalan setelah pesanan ada → 422
- [ ] BR-07/08: konfirmasi pembayaran hanya endpoint mitra + tidak ada input metode
- [ ] BR-09: ulasan sebelum `Pesanan Selesai` → 422
- [ ] BR-10: ulasan ganda → 422
- [ ] D1: approve penawaran → penawaran lain `Kedaluwarsa`, hanya 1 pesanan
- [ ] Otorisasi: pelanggan A tidak bisa membuka pesanan pelanggan B
- [ ] `php artisan test` hijau seluruhnya dengan SQLite `:memory:`

### T-47 — Dokumentasi endpoint & deviasi PRD
- **Modul:** M10
- **Prioritas:** Low
- **Status:** Todo
- **Dependensi:** T-46
- **Estimasi:** 2 jam
- **File:** `.agents/2-TECH-SPEC.md` (bagian deviasi), `README.md`

- [ ] Daftar endpoint + contoh request/response ringkas di `README.md`
- [ ] Catat 6 deviasi PRD dari tech spec bagian 7
- [ ] Catat cara menjalankan: `migrate:fresh --seed`, akun demo, base URL
- [ ] Jangan menambah dokumentasi di luar konteks backend ini

---

## Checklist Sebelum Mulai Eksekusi

- [ ] Pemilik produk setuju D1 (lelang) & D2 (mitra login)
- [ ] Pemilik produk setuju 6 deviasi PRD
- [ ] Database `Dandani-HP` boleh di-`migrate:fresh` (data lama hilang)
- [ ] Tiga keputusan kecil dikonfirmasi: hapus `foto_kerusakan`, hapus `metode_pembayaran`, `nomor_telepon` unique
