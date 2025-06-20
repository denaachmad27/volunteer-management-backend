<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('no_tiket')->unique();
            $table->string('judul');
            $table->enum('kategori', ['Teknis', 'Pelayanan', 'Bantuan', 'Saran', 'Lainnya']);
            $table->text('deskripsi');
            $table->enum('prioritas', ['Rendah', 'Sedang', 'Tinggi', 'Urgent'])->default('Sedang');
            $table->enum('status', ['Baru', 'Diproses', 'Selesai', 'Ditutup'])->default('Baru');
            $table->text('respon_admin')->nullable();
            $table->timestamp('tanggal_respon')->nullable();
            $table->integer('rating')->nullable(); // Rating 1-5
            $table->text('feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};