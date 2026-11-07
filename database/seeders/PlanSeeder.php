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
                'price' => 9.99,
                'old_price' => 29,
                'sort_order' => 1,
                'is_active' => true,
                'items' => ['images' => 20, 'services' => 3, 'coupons' => 2],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 39,
                'old_price' => 79,
                'sort_order' => 2,
                'is_active' => true,
                'items' => ['images' => 100, 'services' => null, 'coupons' => null],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price' => 79,
                'old_price' => 129,
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
                    'price' => $data['price'],
                    'old_price' => $data['old_price'],
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
