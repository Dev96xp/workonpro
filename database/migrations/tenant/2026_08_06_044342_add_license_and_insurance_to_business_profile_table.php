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
        Schema::table('business_profile', function (Blueprint $table) {
            $table->boolean('has_license')->default(false);
            $table->string('license_number')->nullable();
            $table->boolean('has_insurance')->default(false);
            $table->string('insurance_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profile', function (Blueprint $table) {
            $table->dropColumn(['has_license', 'license_number', 'has_insurance', 'insurance_number']);
        });
    }
};
