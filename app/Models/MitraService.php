<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class MitraService extends Authenticatable
{
    use HasApiTokens;
    
    protected $table = 'mitra_service';
    protected $primaryKey = 'id_mitra';
    public $timestamps = false;

    protected $fillable = [
        'nama_pemilik', 'nama_usaha', 'email', 'kata_sandi', 'nomor_telepon',
        'alamat_usaha', 'deskripsi_usaha', 'jam_operasional', 'jam_buka', 'jam_tutup',
        'foto_toko', 'latitude', 'longitude', 'rating_rata_rata', 'status_verifikasi',
    ];

    protected $hidden = ['kata_sandi'];

    protected function casts(): array
    {
        return ['kata_sandi' => 'hashed'];
    }

    public function getAuthPassword()
    {
        return $this->kata_sandi;
    }

    public function penawaran()
    {
        return $this->hasMany(PenawaranMitra::class, 'id_mitra', 'id_mitra');
    }

    public function pesananAktif()
    {
        return $this->hasMany(PesananAktif::class, 'id_mitra', 'id_mitra');
    }

    public function ulasan()
    {
        return $this->hasMany(Ulasan::class, 'id_mitra', 'id_mitra');
    }

    public function layanan()
    {
        return $this->hasMany(LayananMitra::class, 'id_mitra', 'id_mitra');
    }

    public function galeri()
    {
        return $this->hasMany(GaleriMitra::class, 'id_mitra', 'id_mitra');
    }

    /**
     * Tiga ulasan terbaru untuk halaman detail mitra.
     *
     * Catatan: limit di eager load berlaku untuk keseluruhan hasil,
     * jadi relasi ini hanya aman dipakai untuk satu mitra (halaman detail).
     */
    public function ulasanTerbaru()
    {
        return $this->hasMany(Ulasan::class, 'id_mitra', 'id_mitra')
            ->orderByDesc('waktu_ulasan')
            ->limit(3);
    }

    /** Kolom database "HH:MM:SS" dirapikan jadi "HH:MM" untuk API. */
    public function jamBukaFormat(): ?string
    {
        return $this->jam_buka ? substr($this->jam_buka, 0, 5) : null;
    }

    public function jamTutupFormat(): ?string
    {
        return $this->jam_tutup ? substr($this->jam_tutup, 0, 5) : null;
    }

    /**
     * Apakah mitra sedang buka saat request masuk?
     * Dihitung dari jam WIB, bukan disimpan di database.
     */
    public function isOpen(): bool
    {
        if (! $this->jam_buka || ! $this->jam_tutup) {
            return false;
        }

        $sekarang = now('Asia/Jakarta')->format('H:i');

        return $sekarang >= $this->jamBukaFormat()
            && $sekarang <= $this->jamTutupFormat();
    }
}
