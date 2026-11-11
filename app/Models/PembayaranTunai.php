<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranTunai extends Model
{
    protected $table = 'pembayaran_tunai';
    protected $primaryKey = 'id_pembayaran';
    public $timestamps = false;

    protected $fillable = [
        'id_pesanan', 'jumlah_pembayaran', 'waktu_dibuat',
        'waktu_konfirmasi_mitra', 'status_pembayaran',
    ];

    protected function casts(): array
    {
        return [
            'waktu_dibuat' => 'datetime',
            'waktu_konfirmasi_mitra' => 'datetime',
        ];
    }

    public function pesanan()
    {
        return $this->belongsTo(PesananAktif::class, 'id_pesanan', 'id_pesanan');
    }
}
