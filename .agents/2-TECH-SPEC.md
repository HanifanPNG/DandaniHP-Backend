# Tech Spec — Dandanin HP Backend (Laravel + Flutter)

| Informasi | Detail |
|---|---|
| Versi | 1.0 |
| Tanggal | 11 November 2026 |
| Sumber acuan | `PRD.md` (v1.0) |
| Status | Draft untuk direview |
| Ruang lingkup | Backend API untuk aplikasi mobile Flutter pelanggan |
| Repository | `DandaniHP-Backend` |
| Basis kode | Laravel 13.17, PHP 8.3, MySQL, PHPUnit 12 |

> Dokumen ini **hanya** merangkum konteks yang sudah disepakati pada sesi review skema. Tidak menambah fitur, aktor, atau halaman baru di luar PRD dan dua keputusan teknis yang sudah diambil.

---

## 0. Ruang Lingkup dan Non-Goals

### 0.1 Keputusan yang sudah final

| ID | Keputusan | Alasan |
|---|---|---|
| D1 | **Model lelang dipertahankan** — 1 permintaan bisa menerima banyak penawaran dari banyak mitra | Sesuai DB yang sudah dibuat |
| D2 | **Mitra punya akun dan login sendiri** | BR-08 mewajibkan mitra yang mengonfirmasi pembayaran tunai |
| D3 | Status kanonik PRD **diturunkan** dari status per-entitas | Menghindari duplikasi status dan memudahkan Flutter |
| D4 | Migration yang sudah ada **diedit** lalu `migrate:fresh --seed` | Masih tahap awal, data hanya dari seeder. **Data lama akan hilang** |
| D5 | Tabel `users` + model `User` dihapus dari pemakaian | Tidak dipakai; tiga aktor punya tabel sendiri |

### 0.2 Termasuk

- Perbaikan skema 8 tabel yang sudah ada.
- 5 tabel baru: `lampiran_permintaan`, `layanan_mitra`, `galeri_mitra`, `riwayat_status_pesanan`, `notifikasi`.
- Autentikasi token 3 aktor (pelanggan, mitra, administrator) dengan Laravel Sanctum.
- Endpoint REST yang dibutuhkan alur PRD sisi pelanggan + aksi minimal sisi mitra + verifikasi minimal administrator.
- Seeder realistis dan konsisten secara bisnis.
- Feature test untuk aturan bisnis kritis.

### 0.3 Tidak termasuk

- Dashboard administrator (hanya 2 endpoint verifikasi minimal untuk menegakkan BR-01).
- Dashboard mitra berbentuk UI (hanya API).
- Chat, negosiasi harga, peta nyata, geolocation, OTP, lupa kata sandi, pembayaran online (transfer/kartu/QRIS/e-wallet), unggah bukti transfer, keranjang, kurir.
- Perubahan pada `PRD.md`.

### 0.4 Yang belum diputuskan

| Item | Dampak |
|---|---|
| Hosting / server target | Tidak memblokir implementasi; bagian 6 ditandai TBD |
| `id_mitra_diminati` pada permintaan (mitra asal dari halaman detail mitra) | Rekomendasi: **tidak dipakai**; permintaan bersifat terbuka (broadcast) |
| Penyimpanan file lampiran (disk lokal vs object storage) | Sementara pakai `storage/app/public`, mudah diganti |

---

## 1. Tech Stack & Arsitektur

### 1.1 Stack

| Layer | Teknologi | Versi / Catatan |
|---|---|---|
| Backend | Laravel | `^13.17` (composer.json) |
| Bahasa | PHP | `^8.3` (runtime terpasang 8.3.32) |
| Database | MySQL | `Dandani-HP`, port `3307`, driver `mysql` |
| ORM | Eloquent | Bawaan Laravel |
| Auth API | Laravel Sanctum | **belum terpasang** — perlu `composer require laravel/sanctum` |
| Testing | PHPUnit + SQLite `:memory:` | `phpunit.xml` |
| Cache / Session / Queue | database / database / sync | `config/*`, `.env` |
| Klien | Flutter (mobile) | Konsumen API; tidak ada kode di repo ini |

### 1.2 Arsitektur

```
Flutter (mobile)
   │  HTTPS + Bearer token (Sanctum)
   ▼
routes/api.php
   │  middleware: auth:pelanggan | auth:mitra | auth:admin, verified.profile
   ▼
Controller → FormRequest (validasi) → Service (transaksi + aturan bisnis) → Eloquent → MySQL
   │
   └── Storage publik (lampiran permintaan, foto mitra)
```

Prinsip: **aturan bisnis tidak ditaruh di controller**. Perpindahan status, pemilihan pemenang lelang, dan konfirmasi pembayaran dijalankan di kelas Service di dalam `DB::transaction()`.

### 1.3 Struktur folder target

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── Auth/ (PelangganAuthController, MitraAuthController, AdminAuthController)
│   │   ├── Admin/ (VerifikasiController)
│   │   ├── Mitra/ (MitraDiscoveryController, PermintaanTerbukaController, PenawaranController, PesananMitraController)
│   │   └── Pelanggan/ (PermintaanController, PenawaranController, PesananController, RiwayatController, UlasanController, NotifikasiController, ProfilController)
│   ├── Requests/ (per aksi)
│   ├── Resources/ (response DTO)
│   └── Middleware/ (EnsurePelangganVerified, EnsureMitraVerified)
├── Models/ (9 model lama + 5 model baru)
├── Policies/ (PesananPolicy, PermintaanPolicy, PenawaranPolicy)
├── Services/
│   ├── PenawaranService.php   (setujui/tolak/kedaluwarsa)
│   ├── PesananService.php     (transisi status + riwayat)
│   ├── PembayaranService.php  (konfirmasi tunai)
│   ├── UlasanService.php      (validasi + rekalkulasi rating mitra)
│   └── NotifikasiService.php  (pembuatan notifikasi)
└── Support/
    └── StatusPesanan.php      (konstanta + peta status kanonik)
database/
├── migrations/ (8 diedit + 5 baru)
├── seeders/ (DatabaseSeeder dipecah per entitas)
└── factories/ (Untuk testing)
```

---

## 2. Database Design

### 2.1 Entity Overview

| Entity | Tabel | Relasi |
|---|---|---|
| Administrator | `administrator` | — |
| Pelanggan | `pelanggan` | 1:N permintaan_service, 1:N pesanan_aktif, 1:N ulasan, 1:N notifikasi |
| Mitra Service | `mitra_service` | 1:N penawaran_mitra, 1:N pesanan_aktif, 1:N ulasan, 1:N layanan_mitra, 1:N galeri_mitra |
| Permintaan Service | `permintaan_service` | N:1 pelanggan; 1:N penawaran_mitra; 1:N lampiran_permintaan; 1:0..1 pesanan_aktif |
| Penawaran Mitra | `penawaran_mitra` | N:1 permintaan_service, N:1 mitra_service; 1:0..1 pesanan_aktif |
| Pesanan Aktif | `pesanan_aktif` | N:1 penawaran_mitra (pemenang), N:1 permintaan_service, N:1 pelanggan, N:1 mitra_service; 1:1 pembayaran_tunai; 1:0..1 ulasan; 1:N riwayat_status_pesanan |
| Pembayaran Tunai | `pembayaran_tunai` | N:1 pesanan_aktif |
| Ulasan | `ulasan` | N:1 pesanan_aktif (unik), N:1 pelanggan, N:1 mitra_service |
| Riwayat Status | `riwayat_status_pesanan` | N:1 pesanan_aktif |
| Notifikasi | `notifikasi` | N:1 pelanggan; N:0..1 pesanan_aktif |
| Lampiran | `lampiran_permintaan` | N:1 permintaan_service |
| Layanan Mitra | `layanan_mitra` | N:1 mitra_service |
| Galeri Mitra | `galeri_mitra` | N:1 mitra_service |

### 2.2 Definisi Tabel Target

Format: **tebal** = kolom baru, ~~coret~~ = kolom dihapus.

#### `pelanggan`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_pelanggan | bigint, PK | |
| nama_lengkap | varchar(100) | |
| email | varchar(100), unique | |
| **nomor_telepon** | varchar(20), **unique** | FR-AUTH-02 login via email **atau** telepon |
| alamat | text, nullable | |
| kata_sandi | varchar(255) | cast `hashed` |
| foto_profil | varchar(255), nullable | |
| status_verifikasi | enum(`Menunggu`,`Disetujui`,`Ditolak`), **NOT NULL**, default `Menunggu` | sebelumnya nullable |
| **alasan_penolakan** | text, nullable | FR-AUTH-03 state ditolak |

#### `mitra_service`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_mitra | bigint, PK | |
| nama_pemilik | varchar(100) | |
| nama_usaha | varchar(100) | |
| email | varchar(100), unique | identitas login mitra |
| **kata_sandi** | varchar(255) | cast `hashed`; D2 |
| nomor_telepon | varchar(20) | |
| alamat_usaha | text, nullable | |
| **deskripsi_usaha** | text, nullable | FR-PARTNER-03 |
| jam_operasional | varchar(50), nullable | label tampilan, dipertahankan |
| **jam_buka** | time, nullable | untuk menghitung `isOpen` |
| **jam_tutup** | time, nullable | |
| foto_toko | varchar(255), nullable | |
| **latitude** | decimal(10,7), nullable | urutan jarak (mock/lokal) |
| **longitude** | decimal(10,7), nullable | |
| rating_rata_rata | decimal(3,2), **NOT NULL**, default 0.00 | direkalkulasi dari `ulasan` |
| status_verifikasi | enum(`Menunggu`,`Disetujui`,`Ditolak`), **NOT NULL**, default `Menunggu` | syarat menawar |

Catatan: `remember_token` tidak diperlukan karena memakai token Sanctum, bukan session guard.

#### `layanan_mitra` (baru)
| Kolom | Tipe |
|---|---|
| id_layanan | bigint, PK |
| id_mitra | FK → `mitra_service.id_mitra`, cascade |
| nama_layanan | varchar(100) |
| | unique(`id_mitra`, `nama_layanan`) |

#### `galeri_mitra` (baru)
| Kolom | Tipe |
|---|---|
| id_galeri | bigint, PK |
| id_mitra | FK → `mitra_service.id_mitra`, cascade |
| path | varchar(255) |

#### `permintaan_service`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_permintaan | bigint, PK | |
| **kode_pesanan** | varchar(30), **unique** | mis. `SRV-2026-00125` (FR-REQUEST-03 / mock PRD) |
| id_pelanggan | FK → `pelanggan`, cascade | |
| merek_hp | varchar(50) | |
| model_hp | varchar(50) | |
| kategori_kerusakan | varchar(100) | |
| deskripsi_kerusakan | text | |
| **lama_kerusakan** | varchar(50), nullable | FR-REQUEST-01 |
| **kondisi_hp** | varchar(100), nullable | FR-REQUEST-01 |
| **catatan_tambahan** | text, nullable | FR-REQUEST-01 |
| ~~foto_kerusakan~~ | — | diganti `lampiran_permintaan` (multi-foto) |
| waktu_dibuat | timestamp, default now | |
| status_permintaan | enum(`Mencari Penawaran`,`Selesai Dilelang`,`Dibatalkan`), default `Mencari Penawaran` | istilah lelang dipertahankan (D1) |

#### `lampiran_permintaan` (baru)
| Kolom | Tipe |
|---|---|
| id_lampiran | bigint, PK |
| id_permintaan | FK → `permintaan_service`, cascade |
| path | varchar(255) |
| waktu_unggah | timestamp, default now |

#### `penawaran_mitra`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_penawaran | bigint, PK | |
| id_permintaan | FK → `permintaan_service`, cascade | |
| id_mitra | FK → `mitra_service`, cascade | |
| harga_penawaran | **unsignedBigInteger** | sebelumnya `integer` |
| **estimasi_hari** | unsignedTinyInteger, nullable | agar estimasi selesai bisa dihitung |
| estimasi_waktu | varchar(50) | label, mis. `2 Hari` |
| catatan_pemeriksaan | text, nullable | |
| **alasan_penolakan** | text, nullable | FR-OFFER-03 (opsional) |
| waktu_penawaran | timestamp, default now | |
| status_penawaran | enum(`Menunggu Keputusan`,`Disetujui`,`Ditolak Pelanggan`, **`Kedaluwarsa`**), default `Menunggu Keputusan` | `Kedaluwarsa` = kalah lelang |
| | | **unique(`id_permintaan`, `id_mitra`)** |

#### `pesanan_aktif`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_pesanan | bigint, PK | |
| id_penawaran | FK → `penawaran_mitra`, **unique** | satu order per penawaran |
| id_permintaan | FK → `permintaan_service`, **unique** | **satu pemenang per permintaan** (D1) |
| id_pelanggan | FK → `pelanggan` | diisi backend dari penawaran |
| id_mitra | FK → `mitra_service` | diisi backend dari penawaran |
| status_pengerjaan | enum(`Masuk Antrean`,`Sedang Diproses`,`Service Selesai`,`Menunggu Pembayaran Tunai`,`Pembayaran Diterima`,`Pesanan Selesai`), default `Masuk Antrean` | 3 nilai baru |
| catatan_perkembangan | text, nullable | cache catatan terbaru; wajib ditulis bersama riwayat |
| **waktu_dibuat** | timestamp, default now | |
| waktu_update_terakhir | timestamp, nullable | hapus `useCurrentOnUpdate()`; di-set eksplisit |

Invarian yang wajib dijaga service layer: `id_mitra`, `id_pelanggan`, dan `id_permintaan` **selalu** diturunkan dari `penawaran_mitra` pemenang, tidak pernah dari input klien.

#### `pembayaran_tunai`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_pembayaran | bigint, PK | |
| id_pesanan | FK → `pesanan_aktif`, cascade, **unique** | 1 pesanan = 1 pembayaran |
| jumlah_pembayaran | **unsignedBigInteger** | di-set dari `penawaran.harga_penawaran` |
| **waktu_dibuat** | timestamp, default now | baris dibuat saat status → Menunggu Pembayaran Tunai |
| ~~metode_pembayaran~~ | — | dihapus; BR-07/FR-CASH-01 |
| status_pembayaran | enum(`Menunggu Konfirmasi`,`Diterima`), default `Menunggu Konfirmasi` | sebelumnya hanya `Lunas` |
| waktu_konfirmasi_mitra | timestamp, **nullable, tanpa default** | sebelumnya `useCurrent()` sehingga tidak bisa null |

#### `ulasan`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_ulasan | bigint, PK | |
| id_pesanan | FK → `pesanan_aktif`, cascade, **unique** | BR-10 |
| id_pelanggan | FK → `pelanggan`, cascade | diisi backend dari pesanan |
| id_mitra | FK → `mitra_service`, cascade | diisi backend dari pesanan |
| rating_bintang | **unsignedTinyInteger** | validasi 1–5 di FormRequest |
| komentar_ulasan | text, nullable | |
| waktu_ulasan | timestamp, default now | |

Hapus `DB::statement('ALTER TABLE ... CHECK ...')` karena gagal di SQLite `:memory:` yang dipakai test.

#### `riwayat_status_pesanan` (baru)
| Kolom | Tipe |
|---|---|
| id_riwayat | bigint, PK |
| id_pesanan | FK → `pesanan_aktif`, cascade |
| status | varchar(50) — nilai sama dengan `status_pengerjaan` |
| catatan | text, nullable |
| aktor | enum(`Sistem`,`Mitra`,`Pelanggan`), default `Sistem` |
| waktu | timestamp, default now |
| | index(`id_pesanan`, `waktu`) |

#### `notifikasi` (baru)
| Kolom | Tipe |
|---|---|
| id_notifikasi | bigint, PK |
| id_pelanggan | FK → `pelanggan`, cascade |
| tipe | enum(`Penawaran`,`Progres`,`Pembayaran`,`Selesai`,`Ulasan`) |
| judul | varchar(100) |
| pesan | text |
| id_pesanan | FK → `pesanan_aktif`, nullable, `nullOnDelete` |
| dibaca | boolean, default false |
| waktu_dibuat | timestamp, default now |
| | index(`id_pelanggan`, `dibaca`, `waktu_dibuat`) |

#### Bawaan Laravel
- `personal_access_tokens` — dibuat oleh Sanctum.
- `users` — **tidak dipakai**, model `User` dihapus. Migration tabel dibiarkan atau dihapus; `sessions` & `password_reset_tokens` boleh tetap karena tidak mengganggu.

### 2.3 Peta Status

Disimpan dalam Bahasa Indonesia (konsisten dengan DB saat ini), diterjemahkan ke kunci kanonik PRD di `app/Support/StatusPesanan.php`.

| Kunci kanonik (API) | Sumber | Kondisi |
|---|---|---|
| `waiting_offer` | permintaan | `Mencari Penawaran` & belum ada penawaran |
| `waiting_customer_approval` | permintaan + penawaran | `Mencari Penawaran` & ada penawaran `Menunggu Keputusan` |
| `queued` | pesanan | `Masuk Antrean` |
| `in_progress` | pesanan | `Sedang Diproses` |
| `service_completed` | pesanan | `Service Selesai` |
| `waiting_cash_payment` | pesanan | `Menunggu Pembayaran Tunai` |
| `payment_received` | pesanan | `Pembayaran Diterima` |
| `completed` | pesanan | `Pesanan Selesai` |
| `rejected` | permintaan | semua penawaran `Ditolak Pelanggan`, tidak ada pesanan |
| `cancelled` | permintaan | `Dibatalkan` |

### 2.4 State Machine

```
permintaan: Mencari Penawaran ──(pilih penawaran)──► Selesai Dilelang
                  │
                  └──(pelanggan batalkan, hanya jika belum ada pesanan)──► Dibatalkan

penawaran: Menunggu Keputusan ──(disetujui)──► Disetujui
                            ├──(ditolak pelanggan)──► Ditolak Pelanggan
                            └──(ada pemenang lain)──► Kedaluwarsa

pesanan: Masuk Antrean → Sedang Diproses → Service Selesai
         → Menunggu Pembayaran Tunai → Pembayaran Diterima → Pesanan Selesai

pembayaran: Menunggu Konfirmasi ──(mitra konfirmasi)──► Diterima
```

Transisi dilakukan **hanya** oleh endpoint yang berwenang; tidak ada endpoint "set status bebas". Melompati urutan ditolak service layer.

### 2.5 Proses Kritis (transaksi)

**Setujui penawaran** (BR-03, BR-04, D1):
1. Lock penawaran + permintaan (`lockForUpdate`).
2. Validasi: penawaran milik pelanggan, status `Menunggu Keputusan`, permintaan belum punya pesanan.
3. `penawaran.status_penawaran = Disetujui`.
4. Penawaran lain pada permintaan sama → `Kedaluwarsa`.
5. Buat `pesanan_aktif` (status `Masuk Antrean`, FK diturunkan dari penawaran).
6. `permintaan.status_permintaan = Selesai Dilelang`.
7. Tulis `riwayat_status_pesanan` (status `Masuk Antrean`, aktor `Sistem`).
8. Buat notifikasi `Penawaran` untuk pelanggan.

**Konfirmasi pembayaran tunai** (BR-07, BR-08):
1. Hanya `auth:mitra`, pesanan harus milik mitra tersebut.
2. Status pesanan harus `Menunggu Pembayaran Tunai`.
3. Set `pembayaran.status_pembayaran = Diterima`, `waktu_konfirmasi_mitra = now()`.
4. `pesanan.status_pengerjaan = Pembayaran Diterima` + riwayat + notifikasi.

**Ulasan** (BR-09, BR-10):
1. Pesanan milik pelanggan & status `Pesanan Selesai`.
2. Belum ada ulasan (unique constraint sebagai lapis kedua).
3. Simpan; rekalkulasi `mitra.rating_rata_rata`; notifikasi `Ulasan`.

### 2.6 Seeder

| Data | Isi |
|---|---|
| Administrator | 1 akun |
| Pelanggan | 1 pelanggan disetujui (boleh memakai identitas mock PRD) |
| Mitra | 5–8 mitra `Disetujui`, masing-masing punya `layanan_mitra` dan `galeri_mitra` |
| Permintaan | minimal 3 skenario: tanpa penawaran, banyak penawaran menunggu keputusan, pesanan selesai + ulasan |
| Konsistensi waktu | `waktu_dibuat` < `waktu_penawaran` < `waktu_update_terakhir` < `waktu_konfirmasi_mitra` < `waktu_ulasan` |
| Status | penawaran yang kalah harus `Kedaluwarsa`, bukan `Menunggu Keputusan` |

Nilai `null` untuk kolom opsional, bukan string `'-'`.

---

## 3. Interface Design (REST API)

Konvensi:
- Prefix `/api`, response `{ "data": ..., "message": ... }`, paginasi `{ "data": [...], "meta": {...} }`.
- Error validasi `422` dengan `{ "message", "errors": { field: [...] } }`.
- Authorization memakai Policy (kepemilikan resource), bukan hanya cek guard.
- Semua tanggal ISO-8601 dengan timezone `Asia/Jakarta`.

### 3.1 Autentikasi

| Method | Path | Aktor | Keterangan |
|---|---|---|---|
| POST | `/api/auth/register` | publik | FR-AUTH-01; status awal `Menunggu` |
| POST | `/api/auth/login` | publik | email **atau** nomor_telepon + kata sandi |
| POST | `/api/auth/logout` | pelanggan | hapus token aktif |
| GET | `/api/auth/me` | pelanggan | data + status verifikasi |
| POST | `/api/mitra/auth/login` | publik | D2 |
| POST | `/api/mitra/auth/logout` | mitra | |
| GET | `/api/mitra/auth/me` | mitra | |
| POST | `/api/admin/auth/login` | publik | pendukung BR-01 |
| PATCH | `/api/admin/pelanggan/{id}/verifikasi` | admin | `Disetujui` / `Ditolak` + alasan |
| PATCH | `/api/admin/mitra/{id}/verifikasi` | admin | `Disetujui` / `Ditolak` |

### 3.2 Mitra (penemuan)

| Method | Path | Aktor | Keterangan |
|---|---|---|---|
| GET | `/api/mitra` | pelanggan | filter `q`, `layanan`, `rating_min`, `buka`, `sort` (`rekomendasi`,`rating`,`jarak`), paginasi |
| GET | `/api/mitra/{id}` | pelanggan | detail + layanan + galeri + rating + jumlah ulasan |
| GET | `/api/mitra/{id}/ulasan` | pelanggan | daftar ulasan mitra |

`isOpen` dihitung dari `jam_buka`/`jam_tutup` + waktu server.

### 3.3 Permintaan Service

| Method | Path | Aktor | Keterangan |
|---|---|---|---|
| POST | `/api/permintaan` | pelanggan terverifikasi | BR-01; menghasilkan `kode_pesanan`; multi-lampiran |
| GET | `/api/permintaan/{id}` | pemilik | detail + lampiran |
| POST | `/api/permintaan/{id}/batalkan` | pemilik | BR-06: hanya bila belum ada pesanan |

### 3.4 Penawaran

| Method | Path | Aktor | Keterangan |
|---|---|---|---|
| GET | `/api/permintaan/{id}/penawaran` | pemilik | daftar penawaran (D1) |
| POST | `/api/penawaran/{id}/setujui` | pemilik | transaksi bagian 2.5 |
| POST | `/api/penawaran/{id}/tolak` | pemilik | alasan opsional; bila semua ditolak → `rejected` |

### 3.5 Pesanan & Riwayat

| Method | Path | Aktor | Keterangan |
|---|---|---|---|
| GET | `/api/pesanan` | pelanggan | pesanan aktif |
| GET | `/api/pesanan/{id}` | pemilik / mitra terkait | detail + timeline + status kanonik + info pembayaran |
| GET | `/api/riwayat` | pelanggan | filter `semua`,`berlangsung`,`selesai`,`ditolak`,`dibatalkan` |
| GET | `/api/riwayat/{id}` | pemilik | read-only + data ulasan |

### 3.6 Aksi Mitra

| Method | Path | Aktor | Keterangan |
|---|---|---|---|
| GET | `/api/mitra/permintaan` | mitra terverifikasi | daftar permintaan terbuka yang belum ditawar mitra ini |
| POST | `/api/mitra/permintaan/{id}/penawaran` | mitra terverifikasi | unique(`id_permintaan`,`id_mitra`) |
| GET | `/api/mitra/pesanan` | mitra | daftar pesanan miliknya |
| POST | `/api/mitra/pesanan/{id}/status` | mitra | transisi sesuai 2.4 (tanpa melompat) |
| POST | `/api/mitra/pesanan/{id}/pembayaran` | mitra | BR-08 konfirmasi tunai |

### 3.7 Ulasan, Notifikasi, Profil

| Method | Path | Aktor | Keterangan |
|---|---|---|---|
| POST | `/api/pesanan/{id}/ulasan` | pemilik | BR-09, BR-10 |
| GET | `/api/pesanan/{id}/ulasan` | pemilik | |
| GET | `/api/notifikasi` | pelanggan | grup hari ini / kemarin / sebelumnya + jumlah belum dibaca |
| POST | `/api/notifikasi/{id}/baca` | pemilik | |
| POST | `/api/notifikasi/baca-semua` | pelanggan | |
| GET | `/api/profil` | pelanggan | |
| PUT | `/api/profil` | pelanggan | nama, telepon, alamat, foto |
| PUT | `/api/profil/kata-sandi` | pelanggan | kata sandi lama + baru |

---

## 4. Business Rules Enforcement

| ID | Aturan | Ditegakkan di |
|---|---|---|
| BR-01 | Akun terverifikasi sebelum mengajukan service | middleware `EnsurePelangganVerified` + status mitra untuk menawar |
| BR-02 | (D1) permintaan terbuka untuk banyak mitra | tidak ada `id_mitra` di permintaan; unique(`id_permintaan`,`id_mitra`) pada penawaran |
| BR-03 | Harga dari penawaran mitra | `pesanan` merujuk `id_penawaran`; harga tidak bisa diinput pelanggan |
| BR-04 | Kerja mulai setelah penawaran disetujui | `pesanan_aktif` hanya dibuat oleh `PenawaranService::setujui` |
| BR-05 | Penawaran ditolak tidak masuk antrean | tidak ada pembuatan pesanan pada alur tolak; penawaran kalah → `Kedaluwarsa` |
| BR-06 | Batal hanya sebelum penawaran disetujui | validasi `permintaan` belum punya `pesanan_aktif` |
| BR-07 | Hanya tunai di lokasi | kolom `metode_pembayaran` dihapus; tidak ada endpoint pembayaran online |
| BR-08 | Konfirmasi hanya oleh mitra | endpoint `/api/mitra/pesanan/{id}/pembayaran` + Policy kepemilikan |
| BR-09 | Ulasan hanya pesanan selesai | validasi `status_pengerjaan = Pesanan Selesai` |
| BR-10 | Satu pesanan satu ulasan | unique(`id_pesanan`) + validasi service |
| BR-11 | Format Rupiah | API Resource (`number_format`), nilai disimpan numerik |
| BR-12 | Waktu lokal Indonesia | `APP_TIMEZONE=Asia/Jakarta`, format di Resource |

Aturan tambahan dari keputusan D1/D2: satu pemenang per permintaan (`unique(id_permintaan)`), satu penawaran per mitra per permintaan, satu pembayaran per pesanan.

---

## 5. Keamanan, Performa, Testing

### 5.1 Keamanan

- Sanctum personal access token; token per device; logout menghapus token aktif.
- Guard + provider terpisah: `pelanggan`, `mitra`, `admin` (driver `sanctum`), sehingga token mitra tidak bisa mengakses endpoint pelanggan.
- Password cast `hashed` (sudah ada) dan `getAuthPassword()` di ketiga model.
- Rate limit login (`throttle`) untuk mencegah brute force.
- Policy kepemilikan untuk pesanan, permintaan, penawaran, ulasan, notifikasi.
- Validasi upload lampiran: `image`, `mimes:jpg,jpeg,png,webp`, maksimal 2 MB, maksimal 4 file.
- Tidak ada endpoint yang menerima `status` atau `harga` final dari klien.

### 5.2 Performa

- Eager loading relasi pada list (`with`), hindari N+1.
- Paginasi pada mitra, riwayat, notifikasi.
- Index: FK otomatis, tambahan `pelanggan.nomor_telepon`, `permintaan_service.kode_pesanan`, `riwayat_status_pesanan(id_pesanan, waktu)`, `notifikasi(id_pelanggan, dibaca, waktu_dibuat)`, unique pada pasangan kunci di 2.2.

### 5.3 Testing

- `phpunit.xml` memakai SQLite `:memory:` → migration harus kompatibel SQLite (hapus CHECK mentah, hindari `useCurrentOnUpdate`).
- Feature test wajib: BR-01, BR-04, BR-05, BR-06, BR-08, BR-09, BR-10, dan transaksi setujui penawaran (memastikan penawaran lain `Kedaluwarsa`).
- Factory untuk semua model.

### 5.4 Deployment & Development

```bash
composer install
cp .env.example .env
php artisan key:generate
composer require laravel/sanctum
php artisan migrate:fresh --seed
php artisan serve
```

Hosting: **TBD** (belum dibahas).

---

## 6. Task Breakdown

Urutan eksekusi; setiap task harus selesai dan lolos test sebelum lanjut.

| ID | Task | File utama | Kriteria selesai |
|---|---|---|---|
| T-01 | Edit migration `pelanggan` & `mitra_service` (kolom auth, verifikasi, profil mitra) + update model | `database/migrations/..._create_pelanggan_table.php`, `..._create_mitra_service_table.php`, `app/Models/Pelanggan.php`, `MitraService.php` | `migrate:fresh` sukses; unique telepon & email; `kata_sandi` mitra ada |
| T-02 | Tabel `layanan_mitra` & `galeri_mitra` + model/relasi | migration baru, `app/Models/LayananMitra.php`, `GaleriMitra.php` | Relasi mitra→layanan/galeri teruji |
| T-03 | Edit migration `permintaan_service` + tabel `lampiran_permintaan` + model | migration, `PermintaanService.php`, `LampiranPermintaan.php` | `kode_pesanan` unik; multi-lampiran; kolom FR-REQUEST-01 lengkap |
| T-04 | Edit migration `penawaran_mitra` (unique, enum, alasan, estimasi_hari) | migration, `PenawaranMitra.php` | Insert penawaran ganda mitra sama ditolak DB |
| T-05 | Edit migration `pesanan_aktif`, `pembayaran_tunai`, `ulasan` | migration + model | enum 6 status; pembayaran bisa `Menunggu Konfirmasi`; unique pesanan/ulasan/pembayaran |
| T-06 | Tabel `riwayat_status_pesanan` & `notifikasi` + model | migration, `RiwayatStatusPesanan.php`, `Notifikasi.php` | Timeline bisa ditulis & dibaca urut waktu |
| T-07 | Hapus pemakaian `users`/`User`; rapikan seeder | `app/Models/User.php`, `database/seeders/*` | Seeder konsisten (bagian 2.6), tanpa `'-'`, waktu berurutan |
| T-08 | Install Sanctum, daftarkan `api` route, konfigurasi 3 guard/provider | `composer.json`, `bootstrap/app.php`, `config/auth.php`, `routes/api.php` | Login ketiga aktor menghasilkan token; token mitra ditolak di endpoint pelanggan |
| T-09 | Auth pelanggan (register/login/logout/me) + middleware verifikasi | controller, request, resource, `EnsurePelangganVerified` | Register → `Menunggu`; belum verifikasi → 403 saat buat permintaan |
| T-10 | Auth mitra + verifikasi minimal admin | controller, request, middleware | Mitra `Menunggu` tidak bisa mengirim penawaran; admin bisa menyetujui |
| T-11 | Endpoint penemuan mitra + `isOpen` | controller, resource | Filter & sort berfungsi; `isOpen` benar terhadap jam buka/tutup |
| T-12 | Endpoint permintaan (buat/detail/batal) + upload lampiran | controller, request, service | Batalkan ditolak jika pesanan sudah ada (BR-06) |
| T-13 | Endpoint penawaran (list/setujui/tolak) + `PenawaranService` | controller, service, policy | Transaksi bagian 2.5 teruji; penawaran lain `Kedaluwarsa` |
| T-14 | Endpoint pesanan/riwayat + status kanonik + timeline | controller, resource, `StatusPesanan.php` | 10 status kanonik bisa muncul; timeline lengkap |
| T-15 | Endpoint aksi mitra (permintaan terbuka, kirim penawaran, update status, konfirmasi tunai) | controller, service, policy | Transisi melompat ditolak; konfirmasi hanya mitra pemilik pesanan |
| T-16 | Endpoint ulasan + rekalkulasi rating mitra | controller, service | Ulasan ganda & status belum selesai ditolak; rating mitra ter-update |
| T-17 | Endpoint notifikasi (list/baca/baca-semua) + pembuatan notifikasi di tiap event | controller, `NotifikasiService` | Badge unread benar; "baca semua" idempoten |
| T-18 | Endpoint profil (lihat/ubah/kata sandi) | controller, request | Telepon unik tetap terjaga; kata sandi lama diverifikasi |
| T-19 | Feature test aturan bisnis + perbaikan kompatibilitas SQLite | `tests/Feature/*` | Semua test hijau dengan SQLite `:memory:` |
| T-20 | Rapikan dokumentasi deviasi PRD | dokumen ini | Daftar deviasi disetujui pemilik produk |

---

## 7. Deviasi PRD yang Menyertai D1

Bagian ini harus disetujui dan dicatat saat PRD direvisi ke v1.1:

1. Asumsi #3 dan #4 PRD ("satu permintaan satu mitra", "mitra memberi satu penawaran") tidak berlaku.
2. Alur #5–#9: pelanggan tidak memilih mitra sebelum mengirim permintaan; permintaan bersifat terbuka.
3. FR-PARTNER-03: tombol "Ajukan Service" tidak mengikat mitra.
4. Rute penawaran memakai `/penawaran/:penawaranId`, bukan `/pesanan/:pesananId/penawaran`.
5. FR-OFFER "Setujui/Tolak" menjadi "pilih satu dari N penawaran".
6. Filter riwayat "Ditolak" berarti seluruh penawaran ditolak.

Semua aturan lain (verifikasi akun, pembayaran tunai, ulasan, notifikasi, timeline) mengikuti PRD tanpa perubahan.
