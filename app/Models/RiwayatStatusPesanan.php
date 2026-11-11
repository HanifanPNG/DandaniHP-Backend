<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatStatusPesanan extends Model
{
    protected $table = 'riwayat_status_pesanan';
    protected $primaryKey = 'id_riwayat';
    public $timestamps = false;

    protected $fillable = ['id_pesanan', 'status', 'catatan', 'aktor', 'waktu'];

    protected function casts(): array
    {
        return ['waktu' => 'datetime'];
    }

    public function pesanan()
    {
        return $this->belongsTo(PesananAktif::class, 'id_pesanan', 'id_pesanan');
    }
}
