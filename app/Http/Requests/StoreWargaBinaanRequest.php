<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWargaBinaanRequest extends FormRequest
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
        return [
            'relawan_id' => 'required|exists:users,id',
            'no_kta' => 'nullable|string|unique:warga_binaan,no_kta',
            'nama' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'usia' => 'required|integer|min:0|max:150',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'alamat' => 'required|string',
            'kecamatan' => 'required|string|max:255',
            'kelurahan' => 'required|string|max:255',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'no_hp' => 'nullable|string|max:20',
            'status_kta' => 'required|in:Sudah punya,Belum punya',
            'hasil_verifikasi' => 'nullable|in:Bersedia ikut UPA 1 kali per bulan,Bersedia ikut UPA 1 kali per minggu,Tidak bersedia',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'relawan_id.required' => 'Relawan harus dipilih',
            'relawan_id.exists' => 'Relawan tidak ditemukan',
            'no_kta.unique' => 'Nomor KTA sudah terdaftar',
            'nama.required' => 'Nama warga binaan harus diisi',
            'tanggal_lahir.required' => 'Tanggal lahir harus diisi',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid',
            'usia.required' => 'Usia harus diisi',
            'usia.integer' => 'Usia harus berupa angka',
            'jenis_kelamin.required' => 'Jenis kelamin harus dipilih',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid',
            'alamat.required' => 'Alamat harus diisi',
            'kecamatan.required' => 'Kecamatan harus diisi',
            'kelurahan.required' => 'Kelurahan harus diisi',
            'status_kta.required' => 'Status KTA harus dipilih',
            'status_kta.in' => 'Status KTA tidak valid',
            'hasil_verifikasi.in' => 'Hasil verifikasi tidak valid',
        ];
    }
}
