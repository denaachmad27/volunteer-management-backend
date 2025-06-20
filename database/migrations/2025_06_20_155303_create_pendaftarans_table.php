<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bantuan_sosial_id')->constrained()->onDelete('cascade');
            $table->string('no_pendaftaran')->unique();
            $table->date('tanggal_daftar');
            $table->enum('status', ['Pending', 'Diproses', 'Disetujui', 'Ditolak', 'Selesai'])->default('Pending');
            $table->text('alasan_pengajuan');
            $table->json('dokumen_upload')->nullable(); // Menyimpan array nama file
            $table->text('catatan_admin')->nullable();
            $table->date('tanggal_persetujuan')->nullable();
            $table->date('tanggal_penyerahan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftarans');
    }
};