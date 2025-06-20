<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Tampilkan profile user
     */
    public function show(Request $request)
    {
        $profile = $request->user()->profile;

        if (!$profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $profile
        ]);
    }

    /**
     * Buat atau update profile
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik' => 'required|string|size:16|unique:profiles,nik,' . $request->user()->profile?->id,
            'nama_lengkap' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date|before:today',
            'alamat' => 'required|string',
            'kelurahan' => 'required|string|max:255',
            'kecamatan' => 'required|string|max:255',
            'kota' => 'required|string|max:255',
            'provinsi' => 'required|string|max:255',
            'kode_pos' => 'required|string|max:10',
            'agama' => 'required|in:Islam,Kristen,Katolik,Hindu,Buddha,Konghucu',
            'status_pernikahan' => 'required|in:Belum Menikah,Menikah,Cerai,Janda/Duda',
            'pendidikan_terakhir' => 'required|in:SD,SMP,SMA,D3,S1,S2,S3',
            'pekerjaan' => 'required|string|max:255',
            'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['foto_profil']);
        $data['user_id'] = $request->user()->id;

        // Handle upload foto
        if ($request->hasFile('foto_profil')) {
            // Hapus foto lama jika ada
            if ($request->user()->profile && $request->user()->profile->foto_profil) {
                Storage::delete('public/' . $request->user()->profile->foto_profil);
            }

            $file = $request->file('foto_profil');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('profile_photos', $filename, 'public');
            $data['foto_profil'] = $path;
        }

        // Update atau create profile
        $profile = Profile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Profile saved successfully',
            'data' => $profile
        ]);
    }

    /**
     * Update foto profil saja
     */
    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foto_profil' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = $request->user()->profile;
        if (!$profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profile not found'
            ], 404);
        }

        // Hapus foto lama
        if ($profile->foto_profil) {
            Storage::delete('public/' . $profile->foto_profil);
        }

        // Upload foto baru
        $file = $request->file('foto_profil');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('profile_photos', $filename, 'public');

        $profile->update(['foto_profil' => $path]);

        return response()->json([
            'status' => 'success',
            'message' => 'Photo updated successfully',
            'data' => $profile
        ]);
    }
}