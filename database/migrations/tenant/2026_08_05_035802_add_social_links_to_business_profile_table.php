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
            $table->string('youtube')->nullable()->after('facebook');
            $table->string('x')->nullable()->after('youtube');
            $table->string('tiktok')->nullable()->after('x');
            $table->string('discord')->nullable()->after('tiktok');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profile', function (Blueprint $table) {
            $table->dropColumn(['youtube', 'x', 'tiktok', 'discord']);
        });
    }
};
