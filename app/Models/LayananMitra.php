<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LayananMitra extends Model
{
    protected $table = 'layanan_mitra';
    protected $primaryKey = 'id_layanan';
    public $timestamps = false;

    protected $fillable = ['id_mitra', 'nama_layanan'];

    public function mitra()
    {
        return $this->belongsTo(MitraService::class, 'id_mitra', 'id_mitra');
    }
}
