<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranMitra extends Model
{
    protected $table = 'penawaran_mitra';
    protected $primaryKey = 'id_penawaran';
    public $timestamps = false;

    protected $fillable = [
        'id_permintaan', 'id_mitra', 'harga_penawaran', 'estimasi_hari',
        'estimasi_waktu', 'catatan_pemeriksaan', 'alasan_penolakan',
        'waktu_penawaran', 'status_penawaran',
    ];

    protected function casts(): array
    {
        return ['waktu_penawaran' => 'datetime'];
    }

    public function permintaan()
    {
        return $this->belongsTo(PermintaanService::class, 'id_permintaan', 'id_permintaan');
    }

    public function mitra()
    {
        return $this->belongsTo(MitraService::class, 'id_mitra', 'id_mitra');
    }

    public function pesanan()
    {
        return $this->hasOne(PesananAktif::class, 'id_penawaran', 'id_penawaran');
    }
}
