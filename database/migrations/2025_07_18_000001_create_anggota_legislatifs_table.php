<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota_legislatifs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_aleg')->unique();
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->text('alamat');
            $table->string('kelurahan');
            $table->string('kecamatan');
            $table->string('kota');
            $table->string('provinsi');
            $table->string('kode_pos');
            $table->string('no_telepon');
            $table->string('email')->unique();
            $table->string('jabatan_saat_ini');
            $table->string('partai_politik');
            $table->string('daerah_pemilihan');
            $table->text('riwayat_jabatan')->nullable();
            $table->string('foto_profil')->nullable();
            $table->enum('status', ['Aktif', 'Tidak Aktif'])->default('Aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_legislatifs');
    }
};