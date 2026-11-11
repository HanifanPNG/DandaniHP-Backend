# Product Requirements Document (PRD)

## Dandanin HP — Website Pelanggan

| Informasi | Detail |
|---|---|
| Nama produk | Dandanin HP |
| Platform | Website responsif |
| Area produk | Antarmuka pelanggan/pengguna |
| Versi dokumen | 1.0 |
| Status | Siap diimplementasikan |
| Bahasa antarmuka | Bahasa Indonesia |
| Target implementasi awal | Frontend high-fidelity dengan data mock |

---
/
## 1. Ringkasan Eksekutif

**Dandanin HP** adalah platform yang mempertemukan pelanggan yang memiliki HP rusak dengan mitra penyedia jasa service HP yang telah diverifikasi. Platform tidak memperbaiki HP secara langsung. Fungsinya adalah membantu pelanggan menemukan mitra, mengirim permintaan service, menerima dan menyetujui penawaran harga, memantau pengerjaan, melakukan pembayaran tunai di lokasi mitra, serta memberikan rating dan ulasan.

Dokumen ini menjadi acuan OpenCode untuk membangun desain frontend lengkap bagian pelanggan. Hasil akhir harus berupa website responsif, konsisten, dapat dinavigasi, dan memperlihatkan seluruh alur utama menggunakan data mock.

---

## 2. Latar Belakang Masalah

Pelanggan yang mengalami kerusakan HP sering menghadapi beberapa kendala:

- Tidak mengetahui tempat service yang sesuai dan dapat dipercaya.
- Sulit membandingkan reputasi, lokasi, layanan, dan jam operasional mitra.
- Tidak memperoleh gambaran harga sebelum menyetujui perbaikan.
- Harus menanyakan perkembangan pengerjaan secara manual.
- Tidak mempunyai riwayat service yang tersimpan dengan rapi.

Dandanin HP menyelesaikan masalah tersebut melalui satu alur digital yang transparan, sederhana, dan mudah dipahami.

---

## 3. Visi Produk

Menjadi platform penghubung service HP yang membuat proses mencari mitra, menyetujui biaya, dan memantau perbaikan terasa mudah, jelas, serta terpercaya.

---

## 4. Sasaran Produk

### 4.1 Sasaran utama

1. Mempermudah pelanggan menemukan mitra service yang sesuai.
2. Menampilkan informasi mitra secara transparan dan meyakinkan.
3. Memungkinkan pelanggan menjelaskan kerusakan HP secara terstruktur.
4. Memberikan kontrol kepada pelanggan untuk menyetujui atau menolak harga.
5. Menampilkan perkembangan service melalui status yang mudah dipahami.
6. Menyimpan riwayat service, pembayaran tunai, rating, dan ulasan.

### 4.2 Indikator keberhasilan prototipe

- Pengguna dapat menyelesaikan alur utama tanpa menemui halaman buntu.
- Seluruh 16 halaman pelanggan tersedia.
- Semua tombol utama mengarah ke state atau halaman yang tepat.
- Status pengerjaan konsisten pada seluruh halaman.
- Aturan pembayaran tunai tidak dilanggar oleh komponen atau halaman apa pun.
- Tampilan dapat digunakan pada lebar layar mulai 360 px hingga desktop.

---

## 5. Ruang Lingkup

### 5.1 Termasuk dalam versi ini

- Landing page.
- Registrasi dan login pelanggan.
- Tampilan status verifikasi akun.
- Beranda pelanggan.
- Pencarian dan filter mitra.
- Detail mitra.
- Form permintaan service dan unggah foto mock.
- Ringkasan serta pengiriman permintaan.
- Detail penawaran harga.
- Persetujuan atau penolakan penawaran.
- Pelacakan status service.
- Informasi pembayaran tunai.
- Riwayat service.
- Rating dan ulasan.
- Notifikasi.
- Profil dan edit profil.
- State loading, error, kosong, berhasil, dan konfirmasi.
- Navigasi responsif desktop dan mobile.

### 5.2 Tidak termasuk dalam versi ini

- Dashboard administrator.
- Dashboard mitra service.
- Backend, database, dan API nyata.
- Autentikasi nyata atau pengiriman OTP.
- Integrasi peta nyata.
- Geolocation dan perhitungan jarak nyata.
- Chat langsung pelanggan–mitra.
- Negosiasi harga.
- Pembayaran online.
- Transfer bank, kartu, QRIS, atau dompet digital.
- Pemilihan metode pembayaran.
- Unggah bukti transfer.
- Keranjang belanja.
- Pengiriman atau penjemputan HP oleh kurir.

---

## 6. Pengguna Sasaran

### Persona utama: pelanggan service HP

- Memiliki HP yang mengalami kerusakan.
- Membutuhkan rekomendasi tempat service yang dapat dipercaya.
- Ingin mengetahui estimasi harga sebelum perbaikan dimulai.
- Ingin memantau pengerjaan tanpa selalu menghubungi mitra.
- Mengakses website melalui ponsel maupun laptop.
- Memiliki tingkat kemampuan digital dasar hingga menengah.

### Kebutuhan pengguna

- Bahasa sederhana dan tidak teknis.
- Informasi harga dan status yang terlihat jelas.
- Tombol tindakan yang mudah ditemukan.
- Form yang tidak membingungkan.
- Konfirmasi sebelum tindakan penting.
- Informasi lokasi dan kontak mitra.

---

## 7. Asumsi Produk

1. Mitra yang ditampilkan telah diverifikasi administrator.
2. Verifikasi pelanggan dilakukan administrator sebelum akun dapat digunakan penuh.
3. Satu permintaan service hanya dikirim kepada satu mitra.
4. Mitra memberikan satu penawaran harga untuk satu permintaan.
5. Pengerjaan dimulai setelah penawaran disetujui pelanggan.
6. Pelanggan menyerahkan dan mengambil HP langsung di lokasi mitra.
7. Pembayaran hanya dilakukan secara tunai di lokasi mitra.
8. Mitra mengonfirmasi pembayaran setelah menerima uang tunai.
9. Rating dan ulasan hanya dapat dikirim setelah pesanan selesai.
10. Versi frontend menggunakan data mock yang konsisten pada seluruh halaman.

---

## 8. Peran dan Hak Akses

### Pengunjung

- Melihat landing page.
- Melihat penjelasan cara kerja.
- Melihat cuplikan mitra rekomendasi.
- Membuka halaman login dan registrasi.

### Pelanggan belum terverifikasi

- Melihat status verifikasi.
- Memperbaiki data jika verifikasi ditolak.
- Keluar dari akun.
- Tidak dapat mengajukan service.

### Pelanggan terverifikasi

- Mengakses seluruh fitur pelanggan.
- Mencari dan melihat detail mitra.
- Membuat permintaan service.
- Menyetujui atau menolak penawaran.
- Memantau status service.
- Melihat riwayat dan notifikasi.
- Memberikan rating dan ulasan.
- Mengelola profil.

---

## 9. Alur Utama Pengguna

1. Pengunjung membuka landing page.
2. Pengunjung mendaftar sebagai pelanggan.
3. Sistem menampilkan status menunggu verifikasi.
4. Setelah akun disetujui, pelanggan login.
5. Pelanggan mencari dan memilih mitra service.
6. Pelanggan mengisi form permintaan service.
7. Pelanggan memeriksa ringkasan dan mengirim permintaan.
8. Mitra mengirim penawaran harga.
9. Pelanggan menyetujui atau menolak penawaran.
10. Jika disetujui, pesanan masuk antrean dan dikerjakan.
11. Pelanggan memantau perubahan status.
12. Setelah service selesai, pelanggan datang ke lokasi mitra.
13. Pelanggan mengambil HP dan membayar secara tunai.
14. Mitra mengonfirmasi pembayaran.
15. Pesanan menjadi selesai.
16. Pelanggan memberikan rating dan ulasan.

---

## 10. Arsitektur Informasi dan Rute

Gunakan rute berikut atau padanan yang sesuai dengan router proyek.

| No. | Halaman | Rute yang disarankan | Akses |
|---:|---|---|---|
| 1 | Landing Page | `/` | Publik |
| 2 | Registrasi | `/daftar` | Publik |
| 3 | Login | `/masuk` | Publik |
| 4 | Status Verifikasi | `/verifikasi` | Pelanggan |
| 5 | Beranda Pengguna | `/beranda` | Terverifikasi |
| 6 | Daftar Mitra | `/mitra` | Terverifikasi |
| 7 | Detail Mitra | `/mitra/:mitraId` | Terverifikasi |
| 8 | Form Permintaan Service | `/mitra/:mitraId/ajukan-service` | Terverifikasi |
| 9 | Ringkasan Permintaan | `/service/ringkasan` | Terverifikasi |
| 10 | Detail Penawaran | `/pesanan/:pesananId/penawaran` | Terverifikasi |
| 11 | Status dan Detail Pesanan | `/pesanan/:pesananId` | Terverifikasi |
| 12 | Rating dan Ulasan | `/pesanan/:pesananId/ulasan` | Terverifikasi |
| 13 | Riwayat Service | `/riwayat` | Terverifikasi |
| 14 | Detail Riwayat Service | `/riwayat/:pesananId` | Terverifikasi |
| 15 | Notifikasi | `/notifikasi` | Terverifikasi |
| 16 | Profil Pengguna | `/profil` | Terverifikasi |

Untuk prototipe dengan satu pesanan aktif, menu **Pesanan Saya** boleh langsung membuka `/pesanan/SRV-2026-00125`.

---

## 11. Navigasi Global

### 11.1 Navbar publik

- Logo Dandanin HP.
- Cara Kerja.
- Cari Mitra.
- Tentang Kami.
- Masuk.
- Daftar.

### 11.2 Navbar pengguna desktop

- Logo Dandanin HP.
- Beranda.
- Cari Mitra.
- Pesanan Saya.
- Riwayat.
- Ikon Notifikasi dengan badge jumlah belum dibaca.
- Avatar dengan menu Profil dan Keluar.

### 11.3 Navigasi mobile

- Beranda.
- Cari.
- Pesanan.
- Notifikasi.
- Profil.

Navigasi aktif harus memiliki indikator teks, warna, dan/atau bentuk—bukan warna saja.

---

## 12. Kebutuhan Fungsional

### FR-AUTH — Akun dan autentikasi

#### FR-AUTH-01 Registrasi

Sistem harus menyediakan form dengan field:

- Nama lengkap.
- Email.
- Nomor telepon.
- Alamat.
- Kata sandi.
- Konfirmasi kata sandi.
- Persetujuan syarat dan ketentuan.

Validasi minimum:

- Semua field wajib harus diisi.
- Email harus memiliki format valid.
- Nomor telepon hanya menerima format nomor yang wajar.
- Kata sandi minimal 8 karakter.
- Konfirmasi kata sandi harus sama.
- Tombol menampilkan loading state saat form dikirim.

#### FR-AUTH-02 Login

- Pengguna dapat login dengan email atau nomor telepon dan kata sandi.
- Tersedia show/hide password.
- Tersedia tautan lupa kata sandi sebagai placeholder.
- Login berhasil mengarah ke beranda jika akun terverifikasi.
- Akun belum terverifikasi mengarah ke halaman status verifikasi.

#### FR-AUTH-03 Status verifikasi

Sediakan tiga state:

- Menunggu Verifikasi.
- Akun Disetujui.
- Verifikasi Ditolak beserta alasan dan tombol Perbaiki Data.

### FR-HOME — Beranda

#### FR-HOME-01 Konten beranda

Beranda harus menampilkan:

- Sapaan pengguna.
- Kolom pencarian mitra atau jenis kerusakan.
- Shortcut kategori kerusakan.
- Pesanan aktif.
- Penawaran terbaru yang membutuhkan keputusan.
- Mitra rekomendasi.
- Banner bantuan.

#### FR-HOME-02 Empty state

Jika tidak ada pesanan aktif, tampilkan empty state dan tombol **Cari Mitra Sekarang**.

### FR-PARTNER — Pencarian mitra

#### FR-PARTNER-01 Daftar mitra

Setiap kartu mitra harus menampilkan:

- Foto atau logo.
- Nama mitra.
- Badge Terverifikasi.
- Rating dan jumlah ulasan.
- Alamat singkat.
- Jam operasional.
- Status buka atau tutup.
- Layanan utama.
- Tombol Lihat Detail.

#### FR-PARTNER-02 Pencarian dan filter

Pengguna dapat melakukan filter berdasarkan:

- Nama atau lokasi.
- Kategori layanan.
- Rating minimum.
- Status buka.
- Urutan rekomendasi, rating, atau jarak mock.

#### FR-PARTNER-03 Detail mitra

Detail mitra harus menampilkan:

- Identitas dan status verifikasi.
- Deskripsi usaha.
- Alamat dan placeholder peta.
- Nomor kontak.
- Jam operasional.
- Daftar layanan.
- Galeri lokasi.
- Rating dan ulasan.
- Tombol Ajukan Service.

### FR-REQUEST — Permintaan service

#### FR-REQUEST-01 Form permintaan

Form dibagi menjadi:

1. Data HP: merek dan tipe/model.
2. Detail kerusakan: kategori, deskripsi, lama kerusakan, kondisi HP.
3. Lampiran: foto kerusakan dan catatan tambahan.

#### FR-REQUEST-02 Unggah foto mock

- Pengguna dapat memilih file gambar.
- Sistem menampilkan preview lokal.
- Pengguna dapat menghapus foto sebelum mengirim.
- Tampilkan format dan ukuran file yang diterima sebagai teks bantuan.
- Tidak diperlukan unggahan server pada versi frontend.

#### FR-REQUEST-03 Ringkasan

- Tampilkan seluruh data sebelum dikirim.
- Pengguna dapat kembali untuk mengubah data.
- Tombol Kirim Permintaan membuka modal berhasil.
- Modal menampilkan nomor pesanan dan tombol Lihat Status Pesanan.

### FR-OFFER — Penawaran harga

#### FR-OFFER-01 Detail penawaran

Tampilkan:

- Nomor pesanan.
- Informasi mitra.
- Data HP.
- Hasil pemeriksaan awal.
- Harga penawaran.
- Estimasi pengerjaan.
- Catatan mitra.

#### FR-OFFER-02 Persetujuan

- Tombol Setujui Penawaran menampilkan modal konfirmasi.
- Setelah dikonfirmasi, status berubah menjadi Masuk Antrean.
- Pengguna diarahkan ke detail pesanan.

#### FR-OFFER-03 Penolakan

- Tombol Tolak Penawaran menampilkan modal konfirmasi.
- Alasan penolakan bersifat opsional.
- Setelah dikonfirmasi, status berubah menjadi Ditolak.
- Pesanan yang ditolak tidak masuk proses pengerjaan.

### FR-TRACKING — Pelacakan service

#### FR-TRACKING-01 Detail pesanan

Tampilkan:

- Nomor dan badge status pesanan.
- Nama serta kontak mitra.
- Timeline status.
- Detail HP dan kerusakan.
- Harga yang disetujui.
- Estimasi selesai.
- Catatan pembaruan dari mitra.
- Informasi pembayaran tunai.

#### FR-TRACKING-02 Timeline

- Status selesai menggunakan ikon centang.
- Status aktif disorot dengan jelas.
- Status mendatang berwarna netral.
- Tampilkan waktu perubahan jika tersedia.
- Mobile menggunakan timeline vertikal.

### FR-CASH — Pembayaran tunai

#### FR-CASH-01 Aturan pembayaran

- Pembayaran hanya dilakukan secara tunai di lokasi mitra.
- Tidak boleh ada halaman pembayaran.
- Tidak boleh ada pemilihan metode pembayaran.
- Tidak boleh ada rekening, kartu, QRIS, e-wallet, atau unggah bukti transfer.
- Pelanggan tidak memiliki tombol Konfirmasi Pembayaran.
- Mitra yang mengubah status menjadi Pembayaran Diterima.

#### FR-CASH-02 Informasi pengambilan

Saat service selesai, tampilkan panel perhatian:

> HP Anda telah selesai diperbaiki. Silakan datang ke lokasi mitra untuk mengambil HP dan melakukan pembayaran tunai sebesar Rp350.000.

### FR-HISTORY — Riwayat

#### FR-HISTORY-01 Daftar riwayat

Sediakan filter:

- Semua.
- Berlangsung.
- Selesai.
- Ditolak.
- Dibatalkan.

Setiap item menampilkan nomor pesanan, mitra, tipe HP, tanggal, biaya, badge status, dan tombol Lihat Detail.

#### FR-HISTORY-02 Detail riwayat

Tampilkan data HP, kerusakan, mitra, harga, seluruh perubahan status, status pembayaran tunai, tanggal selesai, dan ulasan.

### FR-REVIEW — Rating dan ulasan

- Halaman hanya dapat diakses setelah pesanan selesai.
- Pengguna memilih rating 1–5 bintang.
- Tampilkan label arti rating.
- Pengguna dapat menulis komentar.
- Tampilkan penghitung karakter.
- Satu pesanan hanya menerima satu ulasan dari pelanggan.
- Setelah berhasil, tampilkan konfirmasi dan ulasan pada detail riwayat.

### FR-NOTIFICATION — Notifikasi

Notifikasi dikelompokkan menjadi:

- Hari Ini.
- Kemarin.
- Sebelumnya.

Jenis notifikasi:

- Penawaran baru.
- Penawaran disetujui atau ditolak.
- Service mulai diproses.
- Service selesai.
- Pembayaran tunai dikonfirmasi.
- Pesanan selesai.
- Pengingat memberikan ulasan.

Sediakan indikator belum dibaca dan tombol **Tandai semua sudah dibaca**.

### FR-PROFILE — Profil

Pengguna dapat melihat dan mengubah:

- Foto profil.
- Nama lengkap.
- Email.
- Nomor telepon.
- Alamat.

Tampilkan status verifikasi, aksi Ubah Kata Sandi, dan Keluar. Berikan peringatan jika pengguna meninggalkan form dengan perubahan yang belum disimpan.

---

## 13. Spesifikasi Halaman

### 13.1 Landing Page

Bagian wajib:

1. Navbar publik.
2. Hero dengan judul **Service HP Lebih Mudah dan Terpercaya**.
3. CTA Cari Tempat Service dan Daftar Sekarang.
4. Cara kerja empat langkah.
5. Kategori kerusakan populer.
6. Mitra rekomendasi.
7. Keunggulan platform.
8. Testimoni.
9. CTA penutup.
10. Footer.

### 13.2 Registrasi

- Layout kartu form terpusat.
- Tampilkan indikator kata sandi.
- Tampilkan error inline.
- Sediakan tautan menuju login.

### 13.3 Login

- Layout sederhana dan terpercaya.
- Ilustrasi samping boleh digunakan pada desktop.
- Pada mobile, prioritaskan form.

### 13.4 Status Verifikasi

- Satu kartu status terpusat.
- Buat tiga state sebagai komponen/varian.
- Berikan tindakan yang sesuai pada setiap state.

### 13.5 Beranda

- Pencarian menjadi elemen utama.
- Pesanan aktif dan penawaran baru harus terlihat tanpa menggulir terlalu jauh.
- Rekomendasi mitra menggunakan kartu konsisten.

### 13.6 Daftar Mitra

- Desktop: sidebar filter dan grid 2–3 kolom.
- Mobile: filter melalui bottom sheet.
- Sertakan loading skeleton dan no-result state.

### 13.7 Detail Mitra

- Gunakan layout dua kolom pada desktop.
- Panel Ajukan Service dapat dibuat sticky.
- Gunakan satu kolom pada mobile.

### 13.8 Form Permintaan

- Tampilkan mitra yang dipilih di bagian atas.
- Bagi form menjadi tiga section.
- Tombol utama: Lanjutkan ke Ringkasan.

### 13.9 Ringkasan Permintaan

- Gunakan kartu terstruktur.
- Tombol Ubah Data bersifat outlined.
- Tombol Kirim Permintaan bersifat primary.

### 13.10 Detail Penawaran

- Harga adalah informasi paling menonjol.
- Setujui menjadi aksi utama.
- Tolak menjadi aksi danger/outlined.

### 13.11 Status dan Detail Pesanan

- Timeline adalah elemen utama.
- Tampilkan panel status dan instruksi berikutnya.
- Tampilkan informasi pembayaran tunai hanya sebagai informasi.

### 13.12 Rating dan Ulasan

- Bintang dapat dipilih.
- Tampilkan label arti nilai.
- Tombol submit hanya aktif jika rating dipilih.

### 13.13 Riwayat Service

- Gunakan tab/filter status.
- Sediakan empty state untuk setiap filter tanpa data.

### 13.14 Detail Riwayat

- Bersifat read-only.
- Beri Ulasan hanya tampil jika syarat terpenuhi.

### 13.15 Notifikasi

- Notifikasi yang belum dibaca lebih menonjol.
- Klik notifikasi mengarah ke pesanan terkait.

### 13.16 Profil

- Sediakan mode lihat dan edit.
- Gunakan modal konfirmasi untuk keluar.

---

## 14. State Pesanan dan Transisi

| Urutan | Status | Pemicu | Aktor | Tindakan pelanggan |
|---:|---|---|---|---|
| 1 | Menunggu Penawaran | Permintaan dikirim | Sistem | Menunggu |
| 2 | Menunggu Persetujuan Pelanggan | Mitra mengirim penawaran | Mitra | Setujui atau tolak |
| 3 | Masuk Antrean | Penawaran disetujui | Pelanggan | Menunggu |
| 4 | Sedang Diproses | Pengerjaan dimulai | Mitra | Memantau |
| 5 | Service Selesai | Perbaikan selesai | Mitra | Datang ke lokasi |
| 6 | Menunggu Pembayaran Tunai | HP siap diambil | Mitra/Sistem | Bayar tunai di lokasi |
| 7 | Pembayaran Diterima | Uang tunai diterima | Mitra | Menunggu penyelesaian |
| 8 | Pesanan Selesai | Transaksi ditutup | Mitra/Sistem | Beri ulasan |

Status terminal alternatif:

- **Ditolak:** pelanggan menolak penawaran.
- **Dibatalkan:** pelanggan membatalkan sebelum penawaran disetujui.

Jangan mengizinkan transisi acak yang melompati urutan utama pada prototipe.

---

## 15. Aturan Bisnis

| ID | Aturan |
|---|---|
| BR-01 | Akun harus terverifikasi sebelum pelanggan dapat mengajukan service. |
| BR-02 | Satu permintaan hanya ditujukan kepada satu mitra. |
| BR-03 | Harga ditentukan melalui penawaran mitra. |
| BR-04 | Pengerjaan hanya dimulai setelah penawaran disetujui. |
| BR-05 | Penawaran yang ditolak tidak masuk antrean pengerjaan. |
| BR-06 | Pembatalan oleh pelanggan hanya tersedia sebelum penawaran disetujui. |
| BR-07 | Pembayaran hanya tunai di lokasi mitra. |
| BR-08 | Konfirmasi pembayaran hanya dilakukan oleh mitra. |
| BR-09 | Ulasan hanya tersedia untuk pesanan selesai. |
| BR-10 | Satu pesanan hanya dapat memiliki satu ulasan pelanggan. |
| BR-11 | Data harga menggunakan format Rupiah Indonesia. |
| BR-12 | Tanggal dan waktu menggunakan format lokal Indonesia. |

---

## 16. Model Data Frontend

Gunakan struktur berikut sebagai acuan data mock. Nama properti boleh menyesuaikan gaya proyek.

### Customer

```ts
type Customer = {
  id: string;
  fullName: string;
  email: string;
  phone: string;
  address: string;
  avatarUrl?: string;
  verificationStatus: "pending" | "approved" | "rejected";
  rejectionReason?: string;
};
```

### RepairPartner

```ts
type RepairPartner = {
  id: string;
  name: string;
  description: string;
  address: string;
  phone: string;
  rating: number;
  reviewCount: number;
  verified: boolean;
  isOpen: boolean;
  openingHours: string;
  services: string[];
  imageUrl?: string;
  galleryUrls?: string[];
};
```

### ServiceRequest

```ts
type OrderStatus =
  | "waiting_offer"
  | "waiting_customer_approval"
  | "queued"
  | "in_progress"
  | "service_completed"
  | "waiting_cash_payment"
  | "payment_received"
  | "completed"
  | "rejected"
  | "cancelled";

type ServiceRequest = {
  id: string;
  customerId: string;
  partnerId: string;
  phoneBrand: string;
  phoneModel: string;
  damageCategory: string;
  damageDescription: string;
  damageDuration?: string;
  phoneCondition?: string;
  imageUrls: string[];
  additionalNotes?: string;
  status: OrderStatus;
  createdAt: string;
};
```

### Offer

```ts
type Offer = {
  id: string;
  orderId: string;
  inspectionResult: string;
  price: number;
  estimatedDuration: string;
  partnerNote?: string;
  status: "pending" | "accepted" | "rejected";
};
```

### StatusEvent

```ts
type StatusEvent = {
  id: string;
  orderId: string;
  status: OrderStatus;
  label: string;
  note?: string;
  timestamp: string;
};
```

### Review

```ts
type Review = {
  id: string;
  orderId: string;
  customerId: string;
  partnerId: string;
  rating: 1 | 2 | 3 | 4 | 5;
  comment: string;
  createdAt: string;
};
```

### Notification

```ts
type Notification = {
  id: string;
  title: string;
  message: string;
  type: "offer" | "progress" | "payment" | "completed" | "review";
  orderId?: string;
  read: boolean;
  createdAt: string;
};
```

---

## 17. Data Mock Konsisten

Gunakan data berikut pada seluruh halaman:

| Data | Nilai |
|---|---|
| Pelanggan | Budi Santoso |
| Email | budi.santoso@example.com |
| Nomor telepon | 0812-3456-7890 |
| Mitra | Dandanin Cell Purwokerto |
| Rating mitra | 4,8 dari 126 ulasan |
| HP | Samsung Galaxy A54 |
| Kerusakan | LCD pecah dan layar tidak merespons |
| Nomor pesanan | SRV-2026-00125 |
| Harga penawaran | Rp350.000 |
| Estimasi | 2–3 hari kerja |
| Pembayaran | Tunai di lokasi mitra |

Tambahkan 5–8 mitra mock lain dengan nama, rating, lokasi, dan layanan yang berbeda untuk halaman daftar.

---

## 18. Design System

### 18.1 Warna

| Token | Hex | Penggunaan |
|---|---:|---|
| Primary | `#2563EB` | Tombol utama, menu aktif, tautan, focus ring |
| Secondary | `#0D9488` | Progres positif dan service selesai |
| Tertiary | `#F59E0B` | Rating, status menunggu, perhatian |
| Neutral | `#0F172A` | Judul dan teks utama |
| Background | `#F8FAFC` | Latar halaman |
| Surface | `#FFFFFF` | Kartu, modal, navbar |
| Primary soft | `#EFF6FF` | Latar informasi biru |
| Secondary soft | `#F0FDFA` | Latar status positif |
| Tertiary soft | `#FFFBEB` | Latar status menunggu |
| Border | `#E2E8F0` | Border dan divider |
| Text secondary | `#64748B` | Teks sekunder |
| Disabled | `#CBD5E1` | Komponen nonaktif |
| Success | `#16A34A` | Berhasil dan pembayaran diterima |
| Danger | `#DC2626` | Error, tolak, dan batal |

### 18.2 Tipografi

- Headline: **Plus Jakarta Sans**.
- Body, label, dan tombol: **Inter**.
- Display: 48/56 px, bold.
- H1: 36/44 px, bold.
- H2: 28/36 px, bold.
- H3: 22/30 px, semibold.
- Body: 16/24 px, regular.
- Body small: 14/20 px, regular.
- Caption: 12/16 px, medium.

### 18.3 Bentuk dan spasi

- Spacing menggunakan kelipatan 4 px.
- Radius tombol/input: 8 px.
- Radius kartu: 12–16 px.
- Radius modal: 16 px.
- Tinggi minimum tombol/input: 44 px.
- Bayangan kartu lembut, tidak berlebihan.
- Lebar konten desktop maksimal 1200 px.

### 18.4 Tombol

- Primary: biru dengan teks putih.
- Secondary: biru lembut dengan teks biru.
- Outlined: putih, border abu-abu, teks navy.
- Inverted: navy dengan teks putih.
- Danger: merah atau outlined merah.
- Buat varian default, hover, focus, active, disabled, dan loading.

### 18.5 Status

| Status | Tampilan |
|---|---|
| Menunggu Penawaran | Amber soft + ikon jam + teks amber gelap |
| Menunggu Persetujuan | Biru soft + ikon dokumen + teks biru |
| Masuk Antrean | Biru soft + ikon antrean + teks biru |
| Sedang Diproses | Teal soft + ikon alat + teks teal |
| Service Selesai | Teal soft + ikon centang + teks teal |
| Menunggu Pembayaran Tunai | Amber soft + ikon uang tunai + teks amber gelap |
| Pembayaran Diterima | Hijau soft + ikon centang + teks hijau |
| Pesanan Selesai | Hijau/teal soft + ikon selesai + teks hijau |
| Ditolak/Dibatalkan | Merah soft + ikon silang + teks merah |

---

## 19. Komponen yang Harus Dibuat

- PublicNavbar.
- CustomerNavbar.
- MobileBottomNavigation.
- PageContainer.
- SectionHeader.
- Button beserta seluruh variannya.
- TextInput.
- PasswordInput.
- TextArea.
- Select.
- Checkbox.
- SearchInput.
- FileUpload dengan preview.
- PartnerCard.
- OrderCard.
- OfferCard.
- StatusBadge.
- StatusTimeline.
- RatingStars.
- ReviewCard.
- NotificationItem.
- Tabs/FilterTabs.
- Modal konfirmasi.
- Toast berhasil dan error.
- EmptyState.
- ErrorState.
- LoadingSkeleton.
- ProfileDropdown.
- Footer.

Komponen harus reusable dan tidak diduplikasi pada setiap halaman.

---

## 20. State Antarmuka yang Wajib

Setiap fitur yang relevan harus memiliki:

- Default.
- Hover.
- Focus.
- Active.
- Disabled.
- Loading.
- Success.
- Error.
- Empty.

State khusus yang harus diperlihatkan:

- Hasil pencarian mitra kosong.
- Belum ada pesanan aktif.
- Tidak ada riwayat pada filter tertentu.
- Belum ada notifikasi.
- Gagal memuat data.
- Form memiliki input tidak valid.
- Permintaan berhasil dikirim.
- Penawaran berhasil disetujui.
- Penawaran berhasil ditolak.
- Ulasan berhasil dikirim.

---

## 21. Responsivitas

### Desktop

- 12-column grid.
- Konten maksimal 1200 px.
- Daftar mitra menggunakan sidebar filter dan grid kartu.
- Detail mitra dan pesanan boleh menggunakan dua kolom.
- Navbar berada di atas dan dapat sticky.

### Tablet

- 8-column grid.
- Sidebar dapat berubah menjadi drawer.
- Grid kartu menjadi dua kolom.

### Mobile

- 4-column grid.
- Margin horizontal 16 px.
- Kartu menggunakan satu kolom.
- Filter menggunakan bottom sheet.
- Timeline menjadi vertikal.
- Tindakan utama boleh sticky di bawah.
- Gunakan bottom navigation.
- Pastikan dapat digunakan pada lebar 360 px.

---

## 22. Aksesibilitas

- Gunakan elemen HTML semantik.
- Seluruh interaksi dapat digunakan dengan keyboard.
- Focus ring harus terlihat jelas.
- Target klik/sentuh minimal 44 × 44 px.
- Label harus terhubung ke input.
- Gambar informatif memiliki alt text.
- Ikon dekoratif disembunyikan dari screen reader.
- Modal mengunci fokus dan dapat ditutup dengan Escape.
- Jangan menggunakan warna sebagai satu-satunya penanda status.
- Jaga kontras teks sesuai WCAG AA.
- Pesan error harus spesifik dan membantu pengguna memperbaiki input.

---

## 23. Perilaku dan Interaksi Prototipe

- Semua tautan navigasi harus berfungsi.
- Tombol CTA harus membuka halaman atau modal yang tepat.
- Filter mitra harus mengubah daftar mock secara langsung.
- Search dapat memfilter berdasarkan nama, lokasi, atau layanan.
- Preview foto menggunakan object URL lokal dan dapat dihapus.
- Persetujuan penawaran memperbarui state mock ke Masuk Antrean.
- Penolakan penawaran memperbarui state mock ke Ditolak.
- Timeline dapat menggunakan data berbeda untuk memperlihatkan beberapa state.
- Tandai semua sudah dibaca memperbarui notifikasi mock.
- Edit profil memperbarui data lokal selama sesi.
- Data boleh disimpan dalam state atau localStorage agar demo terasa konsisten.
- Refresh tidak wajib mempertahankan semua perubahan kecuali localStorage dipilih.

---

## 24. Pesan dan Microcopy Utama

Gunakan bahasa Indonesia yang ramah, singkat, dan langsung.

| Konteks | Copy |
|---|---|
| Hero | Service HP Lebih Mudah dan Terpercaya |
| Search | Cari tempat service atau jenis kerusakan |
| CTA utama | Cari Tempat Service |
| Pengajuan | Ajukan Service |
| Ringkasan | Periksa kembali data sebelum mengirim permintaan. |
| Berhasil mengirim | Permintaan service berhasil dikirim. |
| Menunggu penawaran | Mitra sedang memeriksa permintaan Anda. |
| Penawaran baru | Mitra telah memberikan penawaran harga. |
| Service selesai | HP Anda telah selesai diperbaiki. |
| Pembayaran tunai | Silakan datang ke lokasi mitra untuk mengambil HP dan melakukan pembayaran tunai. |
| Empty order | Belum ada service yang sedang berlangsung. |
| Empty search | Mitra yang Anda cari belum ditemukan. Coba ubah kata kunci atau filter. |
| Review success | Terima kasih, ulasan Anda berhasil dikirim. |

---

## 25. Kebutuhan Nonfungsional

### NFR-01 Performa

- Hindari aset gambar berukuran sangat besar.
- Gunakan lazy loading untuk galeri dan daftar gambar.
- Hindari library berat jika tidak diperlukan.
- Interaksi mock harus terasa responsif.

### NFR-02 Konsistensi

- Gunakan komponen dan token desain yang sama di seluruh halaman.
- Jangan menulis warna dan ukuran berulang jika dapat dibuat sebagai token.
- Gunakan satu gaya ikon, misalnya Lucide.

### NFR-03 Maintainability

- Pisahkan halaman, komponen, mock data, tipe, dan utilitas.
- Hindari satu file aplikasi yang terlalu besar.
- Gunakan nama komponen dan variabel yang jelas.
- Jangan menduplikasi data mitra dan pesanan pada banyak file.

### NFR-04 Keamanan demo

- Jangan memasukkan credential, API key, atau data pribadi nyata.
- Semua data contoh harus fiktif.
- File upload hanya untuk preview lokal.

---

## 26. Panduan Implementasi untuk OpenCode

### 26.1 Prioritas

1. Bangun fondasi design system dan layout.
2. Buat komponen reusable.
3. Buat mock data dan tipe.
4. Implementasikan rute publik.
5. Implementasikan rute pelanggan.
6. Hubungkan seluruh alur utama.
7. Tambahkan state loading, error, dan empty.
8. Lakukan pemeriksaan responsif dan aksesibilitas.

### 26.2 Ketentuan teknis

- Jika repository sudah memiliki framework dan pola komponen, pertahankan stack tersebut.
- Jika project masih kosong, gunakan default berikut:
  - React.
  - Vite.
  - TypeScript.
  - Tailwind CSS.
  - React Router.
  - Lucide React untuk ikon.
- Gunakan font Plus Jakarta Sans dan Inter.
- Gunakan data mock lokal; jangan membuat backend.
- Jangan menambahkan alur atau halaman pembayaran online.
- Jangan membuat dashboard admin atau mitra.
- Jangan mengubah palet warna utama.
- Jangan menggunakan lorem ipsum; gunakan copy Bahasa Indonesia yang realistis.

### 26.3 Struktur folder yang disarankan

```text
src/
├── components/
│   ├── common/
│   ├── layout/
│   ├── partner/
│   ├── order/
│   └── feedback/
├── pages/
│   ├── public/
│   └── customer/
├── data/
│   └── mockData.ts
├── types/
│   └── index.ts
├── hooks/
├── utils/
├── styles/
└── routes/
```

Struktur boleh menyesuaikan repository, tetapi pemisahan tanggung jawab harus dipertahankan.

---

## 27. Acceptance Criteria per Alur

### AC-01 Registrasi dan verifikasi

- Pengguna dapat membuka registrasi dari landing page.
- Form menampilkan validasi wajib dan format.
- Submit berhasil menuju status Menunggu Verifikasi.
- State Disetujui memiliki tombol menuju login/beranda.
- State Ditolak menampilkan alasan.

### AC-02 Mencari mitra

- Pengguna dapat mencari berdasarkan nama atau layanan.
- Filter mengubah hasil mock.
- Kartu mitra menampilkan informasi wajib.
- Detail mitra dapat dibuka.
- Ajukan Service mengarah ke form mitra yang benar.

### AC-03 Mengirim permintaan

- Form memiliki tiga kelompok informasi.
- Foto dapat dipreview dan dihapus.
- Data tampil kembali pada halaman ringkasan.
- Pengguna dapat mengubah data.
- Submit menampilkan modal berhasil dan nomor pesanan.

### AC-04 Menanggapi penawaran

- Detail penawaran menampilkan harga dan estimasi dengan jelas.
- Setujui dan Tolak memiliki modal konfirmasi.
- Setujui mengubah status menjadi Masuk Antrean.
- Tolak mengubah status menjadi Ditolak.

### AC-05 Memantau pengerjaan

- Seluruh delapan status utama tersedia.
- Status aktif terlihat jelas.
- Timeline vertikal berfungsi pada mobile.
- Halaman menampilkan harga, estimasi, dan pembaruan mitra.

### AC-06 Pembayaran tunai

- Saat service selesai, instruksi pengambilan dan pembayaran tunai terlihat.
- Tidak ada halaman atau kontrol pembayaran online.
- Pelanggan tidak dapat mengonfirmasi pembayaran sendiri.
- State Pembayaran Diterima dapat ditampilkan sebagai update mitra.

### AC-07 Ulasan dan riwayat

- Ulasan hanya tersedia ketika pesanan selesai.
- Rating wajib dipilih sebelum submit.
- Ulasan berhasil tampil pada detail riwayat.
- Filter riwayat menampilkan data sesuai status.

### AC-08 Responsivitas

- Semua halaman utama dapat digunakan pada 360 px.
- Tidak terdapat horizontal overflow yang tidak disengaja.
- Navigasi desktop berubah menjadi navigasi mobile.
- Tombol dan input memenuhi ukuran sentuh minimum.

---

## 28. Definition of Done

Implementasi desain dianggap selesai jika:

- Seluruh 16 halaman telah dibuat.
- Semua rute dapat dibuka tanpa error.
- Alur utama dari landing page hingga ulasan dapat diklik end-to-end.
- Komponen reusable digunakan secara konsisten.
- Semua data mock konsisten.
- Design system mengikuti palet dan tipografi yang ditentukan.
- State loading, error, empty, success, disabled, dan konfirmasi tersedia.
- Pembayaran tetap tunai dan tidak ada fitur pembayaran online.
- Tampilan telah diperiksa pada desktop, tablet, dan mobile.
- Tidak ada error penting pada console.
- Tidak ada link atau CTA utama yang mati.
- Aksesibilitas dasar dan focus state telah diterapkan.

---

## 29. Instruksi Akhir untuk OpenCode

Bangun website pelanggan Dandanin HP berdasarkan PRD ini secara bertahap sampai seluruh alur selesai. Prioritaskan pengalaman pengguna, konsistensi visual, responsivitas, dan navigasi yang berfungsi. Gunakan data mock yang realistis dan jangan menambahkan fitur di luar ruang lingkup. Jika ada detail kecil yang tidak disebutkan, pilih solusi yang paling sederhana dan tetap konsisten dengan aturan bisnis serta design system dalam dokumen ini.

