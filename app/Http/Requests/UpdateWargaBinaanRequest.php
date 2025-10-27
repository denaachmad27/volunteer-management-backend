<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWargaBinaanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $wargaBinaanId = $this->route('id');

        return [
            'relawan_id' => 'sometimes|exists:users,id',
            'no_kta' => 'nullable|string|unique:warga_binaan,no_kta,' . $wargaBinaanId,
            'nama' => 'sometimes|string|max:255',
            'tanggal_lahir' => 'sometimes|date',
            'usia' => 'sometimes|integer|min:0|max:150',
            'jenis_kelamin' => 'sometimes|in:Laki-laki,Perempuan',
            'alamat' => 'sometimes|string',
            'kecamatan' => 'sometimes|string|max:255',
            'kelurahan' => 'sometimes|string|max:255',
            'rt' => 'sometimes|string|max:10',
            'rw' => 'sometimes|string|max:10',
            'no_hp' => 'nullable|string|max:20',
            'status_kta' => 'sometimes|in:Sudah punya,Belum punya',
            'hasil_verifikasi' => 'nullable|in:Bersedia ikut UPA 1 kali per bulan,Bersedia ikut UPA 1 kali per minggu,Tidak bersedia',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'relawan_id.exists' => 'Relawan tidak ditemukan',
            'no_kta.unique' => 'Nomor KTA sudah terdaftar',
            'nama.string' => 'Nama harus berupa teks',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid',
            'usia.integer' => 'Usia harus berupa angka',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid',
            'status_kta.in' => 'Status KTA tidak valid',
            'hasil_verifikasi.in' => 'Hasil verifikasi tidak valid',
        ];
    }
}
