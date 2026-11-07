<?php

namespace Database\Seeders;

use App\AccountType;
use App\Models\BusinessProfile;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantAvailability;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample tenants for trying the platform out. Not part of DatabaseSeeder so it never runs in a
 * production `migrate:fresh --seed`; run it with `php artisan db:seed --class=DemoTenantSeeder`.
 */
class DemoTenantSeeder extends Seeder
{
    public const PASSWORD = '12345612';

    /**
     * @var array<string, array{name: string, email: string, type: AccountType, services: array<string, string>}>
     */
    public const TENANTS = [
        'concrete' => ['name' => 'Concrete LLC', 'email' => 'admin@concrete.com', 'type' => AccountType::Business, 'services' => [
            'Losas y banquetas de concreto' => 'concrete',
            'Cimentación para casas' => 'foundation',
            'Driveways estampados' => 'paving',
        ]],
        'limpieza' => ['name' => 'Limpieza LLC', 'email' => 'admin@limpieza.com', 'type' => AccountType::Business, 'services' => [
            'Limpieza profunda de casas' => 'house-cleaning',
            'Lavado de alfombras' => 'carpet-cleaning',
            'Limpieza final de obra' => 'construction-cleaning',
        ]],
        'framing' => ['name' => 'Framing LLC', 'email' => 'admin@framing.com', 'type' => AccountType::Business, 'services' => [
            'Estructura de madera para casas' => 'framing',
            'Instalación de tablaroca' => 'drywall',
            'Terrazas de madera' => 'decks-patios',
        ]],
        'photography' => ['name' => 'Photography LLC', 'email' => 'admin@photography.com', 'type' => AccountType::Business, 'services' => [
            'Fotografía de bodas' => 'event-photography',
            'Sesiones de XV años' => 'event-photography',
            'Fotos para inmobiliarias' => 'home-sales',
        ]],
        'wine' => ['name' => 'Wine LLC', 'email' => 'admin@wine.com', 'type' => AccountType::Business, 'services' => [
            'Catas de vino' => 'restaurants',
            'Barra de vinos para eventos' => 'catering',
            'Maridaje para cenas privadas' => 'personal-chef',
        ]],
        'jorgelopez' => ['name' => 'Jorge Lopez', 'email' => 'jorgel@google.com', 'type' => AccountType::Personal, 'services' => [
            'Reparaciones del hogar' => 'handyman',
            'Arreglo de fugas' => 'plumbing-repair',
        ]],
        'lorenahernandez' => ['name' => 'Lorena Hernandez', 'email' => 'lorenah@google.com', 'type' => AccountType::Personal, 'services' => [
            'Maquillaje para eventos' => 'makeup',
            'Uñas acrílicas' => 'nails',
        ]],
        'claudiaramirez' => ['name' => 'Claudia Ramirez', 'email' => 'claudiar@google.com', 'type' => AccountType::Personal, 'services' => [
            'Clases de inglés' => 'languages',
            'Tutorías de matemáticas' => 'tutoring',
        ]],
        'perlarodriguez' => ['name' => 'Perla Rodriguez', 'email' => 'perlar@google.com', 'type' => AccountType::Personal, 'services' => [
            'Pasteles personalizados' => 'bakery',
            'Mesas de postres' => 'catering',
        ]],
        'robertosanchez' => ['name' => 'Roberto Sanchez', 'email' => 'robertos@google.com', 'type' => AccountType::Personal, 'services' => [
            'Mecánica a domicilio' => 'auto-mechanic',
            'Lavado y detallado de autos' => 'car-detailing',
        ]],
    ];

    /**
     * @var list<array{city: string, state: string, area_code: string}>
     */
    private const CITIES = [
        ['city' => 'Omaha', 'state' => 'Nebraska', 'area_code' => '402'],
        ['city' => 'Council Bluffs', 'state' => 'Iowa', 'area_code' => '712'],
    ];

    public function run(): void
    {
        $this->call(CategorySeeder::class);

        foreach (self::TENANTS as $subdomain => $demo) {
            if (Tenant::find($subdomain)) {
                continue;
            }

            $this->createTenant($subdomain, $demo);
        }
    }

    /**
     * @param  array{name: string, email: string, type: AccountType, services: array<string, string>}  $demo
     */
    private function createTenant(string $subdomain, array $demo): void
    {
        $location = fake()->randomElement(self::CITIES);
        $street = fake()->buildingNumber().' '.fake()->randomElement(['Dodge St', 'Farnam St', 'Broadway', 'Maple St', 'Pacific St', 'Center St', 'Madison Ave']);
        $phone = sprintf('(%s) %03d-%04d', $location['area_code'], fake()->numberBetween(200, 999), fake()->numberBetween(0, 9999));
        $plan = fake()->randomElement(['basic', 'pro', 'enterprise']);

        $tenant = Tenant::create([
            'id' => $subdomain,
            'name' => $demo['name'],
            'email' => $demo['email'],
            'status' => 'active',
            'plan' => $plan,
            'account_type' => $demo['type'],
            'signup_city' => $location['city'],
            'street_address' => $street,
            'city' => $location['city'],
            'state' => $location['state'],
            'country' => 'United States',
        ]);
        $tenant->domains()->create(['domain' => $subdomain]);

        if (fake()->boolean(70)) {
            TenantAvailability::create([
                'tenant_id' => $subdomain,
                'is_active' => true,
                'timezone' => 'America/Chicago',
                'slots' => $this->randomSlots(),
                'note' => fake()->randomElement(['Llámame para cotizar', 'Respondo el mismo día', 'Cotización gratis', null]),
                'hourly_rate' => $demo['type'] === AccountType::Personal ? fake()->randomFloat(2, 20, 45) : null,
            ]);
        }

        $tenant->run(function () use ($demo, $location, $street, $phone) {
            User::create([
                'name' => $demo['name'],
                'email' => $demo['email'],
                'password' => self::PASSWORD,
            ]);

            BusinessProfile::create([
                'business_name' => $demo['name'],
                'slogan' => fake()->randomElement(['Calidad que se nota', 'Tu proyecto en buenas manos', 'Servicio rápido y honesto', 'Hecho con pasión']),
                'description' => fake()->paragraph(3),
                'email' => $demo['email'],
                'phone' => $phone,
                'whatsapp' => $phone,
                'address' => $street,
                'city' => $location['city'],
                'country' => 'United States',
                'business_hours' => fake()->randomElement(['Lun-Vie 8:00-17:00', 'Lun-Sáb 9:00-18:00', 'Todos los días 7:00-20:00']),
            ]);

            foreach ($demo['services'] as $name => $subcategory) {
                Service::create([
                    'name' => $name,
                    'description' => fake()->sentence(12),
                    'subcategory' => $subcategory,
                    'price' => fake()->randomElement([null, fake()->numberBetween(5, 60) * 10]),
                    'is_active' => true,
                ]);
            }
        });
    }

    /**
     * @return list<array{day: int, start: string, end: string}>
     */
    private function randomSlots(): array
    {
        return collect(fake()->randomElements(range(0, 6), fake()->numberBetween(2, 5)))
            ->sort()
            ->map(fn (int $day) => [
                'day' => $day,
                'start' => fake()->randomElement(['07:00', '08:00', '09:00', '17:00']),
                'end' => fake()->randomElement(['15:00', '18:00', '21:00', '23:00']),
            ])
            ->filter(fn (array $slot) => $slot['start'] < $slot['end'])
            ->values()
            ->all();
    }
}
