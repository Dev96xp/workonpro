<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('categories')->insert(collect([
            ['name' => 'Electricidad', 'name_en' => 'Electrical', 'slug' => 'electrical'],
            ['name' => 'Plomería', 'name_en' => 'Plumbing', 'slug' => 'plumbing'],
            ['name' => 'Construcción', 'name_en' => 'Construction', 'slug' => 'construction'],
            ['name' => 'Remodelación', 'name_en' => 'Remodeling', 'slug' => 'remodeling'],
            ['name' => 'Pintura', 'name_en' => 'Painting', 'slug' => 'painting'],
            ['name' => 'Carpintería', 'name_en' => 'Carpentry', 'slug' => 'carpentry'],
            ['name' => 'Techos', 'name_en' => 'Roofing', 'slug' => 'roofing'],
            ['name' => 'Jardinería', 'name_en' => 'Landscaping', 'slug' => 'landscaping'],
            ['name' => 'Limpieza', 'name_en' => 'Cleaning', 'slug' => 'cleaning'],
            ['name' => 'Mudanzas y transporte', 'name_en' => 'Moving & Transport', 'slug' => 'moving-transport'],
            ['name' => 'Otro', 'name_en' => 'Other', 'slug' => 'other'],
        ])->values()->map(fn (array $category, int $index) => [
            ...$category,
            'sort_order' => $index,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
