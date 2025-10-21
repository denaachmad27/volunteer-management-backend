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
        Schema::create('pokirs', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi');
            $table->string('kategori'); // Infrastruktur, Pendidikan, Kesehatan, Ekonomi, Sosial, Lingkungan
            $table->enum('prioritas', ['high', 'medium', 'low'])->default('medium');
            $table->enum('status', ['proposed', 'approved', 'in_progress', 'completed', 'rejected'])->default('proposed');
            $table->string('lokasi_pelaksanaan')->nullable();
            $table->date('target_pelaksanaan')->nullable();
            $table->foreignId('anggota_legislatif_id')->nullable()->constrained('anggota_legislatifs')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('kategori');
            $table->index('prioritas');
            $table->index('status');
            $table->index('target_pelaksanaan');
            $table->index('anggota_legislatif_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokirs');
    }
};
