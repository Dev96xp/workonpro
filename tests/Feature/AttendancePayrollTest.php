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

function makePayrollTestTenant(string $id, string $plan = 'enterprise'): Tenant
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

it('calculates the amount to pay for an hourly employee based on hours worked', function () {
    $tenant = makePayrollTestTenant('payrollhourly');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $employee = Employee::factory()->create([
        'salary' => 20,
        'salary_period' => Employee::PERIOD_HOURLY,
    ]);

    Attendance::create(['employee_id' => $employee->id, 'check_in' => now()->subDays(1)->setTime(8, 0), 'check_out' => now()->subDays(1)->setTime(10, 0)]);
    Attendance::create(['employee_id' => $employee->id, 'check_in' => now()->subDays(2)->setTime(8, 0), 'check_out' => now()->subDays(2)->setTime(11, 0)]);

    $rows = Volt::test('tenant.attendance-payroll')->viewData('rows');

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['hours'])->toBe(5.0)
        ->and($rows->first()['amount'])->toBe(100.0);

    $tenant->delete();
});

it('shows the fixed salary for a monthly employee regardless of hours worked', function () {
    $tenant = makePayrollTestTenant('payrollmonthly');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $employee = Employee::factory()->create([
        'salary' => 1500,
        'salary_period' => Employee::PERIOD_MONTHLY,
    ]);

    Attendance::create(['employee_id' => $employee->id, 'check_in' => now()->subDays(1)->setTime(8, 0), 'check_out' => now()->subDays(1)->setTime(9, 0)]);

    $rows = Volt::test('tenant.attendance-payroll')->viewData('rows');

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['amount'])->toBe(1500.0);

    $tenant->delete();
});

it('excludes employees without attendance in the filtered range and totals the rest', function () {
    $tenant = makePayrollTestTenant('payrolltotal');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $withAttendance = Employee::factory()->create(['salary' => 1000, 'salary_period' => Employee::PERIOD_MONTHLY]);
    Employee::factory()->create(['salary' => 800, 'salary_period' => Employee::PERIOD_MONTHLY]);

    Attendance::create(['employee_id' => $withAttendance->id, 'check_in' => now()->subDays(1), 'check_out' => now()->subDays(1)->addHour()]);

    $component = Volt::test('tenant.attendance-payroll');

    expect($component->viewData('rows'))->toHaveCount(1)
        ->and($component->viewData('total'))->toBe(1000.0);

    $tenant->delete();
});

it('blocks a pro tenant from accessing the payroll report', function () {
    $tenant = makePayrollTestTenant('payrollpro', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.attendance-payroll')->assertStatus(403);

    $tenant->delete();
});
