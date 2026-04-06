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
            $table->unsignedBigInteger('coupon_id')->nullable()->after('is_featured');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['image_1_path', 'image_2_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_images', function (Blueprint $table) {
            $table->dropColumn('coupon_id');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->string('image_1_path')->nullable();
            $table->string('image_2_path')->nullable();
        });
    }
};
