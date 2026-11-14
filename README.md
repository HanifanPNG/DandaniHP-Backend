# Dandanin HP — Backend API

Backend REST API untuk aplikasi service HP **Dandanin HP**. Dibangun dengan Laravel 12 + Sanctum,
mendukung tiga aktor: **Pelanggan**, **Mitra Service**, dan **Admin**.

- Spesifikasi produk: [`PRD.md`](PRD.md)
- Rancangan teknis: [`.agents/2-TECH-SPEC.md`](.agents/2-TECH-SPEC.md)
- Papan task: [`.agents/3-TASKS/2026-11-11_backend-dandanin-hp.md`](.agents/3-TASKS/2026-11-11_backend-dandanin-hp.md)

---

## Menjalankan Proyek

Prasyarat: PHP 8.4+, Composer, dan ekstensi SQLite.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite      # jika memakai SQLite
php artisan migrate:fresh --seed
php artisan serve                   # http://localhost:8000
```

Base URL API: `http://localhost:8000/api`

Perintah lain:

```bash
php artisan test                    # seluruh feature test
php artisan route:list --path=api   # daftar endpoint
```

### Akun Demo (hasil seeder)

| Aktor | Email | Kata sandi |
|---|---|---|
| Admin | `admin@dandanin.id` | `admin12345` |
| Pelanggan | `budi.santoso@example.com` | `password123` |
| Mitra | `amanah@dandanin.id` | `mitra12345` |

Semua mitra seeder memakai kata sandi `mitra12345` dan berstatus `Disetujui`.

---

## Autentikasi

Memakai **Laravel Sanctum** dengan tiga guard terpisah: `pelanggan`, `mitra`, `admin`.
Setelah login/register, sertakan header:

```
Authorization: Bearer <token>
```

Token hanya berlaku untuk guard asalnya. Token pelanggan yang dipakai di endpoint mitra
ditolak `401`.

### Kode Status yang Dipakai

| Kode | Arti |
|---|---|
| `200` | Berhasil |
| `201` | Dibuat |
| `401` | Belum terautentikasi / token salah guard |
| `403` | Terautentikasi tapi bukan pemilik resource atau akun belum diverifikasi |
| `404` | Resource tidak ditemukan atau bukan milik pengguna |
| `422` | Validasi gagal / aturan bisnis dilanggar |

---

## Endpoint

### Auth Pelanggan

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/auth/register` | Daftar pelanggan; status awal `Menunggu`, langsung dapat token |
| POST | `/api/auth/login` | Login dengan `email` atau `nomor_telepon` + `kata_sandi`; dibatasi 5 percobaan/menit |
| GET | `/api/auth/me` | Profil pengguna aktif + status verifikasi |
| POST | `/api/auth/logout` | Cabut token yang sedang dipakai |

Contoh register:

```json
POST /api/auth/register
{
  "nama_lengkap": "Budi Santoso",
  "email": "budi@example.com",
  "nomor_telepon": "081234567890",
  "alamat": "Jl. Contoh No. 1",
  "kata_sandi": "password123",
  "kata_sandi_confirmation": "password123"
}
```

### Auth Mitra

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/mitra/auth/register` | Daftar mitra; status awal `Menunggu` |
| POST | `/api/mitra/auth/login` | Login mitra; dibatasi 5 percobaan/menit |
| GET | `/api/mitra/auth/me` | Profil mitra aktif |
| POST | `/api/mitra/auth/logout` | Cabut token mitra |

### Admin

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/admin/auth/login` | Login admin |
| PATCH | `/api/admin/pelanggan/{id}/verifikasi` | Setujui/tolak pelanggan (`Disetujui` / `Ditolak`) |
| PATCH | `/api/admin/mitra/{id}/verifikasi` | Setujui/tolak mitra |

### Penemuan Mitra (Pelanggan)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/mitra` | Daftar mitra terverifikasi. Filter: `q`, `layanan`, `rating_min`, `buka_sekarang`, `lat` + `lng` (Haversine, urut terdekat), `per_page` (1–50) |
| GET | `/api/mitra/{id}` | Detail mitra: profil, layanan, galeri, ulasan |
| GET | `/api/mitra/{id}/ulasan` | Ulasan mitra (paginasi) |

### Permintaan Service (Pelanggan, akun terverifikasi)

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/permintaan` | Buat permintaan terbuka; wajib `merek_hp`, `model_hp`, `kategori_kerusakan`, `deskripsi_kerusakan`, lampiran opsional (maks 4 file) |
| GET | `/api/permintaan/{id}` | Detail permintaan (pemilik saja) |
| POST | `/api/permintaan/{id}/batalkan` | Batalkan selama belum ada pesanan aktif (BR-06) |

### Penawaran (Pelanggan)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/permintaan/{id}/penawaran` | Semua penawaran untuk satu permintaan milik pengguna |
| GET | `/api/penawaran/baru` | Penawaran masuk yang masih `Menunggu Keputusan` — dipakai widget **Tawaran Baru** di beranda |
| POST | `/api/penawaran/{id}/setujui` | Pilih pemenang; penawaran lain menjadi `Kedaluwarsa` dan pesanan aktif dibuat (BR-04, D1) |
| POST | `/api/penawaran/{id}/tolak` | Tolak satu penawaran; tidak membuat pesanan (BR-05) |

Contoh respons `GET /api/penawaran/baru`:

```json
{
  "data": [
    {
      "id_penawaran": 12,
      "permintaan": {
        "id_permintaan": 5,
        "kode_pesanan": "SRV-2026-00005",
        "merek_hp": "Samsung",
        "model_hp": "Galaxy A54 5G"
      },
      "mitra": { "id_mitra": 2, "nama_usaha": "Servis Amanah", "rating": 4.5 },
      "harga_penawaran": 250000,
      "estimasi_hari": 2,
      "estimasi_waktu": "2 hari",
      "catatan_pemeriksaan": "LCD perlu diganti",
      "status_penawaran": "Menunggu Keputusan",
      "waktu_penawaran": "2026-11-14T10:30:00+07:00"
    }
  ]
}
```

### Pesanan (Pelanggan)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/pesanan` | Pesanan aktif pelanggan |
| GET | `/api/pesanan/{id}` | Detail pesanan + timeline + info pembayaran |
| POST | `/api/pesanan/{id}/ulasan` | Kirim ulasan; hanya setelah `Pesanan Selesai` (BR-09), satu ulasan per pesanan (BR-10) |

Body ulasan: `rating_bintang` (1–5, wajib), `komentar_ulasan` (opsional, maks 1000),
`foto_ulasan` (opsional, gambar maks 2 MB, `multipart/form-data`).
Respons memuat `label_rating` dan `warna_rating` (lihat bagian Label Rating).

### Riwayat (Pelanggan)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/riwayat` | Semua permintaan/pesanan pelanggan. Filter `status` (Berlangsung, Selesai, Ditolak, Dibatalkan) |
| GET | `/api/riwayat/{id}` | Detail riwayat + ulasan jika ada |

### Notifikasi (Pelanggan)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/notifikasi` | Daftar notifikasi dikelompokkan Hari Ini / Kemarin / Sebelumnya |
| POST | `/api/notifikasi/{id}/baca` | Tandai satu notifikasi sudah dibaca |
| POST | `/api/notifikasi/baca-semua` | Tandai semua notifikasi sudah dibaca |

### Profil (Pelanggan)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/profil` | Profil pelanggan aktif |
| PUT | `/api/profil` | Update `nama_lengkap`, `nomor_telepon`, `alamat`, `foto_profil` |
| PUT | `/api/profil/kata-sandi` | Ubah kata sandi; wajib `kata_sandi_lama`, `kata_sandi_baru` + konfirmasi. Semua token dicabut |

### Bursa & Aksi Mitra (Mitra terverifikasi)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/mitra/permintaan` | Bursa permintaan terbuka. Filter `kategori` dan lokasi |
| POST | `/api/mitra/permintaan/{id}/penawaran` | Kirim penawaran (`harga_penawaran`, `estimasi_hari`, `estimasi_waktu`, `catatan_pemeriksaan`) |
| GET | `/api/mitra/pesanan` | Antrean pesanan mitra |
| POST | `/api/mitra/pesanan/{id}/status` | Ubah status pengerjaan (hanya transisi yang diizinkan) |
| POST | `/api/mitra/pesanan/{id}/pembayaran` | Konfirmasi pembayaran tunai (BR-07/BR-08); tanpa input metode pembayaran |

---

## Aturan Bisnis Penting

| Kode | Aturan |
|---|---|
| BR-01 | Pelanggan berstatus `Menunggu` tidak bisa membuat permintaan (`403`) |
| BR-04 | Pesanan hanya lahir dari penawaran yang disetujui |
| BR-05 | Penawaran ditolak tidak membuat pesanan |
| BR-06 | Pembatalan permintaan ditolak `422` jika pesanan aktif sudah ada |
| BR-07/08 | Pembayaran hanya tunai dan hanya dikonfirmasi mitra |
| BR-09 | Ulasan hanya untuk pesanan berstatus `Pesanan Selesai` |
| BR-10 | Satu pesanan hanya boleh punya satu ulasan |
| D1 | Menyetujui satu penawaran membuat penawaran lain `Kedaluwarsa` |

### Label Rating

`App\Support\LabelRating` memetakan bintang ke label dan warna badge:

| Bintang | Label | Warna |
|---|---|---|
| 1 | Buruk | `#DC2626` |
| 2 | Cukup | `#F59E0B` |
| 3 | Baik | `#0D9488` |
| 4 | Sangat Baik | `#2563EB` |
| 5 | Sempurna | `#16A34A` |

### Status Kanonik

Nilai status tersimpan di `App\Support\StatusPesanan` dan dipakai sebagai string kanonik
di seluruh API (tanpa enum database), sehingga frontend cukup menampilkan apa adanya.

---

## Deviasi dari PRD

Enam deviasi berikut disepakati pada tech spec bagian 7:

1. Asumsi #3 dan #4 PRD ("satu permintaan satu mitra", "mitra memberi satu penawaran") tidak berlaku.
2. Alur #5–#9: pelanggan tidak memilih mitra sebelum mengirim permintaan; permintaan bersifat terbuka.
3. FR-PARTNER-03: tombol "Ajukan Service" tidak mengikat mitra.
4. Rute penawaran memakai `/penawaran/{id}`, bukan `/pesanan/{id}/penawaran`.
5. FR-OFFER "Setujui/Tolak" menjadi "pilih satu dari N penawaran".
6. Filter riwayat "Ditolak" berarti seluruh penawaran ditolak.

---

## Struktur Singkat

```
app/
  Http/Controllers/Api/    controller per aktor (Auth, Admin, Pelanggan, Mitra)
  Http/Requests/           validasi form
  Http/Resources/          bentuk response JSON
  Models/                  model Eloquent per tabel
  Services/                logika bisnis (pesanan, penawaran, notifikasi)
  Support/                 konstanta status, tipe notifikasi, label rating
  Observers/               recalculate rating mitra saat ulasan berubah
```
