<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Siembra los 3 planes que hoy están conectados al registro y a Stripe
     * (basic/pro/enterprise), con los límites vigentes hasta ahora.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basic',
                'sort_order' => 1,
                'is_active' => true,
                'items' => ['images' => 20, 'services' => 3, 'coupons' => 2],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'sort_order' => 2,
                'is_active' => true,
                'items' => ['images' => 100, 'services' => null, 'coupons' => null],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'sort_order' => 3,
                'is_active' => true,
                'items' => ['images' => null, 'services' => null, 'coupons' => null],
            ],
        ];

        foreach ($plans as $data) {
            $plan = Plan::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'sort_order' => $data['sort_order'],
                    'is_active' => $data['is_active'],
                ]
            );

            foreach ($data['items'] as $name => $quantity) {
                $plan->items()->updateOrCreate(['name' => $name], ['quantity' => $quantity]);
            }
        }
    }
}
