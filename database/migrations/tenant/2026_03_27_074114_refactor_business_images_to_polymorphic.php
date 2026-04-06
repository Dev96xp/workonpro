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
        Schema::table('business_images', function (Blueprint $table) {
            $table->dropColumn('coupon_id');
            $table->nullableMorphs('imageable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_images', function (Blueprint $table) {
            $table->dropMorphs('imageable');
            $table->unsignedBigInteger('coupon_id')->nullable();
        });
    }
};
