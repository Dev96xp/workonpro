<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_images', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('compressed_size');
            $table->boolean('is_featured')->default(false);
            $table->nullableMorphs('imageable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_images');
    }
};
