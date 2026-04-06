<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profile', function (Blueprint $table) {
            $table->string('phone_2')->nullable()->after('phone');
            $table->string('whatsapp')->nullable()->after('phone_2');
            $table->string('instagram')->nullable()->after('website');
            $table->string('facebook')->nullable()->after('instagram');
            $table->string('slogan')->nullable()->after('business_name');
            $table->text('description')->nullable()->after('slogan');
            $table->text('policy')->nullable()->after('description');
            $table->text('objectives')->nullable()->after('policy');
            $table->string('business_hours')->nullable()->after('objectives');
        });
    }

    public function down(): void
    {
        Schema::table('business_profile', function (Blueprint $table) {
            $table->dropColumn([
                'phone_2', 'whatsapp', 'instagram', 'facebook',
                'slogan', 'description', 'policy', 'objectives', 'business_hours',
            ]);
        });
    }
};
