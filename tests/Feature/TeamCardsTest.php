<?php

use App\Models\Employee;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Volt\Volt;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

beforeEach(function () {
    (new PlanSeeder)->run();
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    File::deleteDirectory(base_path('storage/app/public/tenants/teamcards'));
    Tenant::query()->where('id', 'teamcards')->get()->each->delete();
});

function actAsTeamCardsTenant(string $plan = 'basic'): Tenant
{
    $tenant = Tenant::create([
        'id' => 'teamcards',
        'name' => 'Team Co',
        'email' => 'teamcards@example.com',
        'status' => 'active',
        'plan' => $plan,
    ]);
    $tenant->domains()->create(['domain' => 'teamcards']);
    tenancy()->initialize($tenant);
    test()->actingAs(User::factory()->create());

    return $tenant;
}

function addTeamPerson(string $name, string $email): Testable
{
    return Volt::test('tenant.team')
        ->call('openCreate')
        ->set('name', $name)
        ->set('role', 'Sales')
        ->set('email', $email)
        ->set('phone', '(402) 555-0100')
        ->call('save');
}

it('lets a basic plan give cards to two people and asks to upgrade for a third', function () {
    actAsTeamCardsTenant('basic');

    Volt::test('tenant.team')
        ->assertSee('0 of 2 cards in use')
        ->assertDontSee('Salary')
        ->assertDontSee('Password');

    addTeamPerson('Ana Torres', 'ana@example.com')->assertHasNoErrors();
    addTeamPerson('Luis Perez', 'luis@example.com')->assertHasNoErrors();

    addTeamPerson('Carla Diaz', 'carla@example.com')
        ->assertHasErrors('card_enabled')
        ->assertSee('You already used all the cards in your plan.');

    expect(Employee::count())->toBe(2)
        ->and(Employee::firstWhere('email', 'ana@example.com')->hasPublicCard())->toBeTrue();

    Volt::test('tenant.team')
        ->assertSee('2 of 2 cards in use')
        ->assertSee(url('/card/ana-torres'))
        ->assertSeeHtml('href="'.url('/plan').'"');
});

it('saves a person without a card when the plan is full and blocks turning it on', function () {
    actAsTeamCardsTenant('basic');
    addTeamPerson('Ana Torres', 'ana@example.com');
    addTeamPerson('Luis Perez', 'luis@example.com');

    $component = Volt::test('tenant.team')
        ->call('openCreate')
        ->set('name', 'Carla Diaz')
        ->set('email', 'carla@example.com')
        ->set('card_enabled', false)
        ->call('save')
        ->assertHasNoErrors();

    $carla = Employee::firstWhere('email', 'carla@example.com');

    $component->call('toggleCard', $carla->id)
        ->assertSeeHtml('data-team-limit-message');

    expect($carla->fresh()->card_enabled)->toBeFalse();

    $ana = Employee::firstWhere('email', 'ana@example.com');
    $component->call('toggleCard', $ana->id)->call('toggleCard', $carla->id);

    expect($carla->fresh()->hasPublicCard())->toBeTrue()
        ->and($ana->fresh()->card_enabled)->toBeFalse();
});

it('keeps only the oldest cards working after moving to a smaller plan', function () {
    actAsTeamCardsTenant('pro');
    foreach (['Ana Torres', 'Luis Perez', 'Carla Diaz'] as $index => $name) {
        Employee::create(['name' => $name, 'email' => "person{$index}@example.com", 'password' => 'password123', 'card_enabled' => true])->ensureCardSlug();
    }

    tenant()->update(['plan' => 'basic']);

    $withoutMiddleware = $this->withoutMiddleware([PreventAccessFromCentralDomains::class, InitializeTenancyBySubdomain::class]);
    $withoutMiddleware->get(route('tenant.employee-card', ['subdomain' => 'teamcards', 'slug' => 'ana-torres']))->assertOk();
    $withoutMiddleware->get(route('tenant.employee-card', ['subdomain' => 'teamcards', 'slug' => 'luis-perez']))->assertOk();
    $withoutMiddleware->get(route('tenant.employee-card', ['subdomain' => 'teamcards', 'slug' => 'carla-diaz']))->assertNotFound();

    Volt::test('tenant.team')
        ->assertSee('2 of 2 cards in use')
        ->assertSee('Over the plan limit');
});

it('shows an upgrade invitation when the plan has no team cards', function () {
    Plan::where('slug', 'basic')->first()->items()->where('name', 'team_cards')->delete();
    actAsTeamCardsTenant('basic');

    Volt::test('tenant.team')
        ->assertSeeHtml('data-team-locked')
        ->assertSee('Upgrade my plan')
        ->call('openCreate')
        ->assertForbidden();
});

it('does not let the team page delete enterprise employees', function () {
    Plan::where('slug', 'enterprise')->first()->items()->firstOrCreate(['name' => 'employees'], ['quantity' => null]);
    actAsTeamCardsTenant('enterprise');
    $employee = Employee::create(['name' => 'Ana Torres', 'email' => 'ana@example.com', 'password' => 'password123']);

    Volt::test('tenant.team')
        ->assertSee('cards in use')
        ->assertSee('Salary, password and attendance are managed in Employees.')
        ->call('delete', $employee->id)
        ->assertForbidden();

    expect(Employee::count())->toBe(1);
});

it('adds team cards to the tenant menu and the dashboard', function () {
    actAsTeamCardsTenant('basic');

    Volt::test('tenant.dashboard')
        ->assertSeeHtml('href="'.url('/team').'"')
        ->assertSee('Team cards');
});
