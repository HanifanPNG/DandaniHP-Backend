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
}
