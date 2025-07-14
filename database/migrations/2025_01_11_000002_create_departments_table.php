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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->json('categories')->nullable(); // Store array of categories
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default departments
        DB::table('departments')->insert([
            [
                'name' => 'Dinas Sosial',
                'email' => 'dinsos@bantuan-sosial.gov.id',
                'whatsapp' => '+62 812 1111 1111',
                'categories' => json_encode(['Bantuan', 'Pelayanan']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dinas Kesehatan',
                'email' => 'dinkes@bantuan-sosial.gov.id',
                'whatsapp' => '+62 812 2222 2222',
                'categories' => json_encode(['Kesehatan']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dinas Pendidikan',
                'email' => 'disdik@bantuan-sosial.gov.id',
                'whatsapp' => '+62 812 3333 3333',
                'categories' => json_encode(['Pendidikan']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'IT Support',
                'email' => 'it@bantuan-sosial.gov.id',
                'whatsapp' => '+62 812 4444 4444',
                'categories' => json_encode(['Teknis']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};