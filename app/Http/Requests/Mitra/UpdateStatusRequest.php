<?php

namespace App\Http\Requests\Mitra;

use App\Support\StatusPesanan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validasi perubahan status pesanan oleh mitra.
// Nilai yang tidak dikenal ditolak di sini; urutan transisinya
// dicek di PesananService.
class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_pengerjaan'    => ['required', Rule::in(StatusPesanan::DAFTAR_PENGERJAAN)],
            'catatan_perkembangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
