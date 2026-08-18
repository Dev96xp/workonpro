<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\ImageController;
use App\Http\Middleware\SetLocale;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenancyServiceProvider.
|
*/

use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    PreventAccessFromCentralDomains::class,
    InitializeTenancyBySubdomain::class,
    'web',
    SetLocale::class,
])->group(function () {
    // Public welcome page
    Volt::route('/', 'tenant.welcome')->name('tenant.welcome');

    Route::get('/lang/{locale}', function (Request $request) {
        $locale = $request->route('locale');

        if (in_array($locale, ['es', 'en'], true)) {
            $domain = parse_url(config('app.url'), PHP_URL_HOST);
            Cookie::queue(Cookie::forever('locale', $locale, path: '/', domain: '.'.$domain));
        }

        return redirect()->back();
    })->name('tenant.lang.switch');

    // Login
    Volt::route('/login', 'tenant.login')->name('tenant.login');
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    })->name('tenant.logout');

    // Marcado de entrada/salida de empleados (guard 'employee', independiente del admin)
    Route::middleware('guest:employee')->group(function () {
        Volt::route('/clock-in/login', 'tenant.clock-in-login')->name('tenant.clock-in.login');
    });

    Route::middleware('auth:employee')->group(function () {
        Volt::route('/clock-in', 'tenant.clock-in')->name('tenant.clock-in');
    });

    // Panel protegido
    Route::middleware('auth')->group(function () {
        Volt::route('/dashboard', 'tenant.dashboard')->name('tenant.dashboard');
        Volt::route('/clients', 'tenant.clients')->name('tenant.clients');
        Volt::route('/services', 'tenant.services')->name('tenant.services');
        Volt::route('/coupons', 'tenant.coupons')->name('tenant.coupons');
        Volt::route('/appointments', 'tenant.appointments')->name('tenant.appointments');
        Volt::route('/images', 'tenant.images')->name('tenant.images');
        Route::post('/images/upload', [ImageController::class, 'store'])->name('tenant.images.upload');
        Volt::route('/product-categories', 'tenant.product-categories')->name('tenant.product-categories');
        Volt::route('/products', 'tenant.products')->name('tenant.products');
        Volt::route('/taxes', 'tenant.taxes')->name('tenant.taxes');
        Volt::route('/employees', 'tenant.employees')->name('tenant.employees');
        Volt::route('/attendance', 'tenant.attendance')->name('tenant.attendance');
        Route::get('/attendance/print', function (Request $request) {
            abort_unless(Tenant::hasFeature(tenant('plan'), 'employees'), 403);

            $startDate = $request->query('startDate') ?: now()->subDays(6)->format('Y-m-d');
            $endDate = $request->query('endDate') ?: now()->format('Y-m-d');
            $employeeId = $request->query('employeeId');
            $location = $request->query('location');

            $attendances = Attendance::with('employee')
                ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
                ->when($location, fn ($q) => $q->where('location', $location))
                ->whereDate('check_in', '>=', $startDate)
                ->whereDate('check_in', '<=', $endDate)
                ->orderBy('check_in')
                ->get();

            return view('tenant.attendance-print', [
                'attendances' => $attendances,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'employeeName' => $employeeId ? Employee::find($employeeId)?->name : null,
                'location' => $location,
            ]);
        })->name('tenant.attendance.print');
        Volt::route('/attendance/payroll', 'tenant.attendance-payroll')->name('tenant.attendance.payroll');
        Route::get('/attendance/payroll/print', function (Request $request) {
            abort_unless(Tenant::hasFeature(tenant('plan'), 'employees'), 403);

            $startDate = $request->query('startDate') ?: now()->subDays(6)->format('Y-m-d');
            $endDate = $request->query('endDate') ?: now()->format('Y-m-d');
            $employeeId = $request->query('employeeId');
            $location = $request->query('location');

            $rows = Employee::query()
                ->when($employeeId, fn ($q) => $q->where('id', $employeeId))
                ->orderBy('name')
                ->get()
                ->map(function (Employee $employee) use ($location, $startDate, $endDate) {
                    $attendances = Attendance::where('employee_id', $employee->id)
                        ->when($location, fn ($q) => $q->where('location', $location))
                        ->whereDate('check_in', '>=', $startDate)
                        ->whereDate('check_in', '<=', $endDate)
                        ->get();

                    if ($attendances->isEmpty()) {
                        return null;
                    }

                    $hours = $attendances->whereNotNull('check_out')
                        ->sum(fn (Attendance $attendance) => $attendance->check_in->diffInMinutes($attendance->check_out)) / 60;

                    $amount = match ($employee->salary_period) {
                        Employee::PERIOD_HOURLY => $employee->salary !== null ? round($hours * (float) $employee->salary, 2) : null,
                        default => $employee->salary !== null ? (float) $employee->salary : null,
                    };

                    return ['employee' => $employee, 'hours' => $hours, 'amount' => $amount];
                })
                ->filter()
                ->values();

            return view('tenant.attendance-payroll-print', [
                'rows' => $rows,
                'total' => $rows->sum(fn (array $row) => $row['amount'] ?? 0),
                'startDate' => $startDate,
                'endDate' => $endDate,
                'employeeName' => $employeeId ? Employee::find($employeeId)?->name : null,
                'location' => $location,
            ]);
        })->name('tenant.attendance.payroll.print');
        Volt::route('/invoices', 'tenant.invoices')->name('tenant.invoices');
        Route::get('/invoices/{invoice}/print', function (Request $request) {
            abort_unless(Tenant::hasFeature(tenant('plan'), 'invoices'), 403);

            $invoice = Invoice::findOrFail($request->route('invoice'));

            return view('tenant.invoice-print', ['invoice' => $invoice->load('items', 'payments', 'client', 'taxes')]);
        })->name('tenant.invoices.print');
        Volt::route('/settings', 'tenant.settings')->name('tenant.settings');
    });
});
