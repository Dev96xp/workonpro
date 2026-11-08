<?php

use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Deleting a test tenant wipes storage/app/public/tenants/{id}; faking the disk keeps
        // tests that reuse real tenant ids (e.g. the demo seeder) away from local uploads.
        Storage::fake('public');

        // RefreshDatabase rolls back tenant rows without firing their delete events, so the
        // SQLite file each test tenant gets in database/ would survive and break the next run.
        foreach (glob(database_path('tenant*')) ?: [] as $leftoverTenantDatabase) {
            if (is_file($leftoverTenantDatabase)) {
                @unlink($leftoverTenantDatabase);
            }
        }
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Seeds the three signup plans, with the Basic plan free unless a price is given, so the
 * signup flow can activate tenants without going through Stripe.
 */
function seedSignupPlans(float $basicPrice = 0): void
{
    (new PlanSeeder)->run();

    Plan::where('slug', 'basic')->update(['price' => $basicPrice]);
}
