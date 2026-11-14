<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ulasan extends Model
{
    protected $table = 'ulasan';
    protected $primaryKey = 'id_ulasan';
    public $timestamps = false;

    protected $fillable = [
        'id_pesanan', 'id_pelanggan', 'id_mitra',
        'rating_bintang', 'komentar_ulasan', 'foto_ulasan', 'waktu_ulasan',
    ];

    protected function casts(): array
    {
        return ['waktu_ulasan' => 'datetime'];
    }

    public function pesanan()
    {
        return $this->belongsTo(PesananAktif::class, 'id_pesanan', 'id_pesanan');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function mitra()
    {
        return $this->belongsTo(MitraService::class, 'id_mitra', 'id_mitra');
    }
}
