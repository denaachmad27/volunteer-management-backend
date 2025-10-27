<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warga_binaan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('relawan_id'); // Foreign key ke users table
            $table->string('no_kta')->unique()->nullable();
            $table->string('nama');
            $table->date('tanggal_lahir');
            $table->integer('usia');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->text('alamat');
            $table->string('kecamatan');
            $table->string('kelurahan');
            $table->string('rt', 10);
            $table->string('rw', 10);
            $table->string('no_hp', 20)->nullable();
            $table->enum('status_kta', ['Sudah punya', 'Belum punya'])->default('Belum punya');
            $table->enum('hasil_verifikasi', [
                'Bersedia ikut UPA 1 kali per bulan',
                'Bersedia ikut UPA 1 kali per minggu',
                'Tidak bersedia'
            ])->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint
            $table->foreign('relawan_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('relawan_id');
            $table->index('no_kta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warga_binaan');
    }
};
