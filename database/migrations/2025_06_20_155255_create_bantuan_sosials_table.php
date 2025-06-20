<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bantuan_sosials', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bantuan');
            $table->text('deskripsi');
            $table->enum('jenis_bantuan', ['Uang Tunai', 'Sembako', 'Peralatan', 'Pelatihan', 'Kesehatan', 'Pendidikan']);
            $table->decimal('nominal', 12, 2)->nullable();
            $table->integer('kuota');
            $table->integer('kuota_terpakai')->default(0);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status', ['Aktif', 'Tidak Aktif', 'Selesai'])->default('Aktif');
            $table->text('syarat_bantuan');
            $table->text('dokumen_diperlukan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bantuan_sosials');
    }
};