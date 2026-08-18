<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('role')->nullable()->after('email');
            $table->decimal('salary', 10, 2)->nullable()->after('role');
            $table->string('salary_period')->nullable()->after('salary');
            $table->string('status')->default('active')->after('salary_period');
        });

        DB::table('employees')->where('is_active', false)->update(['status' => 'inactive']);

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        DB::table('employees')->where('status', '!=', 'active')->update(['is_active' => false]);

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['role', 'salary', 'salary_period', 'status']);
        });
    }
};
