<?php

namespace App\Http\Requests\Pelanggan;

use Illuminate\Foundation\Http\FormRequest;

// Validasi perubahan kata sandi pelanggan.
// - Wajib kata_sandi_lama, kata_sandi_baru (min 8 + confirmed)
// - Kata sandi lama harus cocok dengan yang tersimpan
class UpdateKataSandiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kata_sandi_lama'   => ['required', 'string'],
            'kata_sandi_baru'   => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}