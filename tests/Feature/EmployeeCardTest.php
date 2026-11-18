<?php

use App\Models\BusinessProfile;
use App\Models\Employee;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Volt\Volt;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

beforeEach(function () {
    (new PlanSeeder)->run();

    $enterprise = Plan::where('slug', 'enterprise')->first();
    foreach (['employees', 'businesscard'] as $resource) {
        $enterprise->items()->firstOrCreate(['name' => $resource], ['quantity' => null]);
    }
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    File::deleteDirectory(base_path('storage/app/public/tenants/employeecard'));
    Tenant::query()->where('id', 'employeecard')->get()->each->delete();
});

function makeEmployeeCardTenant(string $plan = 'enterprise'): Tenant
{
    $tenant = Tenant::create([
        'id' => 'employeecard',
        'name' => 'Perla Co',
        'email' => 'employeecard@example.com',
        'status' => 'active',
        'plan' => $plan,
    ]);
    $tenant->domains()->create(['domain' => 'employeecard']);
    tenancy()->initialize($tenant);

    return $tenant;
}

function makeCardEmployee(array $attributes = []): Employee
{
    $employee = Employee::create([
        'name' => 'Erika Mendoza',
        'email' => 'erika@example.com',
        'password' => 'password123',
        'role' => 'CEO Administrator',
        'phone' => '(402) 555-0199',
        'card_enabled' => true,
        ...$attributes,
    ]);
    $employee->ensureCardSlug();

    return $employee;
}

function visitTenantUrl(string $routeName, array $parameters = [])
{
    return test()->withoutMiddleware([PreventAccessFromCentralDomains::class, InitializeTenancyBySubdomain::class])
        ->get(route($routeName, ['subdomain' => 'employeecard', ...$parameters]));
}

it('shows the employee card with their name, role, contact and the business brand', function () {
    makeEmployeeCardTenant();
    BusinessProfile::create(['business_name' => 'Perla Rodriguez Exports', 'phone' => '(402) 555-0100']);
    $employee = makeCardEmployee();

    expect($employee->card_slug)->toBe('erika-mendoza');

    visitTenantUrl('tenant.employee-card', ['slug' => 'erika-mendoza'])
        ->assertOk()
        ->assertSee('Erika Mendoza')
        ->assertSee('CEO Administrator')
        ->assertSee('(402) 555-0199')
        ->assertSee('erika@example.com')
        ->assertSee('Perla Rodriguez Exports')
        ->assertSee('data-employee-initials', false)
        ->assertSee('EM')
        ->assertSee(url('/card/erika-mendoza/vcard'), false);
});

it('falls back to the business phone when the employee has none', function () {
    makeEmployeeCardTenant();
    BusinessProfile::create(['business_name' => 'Perla Rodriguez Exports', 'phone' => '(402) 555-0100']);
    makeCardEmployee(['phone' => null]);

    visitTenantUrl('tenant.employee-card', ['slug' => 'erika-mendoza'])
        ->assertOk()
        ->assertSee('(402) 555-0100');
});

it('gives each employee a unique link even with the same name', function () {
    makeEmployeeCardTenant();
    $first = makeCardEmployee();
    $second = makeCardEmployee(['email' => 'other-erika@example.com']);

    expect($first->card_slug)->toBe('erika-mendoza')
        ->and($second->card_slug)->toBe('erika-mendoza-2');
});

it('hides the card when it is turned off or the employee is not active', function () {
    makeEmployeeCardTenant();
    $employee = makeCardEmployee();

    $employee->update(['card_enabled' => false]);
    visitTenantUrl('tenant.employee-card', ['slug' => 'erika-mendoza'])->assertNotFound();

    $employee->update(['card_enabled' => true, 'status' => Employee::STATUS_INACTIVE]);
    visitTenantUrl('tenant.employee-card', ['slug' => 'erika-mendoza'])->assertNotFound();

    visitTenantUrl('tenant.employee-card', ['slug' => 'nobody'])->assertNotFound();
});

it('only works on plans with employees and digital cards', function () {
    makeEmployeeCardTenant('pro');
    makeCardEmployee();

    visitTenantUrl('tenant.employee-card', ['slug' => 'erika-mendoza'])->assertStatus(403);
});

it('downloads the employee vcard with the business as company', function () {
    makeEmployeeCardTenant();
    BusinessProfile::create(['business_name' => 'Perla Rodriguez Exports']);
    makeCardEmployee();

    visitTenantUrl('tenant.employee-card.vcard', ['slug' => 'erika-mendoza'])
        ->assertOk()
        ->assertHeader('Content-Type', 'text/vcard; charset=utf-8')
        ->assertSee('FN:Erika Mendoza', false)
        ->assertSee('ORG:Perla Rodriguez Exports', false)
        ->assertSee('TITLE:CEO Administrator', false)
        ->assertSee('TEL;TYPE=WORK,VOICE:(402) 555-0199', false);
});

it('saves phone, whatsapp, photo and turns the card on from the employees module', function () {
    makeEmployeeCardTenant();
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.employees')
        ->assertSet('hasCards', true)
        ->call('openCreate')
        ->set('name', 'Claudia Rodriguez')
        ->set('email', 'claudia@example.com')
        ->set('password', 'password123')
        ->set('phone', '(402) 555-0123')
        ->set('whatsapp', '+14025550123')
        ->set('photo', UploadedFile::fake()->image('claudia.jpg', 900, 1200))
        ->set('card_enabled', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee(url('/card/claudia-rodriguez'));

    $employee = Employee::firstWhere('email', 'claudia@example.com');

    expect($employee->phone)->toBe('(402) 555-0123')
        ->and($employee->whatsapp)->toBe('+14025550123')
        ->and($employee->card_slug)->toBe('claudia-rodriguez')
        ->and($employee->photo_path)->toStartWith('tenants/employeecard/employees/')
        ->and(base_path('storage/app/public/'.$employee->photo_path))->toBeFile();

    visitTenantUrl('tenant.employee-card', ['slug' => 'claudia-rodriguez'])
        ->assertOk()
        ->assertSee('data-employee-photo', false);

    Volt::test('tenant.employees')
        ->call('openEdit', $employee->id)
        ->call('clearPhoto')
        ->call('save')
        ->assertHasNoErrors();

    expect($employee->fresh()->photo_path)->toBeNull();
});

it('does not offer cards when the plan has no digital card', function () {
    makeEmployeeCardTenant();
    Plan::where('slug', 'enterprise')->first()->items()->where('name', 'businesscard')->delete();
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.employees')
        ->assertSet('hasCards', false)
        ->assertDontSee('Digital card');
});
