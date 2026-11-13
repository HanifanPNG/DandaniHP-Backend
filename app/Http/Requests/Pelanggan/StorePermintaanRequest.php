<?php

namespace App\Http\Requests\Pelanggan;

use Illuminate\Foundation\Http\FormRequest;

// Validasi input pembuatan permintaan service.
//
// Dipisah dari controller supaya:
// - controller tetap pendek dan fokus pada alur,
// - aturan validasi mudah dipakai ulang/diuji,
// - pesan error otomatis 422 dengan format JSON yang seragam.
class StorePermintaanRequest extends FormRequest
{
    // Otorisasi akses sudah ditangani middleware auth:pelanggan
    // dan verified.pelanggan, jadi di sini cukup true.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merek_hp'            => ['required', 'string', 'max:50'],
            'model_hp'            => ['required', 'string', 'max:50'],
            'kategori_kerusakan'  => ['required', 'string', 'max:100'],
            'deskripsi_kerusakan' => ['required', 'string'],

            // Opsional: pelanggan mungkin belum tahu detailnya.
            'lama_kerusakan'      => ['nullable', 'string', 'max:50'],
            'kondisi_hp'          => ['nullable', 'string', 'max:100'],
            'catatan_tambahan'    => ['nullable', 'string'],

            // Pemilik permintaan WAJIB dari token, bukan dari body.
            // prohibited membuat request yang mencoba mengirimnya ditolak 422,
            // sehingga tidak ada pikiran "mungkin bisa dipalsukan".
            'id_pelanggan'        => ['prohibited'],
        ];
    }
}
