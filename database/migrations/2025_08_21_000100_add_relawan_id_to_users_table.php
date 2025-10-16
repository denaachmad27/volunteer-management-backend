<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'relawan_id')) {
                $table->unsignedBigInteger('relawan_id')->nullable()->after('anggota_legislatif_id');
                $table->index('relawan_id');
                $table->foreign('relawan_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'relawan_id')) {
                $table->dropForeign(['relawan_id']);
                $table->dropIndex(['relawan_id']);
                $table->dropColumn('relawan_id');
            }
        });
    }
};

