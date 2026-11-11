<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananAktif extends Model
{
    protected $table = 'pesanan_aktif';
    protected $primaryKey = 'id_pesanan';
    public $timestamps = false;

    protected $fillable = [
        'id_penawaran', 'id_permintaan', 'id_pelanggan', 'id_mitra',
        'status_pengerjaan', 'catatan_perkembangan',
        'waktu_dibuat', 'waktu_update_terakhir',
    ];

    protected function casts(): array
    {
        return [
            'waktu_dibuat' => 'datetime',
            'waktu_update_terakhir' => 'datetime',
        ];
    }

    public function penawaran()
    {
        return $this->belongsTo(PenawaranMitra::class, 'id_penawaran', 'id_penawaran');
    }

    public function permintaan()
    {
        return $this->belongsTo(PermintaanService::class, 'id_permintaan', 'id_permintaan');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function mitra()
    {
        return $this->belongsTo(MitraService::class, 'id_mitra', 'id_mitra');
    }

    public function pembayaran()
    {
        return $this->hasOne(PembayaranTunai::class, 'id_pesanan', 'id_pesanan');
    }

    public function ulasan()
    {
        return $this->hasOne(Ulasan::class, 'id_pesanan', 'id_pesanan');
    }

    public function riwayat()
    {
        return $this->hasMany(RiwayatStatusPesanan::class, 'id_pesanan', 'id_pesanan')
                    ->orderBy('waktu');
    }
}
