<?php

use App\Models\Employee;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    (new PlanSeeder)->run();

    Plan::where('slug', 'enterprise')->first()->items()->create(['name' => 'employees', 'quantity' => null]);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

function makeEmployeeManagementTestTenant(string $id): Tenant
{
    $tenant = Tenant::create([
        'id' => $id,
        'name' => "Tenant {$id}",
        'email' => "{$id}@example.com",
        'status' => 'active',
        'plan' => 'enterprise',
    ]);
    $tenant->domains()->create(['domain' => $id]);

    return $tenant;
}

it('creates an employee with a role, salary, period and status', function () {
    $tenant = makeEmployeeManagementTestTenant('employeecreate');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.employees')
        ->set('name', 'Ana María')
        ->set('email', 'ana@example.com')
        ->set('password', 'password123')
        ->set('role', 'Supervisora')
        ->set('salary', '1500')
        ->set('salary_period', Employee::PERIOD_BIWEEKLY)
        ->set('status', Employee::STATUS_VACATION)
        ->call('save')
        ->assertHasNoErrors();

    $employee = Employee::where('email', 'ana@example.com')->first();

    expect($employee)->not->toBeNull()
        ->and($employee->role)->toBe('Supervisora')
        ->and((float) $employee->salary)->toBe(1500.0)
        ->and($employee->salary_period)->toBe(Employee::PERIOD_BIWEEKLY)
        ->and($employee->status)->toBe(Employee::STATUS_VACATION)
        ->and($employee->isActive())->toBeFalse();

    $tenant->delete();
});

it('updates an employee status back to active', function () {
    $tenant = makeEmployeeManagementTestTenant('employeeedit');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $employee = Employee::factory()->create(['status' => Employee::STATUS_SUSPENDED]);

    Volt::test('tenant.employees')
        ->call('openEdit', $employee->id)
        ->set('status', Employee::STATUS_ACTIVE)
        ->call('save')
        ->assertHasNoErrors();

    expect($employee->fresh()->status)->toBe(Employee::STATUS_ACTIVE);

    $tenant->delete();
});

it('only counts active employees on the dashboard', function () {
    $tenant = makeEmployeeManagementTestTenant('employeedash');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Employee::factory()->create(['status' => Employee::STATUS_ACTIVE]);
    Employee::factory()->create(['status' => Employee::STATUS_SUSPENDED]);

    expect(Volt::test('tenant.dashboard')->viewData('employeeCount'))->toBe(1);

    $tenant->delete();
});
