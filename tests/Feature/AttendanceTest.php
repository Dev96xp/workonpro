<?php

use App\Models\Attendance;
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

function makeAttendanceTestTenant(string $id, string $plan): Tenant
{
    $tenant = Tenant::create([
        'id' => $id,
        'name' => "Tenant {$id}",
        'email' => "{$id}@example.com",
        'status' => 'active',
        'plan' => $plan,
    ]);
    $tenant->domains()->create(['domain' => $id]);

    return $tenant;
}

it('auto-generates a unique employee code on creation', function () {
    $tenant = makeAttendanceTestTenant('attendcode', 'enterprise');
    tenancy()->initialize($tenant);

    $employee = Employee::factory()->create(['name' => 'Ana María']);

    expect($employee->employee_code)->not->toBeNull()
        ->and($employee->employee_code)->toStartWith('ANAM-')
        ->and($employee->employee_code)->toMatch('/^ANAM-\d{6}-\d{4}-\d{4}$/');

    $tenant->delete();
});

it('logs an employee in through the clock-in login and redirects to the clock-in screen', function () {
    $tenant = makeAttendanceTestTenant('attendlogin', 'enterprise');
    tenancy()->initialize($tenant);

    $employee = Employee::factory()->create(['password' => bcrypt('password123')]);

    Volt::test('tenant.clock-in-login')
        ->set('email', $employee->email)
        ->set('password', 'password123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(url('/clock-in'));

    expect(auth('employee')->check())->toBeTrue()
        ->and(auth('employee')->id())->toBe($employee->id);

    $tenant->delete();
});

it('clocks an employee in and then out on the same day', function () {
    $tenant = makeAttendanceTestTenant('attendtoggle', 'enterprise');
    tenancy()->initialize($tenant);

    $employee = Employee::factory()->create();
    $this->actingAs($employee, 'employee');

    Volt::test('tenant.clock-in')->call('toggle');

    expect(Attendance::count())->toBe(1);
    $attendance = Attendance::first();
    expect($attendance->employee_id)->toBe($employee->id)
        ->and($attendance->check_in)->not->toBeNull()
        ->and($attendance->check_out)->toBeNull();

    Volt::test('tenant.clock-in')->call('toggle');

    expect(Attendance::count())->toBe(1);
    expect($attendance->fresh()->check_out)->not->toBeNull();

    $tenant->delete();
});

it('captures the building location from the query string when clocking in', function () {
    $tenant = makeAttendanceTestTenant('attendlocation', 'enterprise');
    tenancy()->initialize($tenant);

    $employee = Employee::factory()->create();
    $this->actingAs($employee, 'employee');

    Volt::test('tenant.clock-in')
        ->set('location', 'Almacén 1')
        ->call('toggle');

    $attendance = Attendance::first();

    expect($attendance->location)->toBe('Almacén 1');

    $tenant->delete();
});

it('blocks a pro tenant from accessing the employee module (enterprise-only)', function () {
    $tenant = makeAttendanceTestTenant('attendpro', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.employees')->assertStatus(403);
    Volt::test('tenant.attendance')->assertStatus(403);

    $tenant->delete();
});

it('blocks a basic tenant from accessing the employee module', function () {
    $tenant = makeAttendanceTestTenant('attendbasic', 'basic');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.employees')->assertStatus(403);

    $tenant->delete();
});

it('lets an enterprise tenant access the employee module', function () {
    $tenant = makeAttendanceTestTenant('attendent', 'enterprise');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.employees')->assertOk();
    Volt::test('tenant.attendance')->assertOk();

    $tenant->delete();
});
