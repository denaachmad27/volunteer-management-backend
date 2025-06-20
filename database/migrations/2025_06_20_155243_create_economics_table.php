<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('economics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('penghasilan_bulanan', 12, 2);
            $table->decimal('pengeluaran_bulanan', 12, 2);
            $table->enum('status_rumah', ['Milik Sendiri', 'Sewa', 'Kontrak', 'Menumpang', 'Dinas']);
            $table->string('jenis_rumah');
            $table->boolean('punya_kendaraan')->default(false);
            $table->string('jenis_kendaraan')->nullable();
            $table->boolean('punya_tabungan')->default(false);
            $table->decimal('jumlah_tabungan', 12, 2)->nullable();
            $table->boolean('punya_hutang')->default(false);
            $table->decimal('jumlah_hutang', 12, 2)->nullable();
            $table->text('sumber_penghasilan_lain')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('economics');
    }
};