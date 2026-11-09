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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homeowner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcategory_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('city', 100)->index();
            $table->string('state', 100);
            $table->string('zip', 20)->nullable();
            $table->string('urgency', 20);
            $table->string('budget', 20)->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
