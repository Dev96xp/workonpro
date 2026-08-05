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
            $table->boolean('show_logo_on_hero')->default(false)->after('business_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profile', function (Blueprint $table) {
            $table->dropColumn('show_logo_on_hero');
        });
    }
};
