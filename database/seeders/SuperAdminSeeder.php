<?php

namespace Database\Seeders;

use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        SuperAdmin::firstOrCreate(
            ['email' => 'admin@workonpro.com'],
            [
                'name'     => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );
    }
}
