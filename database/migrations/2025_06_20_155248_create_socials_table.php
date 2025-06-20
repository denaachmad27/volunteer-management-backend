<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('organisasi')->nullable();
            $table->string('jabatan_organisasi')->nullable();
            $table->boolean('aktif_kegiatan_sosial')->default(false);
            $table->text('jenis_kegiatan_sosial')->nullable();
            $table->boolean('pernah_dapat_bantuan')->default(false);
            $table->text('jenis_bantuan_diterima')->nullable();
            $table->date('tanggal_bantuan_terakhir')->nullable();
            $table->text('keahlian_khusus')->nullable();
            $table->text('minat_kegiatan')->nullable();
            $table->enum('ketersediaan_waktu', ['Weekday', 'Weekend', 'Fleksibel', 'Terbatas']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socials');
    }
};