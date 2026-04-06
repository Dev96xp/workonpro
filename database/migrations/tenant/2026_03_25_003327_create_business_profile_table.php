<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profile', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('slogan')->nullable();
            $table->text('description')->nullable();
            $table->text('policy')->nullable();
            $table->text('objectives')->nullable();
            $table->string('business_hours')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_2')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable()->default('US');
            $table->string('website')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profile');
    }
};
