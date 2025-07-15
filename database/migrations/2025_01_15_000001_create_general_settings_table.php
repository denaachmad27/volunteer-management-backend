<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('Admin Panel Bantuan Sosial');
            $table->text('site_description')->nullable();
            $table->string('site_url')->nullable();
            $table->string('admin_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('organization')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('language')->default('id');
            $table->json('social_media')->nullable(); // For future social media links
            $table->json('additional_settings')->nullable(); // For extensibility
            $table->timestamps();
        });

        // Insert default settings
        DB::table('general_settings')->insert([
            'site_name' => 'Admin Panel Bantuan Sosial',
            'site_description' => 'Sistem administrasi bantuan sosial untuk pengelolaan program bantuan masyarakat',
            'site_url' => 'https://bantuan-sosial.gov.id',
            'admin_email' => 'admin@bantuan-sosial.gov.id',
            'contact_phone' => '+62 21 1234 5678',
            'address' => 'Jl. Raya Bantuan Sosial No. 123, Jakarta Pusat',
            'organization' => 'Dinas Sosial DKI Jakarta',
            'timezone' => 'Asia/Jakarta',
            'language' => 'id',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('general_settings');
    }
};