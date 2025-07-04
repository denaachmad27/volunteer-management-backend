<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to modify the ENUM column
        DB::statement("ALTER TABLE pendaftarans MODIFY COLUMN status ENUM('Pending', 'Diproses', 'Disetujui', 'Ditolak', 'Selesai', 'Perlu Dilengkapi') DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM values
        DB::statement("ALTER TABLE pendaftarans MODIFY COLUMN status ENUM('Pending', 'Diproses', 'Disetujui', 'Ditolak', 'Selesai') DEFAULT 'Pending'");
    }
};
