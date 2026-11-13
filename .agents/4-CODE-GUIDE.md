# Panduan Kode — Auth & Penemuan Mitra (M1–M2)

Dokumen ini menjelaskan **kenapa** kode ditulis seperti ini, bukan hanya **apa** isinya.
Target pembaca: kamu sendiri, beberapa minggu kemudian, saat lupa detail.

---

## 1. Peta file

| File | Tugas |
|---|---|
| `routes/api.php` | Daftar endpoint + middleware yang menjaga tiap endpoint |
| `bootstrap/app.php` | Alias middleware (`verified.pelanggan`, `verified.mitra`) |
| `config/auth.php` | Guard & provider untuk 3 aktor |
| `app/Models/Pelanggan.php` | Akun pelanggan (guard `pelanggan`) |
| `app/Models/MitraService.php` | Akun + profil mitra (guard `mitra`) |
| `app/Models/Administrator.php` | Akun admin (guard `admin`) |
| `app/Http/Controllers/Api/Auth/*` | Register/login/me/logout |
| `app/Http/Controllers/Api/Admin/VerifikasiController.php` | Admin menyetujui/menolak akun |
| `app/Http/Controllers/Api/Mitra/MitraDiscoveryController.php` | Daftar, detail, ulasan mitra |
| `app/Http/Resources/MitraResource.php` | Bentuk data mitra untuk daftar |
| `app/Http/Resources/MitraDetailResource.php` | Bentuk data mitra untuk detail |
| `app/Http/Resources/UlasanResource.php` | Bentuk data satu ulasan |
| `app/Http/Middleware/Ensure*Verified.php` | Menolak akun yang belum disetujui |
| `app/Policies/*.php` | Cek kepemilikan resource |
| `app/Providers/AppServiceProvider.php` | Mendaftarkan policy ke Gate |

### Alur satu request

```
HTTP request
  → Route (routes/api.php)
  → Middleware: auth:pelanggan → verified.pelanggan
  → Controller (validasi, query)
  → Resource (menyusun bentuk JSON)
  → response JSON
```

Middleware berjalan **berurutan dari kiri ke kanan**. Karena itu `auth` harus di depan:
`verified.*` membutuhkan `$request->user()` yang baru terisi setelah `auth`.

---

## 2. M1 — Tiga aktor, tiga guard

Satu tabel = satu model = satu provider = satu guard.

| Aktor | Tabel | Guard | Token |
|---|---|---|---|
| Pelanggan | `pelanggan` | `pelanggan` | Sanctum |
| Mitra | `mitra_service` | `mitra` | Sanctum |
| Admin | `administrator` | `admin` | Sanctum |

**Kenapa dipisah?** Token pelanggan yang dipakai di endpoint mitra menghasilkan `401`,
bukan membuka data mitra. Ini sudah diuji.

### Status verifikasi

- Registrasi selalu menyimpan `Menunggu` — nilainya **tidak** diambil dari request.
- Admin mengubahnya lewat `PATCH /api/admin/{pelanggan|mitra}/{id}/verifikasi`.
- Middleware `verified.*` menolak (`403`) semua akun yang statusnya bukan `Disetujui`.
- Login **tetap boleh** untuk akun `Menunggu`, supaya mereka bisa melihat status
  dan alasan penolakan lewat `/auth/me`.

### Policy kepemilikan

Policy menjawab: "boleh tidak user ini menyentuh baris data ini?"

```php
public function view(Pelanggan $user, PermintaanService $permintaan): bool
{
    return $user->id_pelanggan === $permintaan->id_pelanggan;
}
```

Perhatikan tipe parameter `$user`:

- `Pelanggan` → dipakai guard pelanggan.
- Di `PenawaranPolicy`/`PesananPolicy`, parameter tetap `Pelanggan`, tapi isinya
  mengecek `instanceof` karena resource yang sama bisa diakses mitra atau pelanggan.

Karena policy memakai tipe `Pelanggan`, request dari guard mitra/admin otomatis
tidak cocok. Itulah kenapa admin tidak perlu policy terpisah.

---

## 3. M2 — Penemuan mitra

### 3.1 `GET /api/mitra` — daftar

File: `MitraDiscoveryController@index`.

Query disusun bertahap:

```php
$query = MitraService::query()
    ->where('status_verifikasi', 'Disetujui') // BR-01
    ->with('layanan')                          // eager load, hindari N+1
    ->withCount('ulasan');                     // jadi ulasan_count
```

**Kenapa `with()`?** Tanpa eager load, 10 mitra = 1 query mitra + 10 query layanan.
Dengan `with('layanan')` = 2 query total.

**Kenapa `where` status di query, bukan `filter()` di PHP?** Supaya database yang
menyaring. Kalau disaring di PHP, data mitra `Menunggu` tetap terambil dari database.

#### Filter `q`

```php
$query->when($request->filled('q'), function ($q) use ($request) {
    $cari = '%'.$request->string('q').'%';

    $q->where(function ($sub) use ($cari) {
        $sub->where('nama_usaha', 'like', $cari)
            ->orWhere('alamat_usaha', 'like', $cari)
            ->orWhereHas('layanan', fn ($l) => $l->where('nama_layanan', 'like', $cari));
    });
});
```

Closure `where(function ...)` **wajib**. Tanpa itu, SQL-nya menjadi:

```sql
status = 'Disetujui' AND nama_usaha LIKE ... OR alamat_usaha LIKE ...
```

`OR` mengalahkan `AND`, sehingga mitra yang belum disetujui bisa ikut muncul.

#### Filter lain

| Parameter | Efek |
|---|---|
| `layanan=Kamera` | `whereHas('layanan', ...)` — mitra yang punya layanan itu |
| `rating_min=4.5` | `rating_rata_rata >= 4.5` |
| `buka=1` | jam sekarang (WIB) berada di antara `jam_buka`–`jam_tutup` |

`buka` memakai `now('Asia/Jakarta')` + `whereTime`, karena kolomnya bertipe `time`.

#### Sort

| `sort` | Perilaku |
|---|---|
| `rekomendasi` (default) | rating tertinggi |
| `rating` | rating tertinggi, tie-break jumlah ulasan |
| `jarak` | butuh `lat` + `lng`, Haversine, yang tanpa koordinat ditaruh paling bawah |

Rumus Haversine di SQL:

```sql
6371 * acos(
  cos(radians(?)) * cos(radians(latitude)) *
  cos(radians(longitude) - radians(?)) +
  sin(radians(?)) * sin(radians(latitude))
) AS jarak
```

Hasilnya kilometer. `?` diisi `[$lat, $lng, $lat]` berurutan.

#### Paginasi

```php
$perPage = min(max($request->integer('per_page', 10), 1), 50);
$mitra = $query->paginate($perPage)->appends($request->query());
```

- Batas 1–50 mencegah client meminta 10.000 baris sekaligus.
- `appends(...)` membuat link halaman berikutnya tetap membawa filter.

### 3.2 `GET /api/mitra/{id}` — detail

```php
$mitra = MitraService::query()
    ->where('status_verifikasi', 'Disetujui')
    ->with('layanan', 'galeri')
    ->withCount('ulasan')
    ->with('ulasanTerbaru.pelanggan:id_pelanggan,nama_lengkap')
    ->findOrFail($id);
```

- `findOrFail` setelah `where` status → mitra `Menunggu` menghasilkan **404**,
  bukan 403. Tujuannya: jangan membocorkan bahwa akun itu ada.
- `ulasanTerbaru` adalah relasi di model dengan `limit(3)`.
  Relasi ini hanya aman untuk satu mitra; kalau dipakai di daftar, limit-nya
  berlaku global (hanya 3 ulasan untuk semua mitra). Itu sebabnya
  daftar memakai `withCount`, bukan `with('ulasanTerbaru')`.

### 3.3 `GET /api/mitra/{id}/ulasan` — daftar ulasan

```php
$ulasan = $mitra->ulasan()
    ->with('pelanggan:id_pelanggan,nama_lengkap')
    ->orderByDesc('waktu_ulasan')
    ->paginate($perPage);
```

**Privasi:** yang diambil dari pelanggan hanya `id_pelanggan` dan `nama_lengkap`.
Email dan nomor telepon tidak pernah masuk eager load, jadi tidak mungkin bocor.

---

## 4. Resource: kenapa dua?

| Resource | Dipakai | Isi |
|---|---|---|
| `MitraResource` | daftar | ringkas: nama, rating, layanan, is_open, jarak |
| `MitraDetailResource` | detail | lengkap: deskripsi, kontak, galeri, lokasi, 3 ulasan |
| `UlasanResource` | daftar ulasan | satu ulasan + nama pelanggan |

Kalau satu resource dipakai untuk keduanya, daftar akan mengirim deskripsi,
galeri, dan ulasan untuk setiap mitra — boros bandwidth.

### `is_open` dan timezone (BR-12)

- `config/app.php`: `'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta')`.
- `.env`: `APP_TIMEZONE=Asia/Jakarta`.
- Tanggal dikirim ISO-8601 dengan offset, mis. `2026-11-02T10:10:00+07:00`.

`is_open` **tidak disimpan di database** karena nilainya berubah tiap menit.
Dihitung di model `MitraService::isOpen()`:

```php
$sekarang = now('Asia/Jakarta')->format('H:i');

return $sekarang >= $this->jamBukaFormat()
    && $sekarang <= $this->jamTutupFormat();
```

Kolom `time` dari MySQL berbentuk `HH:MM:SS`, sedangkan `format('H:i')` menghasilkan
`HH:MM`. Keduanya dirapikan ke `HH:MM` agar perbandingan string konsisten.

---

## 5. Tes manual cepat (curl)

```bash
# token pelanggan
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"login":"budi.santoso@example.com","kata_sandi":"password123"}' \
  | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["token"];')

# daftar
curl -s "http://localhost:8000/api/mitra?sort=rating" \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'

# detail
curl -s "http://localhost:8000/api/mitra/3" \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'

# ulasan
curl -s "http://localhost:8000/api/mitra/3/ulasan" \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
```

Ekspektasi:

| Request | Status |
|---|---|
| `GET /api/mitra/1` | `200` |
| `GET /api/mitra/10` (Menunggu) | `404` |
| `GET /api/mitra/999` | `404` |
| `GET /api/mitra/3/ulasan` | `200`, 1 ulasan |
| tanpa token | `401` |

---

## 6. Jebakan yang pernah terjadi

| Gejala | Penyebab | Perbaikan |
|---|---|---|
| `Auth guard [pelanggan] is not defined` | guard ditulis di dalam array `web` | jadikan sibling `web` |
| `The route auth/me could not be found` | URL lupa `/api` | `http://localhost:8000/api/...` |
| Register mitra error kolom | aturan `unique:` pakai tabel `mitra` | pakai `mitra_service` |
| Login admin selalu `401` | `$data` input tertimpa model | pakai variabel berbeda (`$admin`) |
| `must be of type App\Models\User` | policy hasil generator default | ganti ke `Pelanggan` |
| `syntax error, unexpected "}"` | `orderByDesc(...)` tanpa `;` | tambahkan titik koma |
| `Class "PermintaanService" not found` | route closure tanpa import | pakai FQCN atau `use` |
| Semua endpoint `200` untuk akun `Menunggu` | middleware lupa dipasang | pasang `verified.*` setelah `auth` |
| Tanggal bergeser 7 jam | app timezone `UTC` | `APP_TIMEZONE=Asia/Jakarta` |

---

## 7. Checklist saat menambah endpoint baru

- [ ] Route di grup middleware yang tepat (`auth:*`, lalu `verified.*` bila transaksi).
- [ ] Validasi input di controller/request.
- [ ] Query memakai `with()` untuk relasi yang ditampilkan.
- [ ] Resource baru bila bentuk response berbeda dari yang sudah ada.
- [ ] Jangan pernah mengembalikan `$model` langsung — selalu lewat Resource.
- [ ] Uji: tanpa token (`401`), token aktor lain (`401`/`403`), data tidak ada (`404`).
