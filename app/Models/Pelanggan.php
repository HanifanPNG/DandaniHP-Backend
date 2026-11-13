<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Pelanggan extends Authenticatable
{
    use HasApiTokens;
    
    protected $table = 'pelanggan';
    protected $primaryKey = 'id_pelanggan';
    public $timestamps = false;

    protected $fillable = [
        'nama_lengkap', 'email', 'nomor_telepon', 'alamat',
        'kata_sandi', 'foto_profil', 'status_verifikasi', 'alasan_penolakan',
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

    public function permintaanService()
    {
        return $this->hasMany(PermintaanService::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function pesananAktif()
    {
        return $this->hasMany(PesananAktif::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function ulasan()
    {
        return $this->hasMany(Ulasan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function notifikasi()
    {
        return $this->hasMany(Notifikasi::class, 'id_pelanggan', 'id_pelanggan');
    }
}
