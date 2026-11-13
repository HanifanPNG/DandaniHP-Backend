<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';
    protected $primaryKey = 'id_notifikasi';
    public $timestamps = false;

    protected $fillable = [
        'id_pelanggan', 'id_mitra', 'tipe', 'judul', 'pesan',
        'id_pesanan', 'dibaca', 'waktu_dibuat',
    ];

    protected function casts(): array
    {
        return [
            'dibaca' => 'boolean',
            'waktu_dibuat' => 'datetime',
        ];
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function mitra()
    {
        return $this->belongsTo(MitraService::class, 'id_mitra', 'id_mitra');
    }

    public function pesanan()
    {
        return $this->belongsTo(PesananAktif::class, 'id_pesanan', 'id_pesanan');
    }
}
