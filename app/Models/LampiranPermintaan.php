<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LampiranPermintaan extends Model
{
    protected $table = 'lampiran_permintaan';
    protected $primaryKey = 'id_lampiran';
    public $timestamps = false;

    protected $fillable = ['id_permintaan', 'path', 'waktu_unggah'];

    protected function casts(): array
    {
        return ['waktu_unggah' => 'datetime'];
    }

    public function permintaan()
    {
        return $this->belongsTo(PermintaanService::class, 'id_permintaan', 'id_permintaan');
    }
}
