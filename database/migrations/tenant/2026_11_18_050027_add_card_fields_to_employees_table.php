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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('whatsapp', 30)->nullable()->after('phone');
            $table->string('photo_path')->nullable()->after('whatsapp');
            $table->string('card_slug')->nullable()->unique()->after('photo_path');
            $table->boolean('card_enabled')->default(false)->after('card_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['card_slug']);
            $table->dropColumn(['phone', 'whatsapp', 'photo_path', 'card_slug', 'card_enabled']);
        });
    }
};
