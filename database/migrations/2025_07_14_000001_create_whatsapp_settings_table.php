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
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('session_name')->default('admin-session');
            $table->text('session_data')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_connected')->default(false);
            $table->string('qr_code')->nullable();
            $table->json('webhook_urls')->nullable();
            $table->text('default_message_template')->nullable();
            $table->json('department_mappings')->nullable(); // Mapping kategori pengaduan ke dinas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};