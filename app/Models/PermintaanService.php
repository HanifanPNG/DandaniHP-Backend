<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanService extends Model
{
    protected $table = 'permintaan_service';
    protected $primaryKey = 'id_permintaan';
    public $timestamps = false;

    protected $fillable = [
        'kode_pesanan', 'id_pelanggan', 'merek_hp', 'model_hp', 'kategori_kerusakan',
        'deskripsi_kerusakan', 'lama_kerusakan', 'kondisi_hp', 'catatan_tambahan',
        'waktu_dibuat', 'status_permintaan',
    ];

    protected function casts(): array
    {
        return ['waktu_dibuat' => 'datetime'];
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function penawaran()
    {
        return $this->hasMany(PenawaranMitra::class, 'id_permintaan', 'id_permintaan');
    }

    public function lampiran()
    {
        return $this->hasMany(LampiranPermintaan::class, 'id_permintaan', 'id_permintaan');
    }

    public function pesanan()
    {
        return $this->hasOne(PesananAktif::class, 'id_permintaan', 'id_permintaan');
    }
}
