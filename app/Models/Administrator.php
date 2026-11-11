<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Administrator extends Authenticatable
{
    protected $table = 'administrator';
    protected $primaryKey = 'id_admin';
    public $timestamps = false;

    protected $fillable = ['nama_lengkap', 'email', 'kata_sandi'];
    protected $hidden = ['kata_sandi'];

    protected function casts(): array
    {
        return ['kata_sandi' => 'hashed'];
    }

    // Agar Auth Laravel membaca kolom kata_sandi, bukan "password"
    public function getAuthPassword()
    {
        return $this->kata_sandi;
    }
}
