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
        Schema::create('forwarding_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('email_forwarding')->default(true);
            $table->boolean('whatsapp_forwarding')->default(false);
            $table->enum('forwarding_mode', ['auto', 'manual'])->default('auto');
            $table->string('admin_email')->nullable();
            $table->string('admin_whatsapp')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('forwarding_settings')->insert([
            'email_forwarding' => true,
            'whatsapp_forwarding' => false,
            'forwarding_mode' => 'auto',
            'admin_email' => 'admin@bantuan-sosial.gov.id',
            'admin_whatsapp' => '+62 812 9999 9999',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forwarding_settings');
    }
};