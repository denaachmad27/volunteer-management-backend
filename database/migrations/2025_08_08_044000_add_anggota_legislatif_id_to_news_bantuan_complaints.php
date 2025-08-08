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
        Schema::table('news', function (Blueprint $table) {
            $table->unsignedBigInteger('anggota_legislatif_id')->nullable()->after('created_by');
            $table->foreign('anggota_legislatif_id')->references('id')->on('anggota_legislatifs')->onDelete('cascade');
        });

        Schema::table('bantuan_sosials', function (Blueprint $table) {
            $table->unsignedBigInteger('anggota_legislatif_id')->nullable()->after('kuota_terpakai');
            $table->foreign('anggota_legislatif_id')->references('id')->on('anggota_legislatifs')->onDelete('cascade');
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->unsignedBigInteger('anggota_legislatif_id')->nullable()->after('user_id');
            $table->foreign('anggota_legislatif_id')->references('id')->on('anggota_legislatifs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign(['anggota_legislatif_id']);
            $table->dropColumn('anggota_legislatif_id');
        });

        Schema::table('bantuan_sosials', function (Blueprint $table) {
            $table->dropForeign(['anggota_legislatif_id']);
            $table->dropColumn('anggota_legislatif_id');
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['anggota_legislatif_id']);
            $table->dropColumn('anggota_legislatif_id');
        });
    }
};
