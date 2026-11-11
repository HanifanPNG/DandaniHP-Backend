<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaleriMitra extends Model
{
    protected $table = 'galeri_mitra';
    protected $primaryKey = 'id_galeri';
    public $timestamps = false;

    protected $fillable = ['id_mitra', 'path'];

    public function mitra()
    {
        return $this->belongsTo(MitraService::class, 'id_mitra', 'id_mitra');
    }
}
