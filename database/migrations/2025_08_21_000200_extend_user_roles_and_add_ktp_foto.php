<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend users.role enum to include aleg, relawan, warga
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'admin_aleg', 'aleg', 'relawan', 'user', 'warga'])
                  ->default('user')
                  ->after('password');
        });

        // Add ktp_foto to profiles (optional)
        Schema::table('profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profiles', 'ktp_foto')) {
                $table->string('ktp_foto')->nullable()->after('foto_profil');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'ktp_foto')) {
                $table->dropColumn('ktp_foto');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'admin_aleg', 'user'])
                  ->default('user')
                  ->after('password');
        });
    }
};

