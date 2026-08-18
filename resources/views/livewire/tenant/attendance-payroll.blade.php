<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tenant')] class extends Component {
    public string $employeeId = '';

    public string $location = '';

    public string $startDate = '';

    public string $endDate = '';

    public function mount(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'employees'), 403);

        $this->startDate = now()->subDays(6)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function with(): array
    {
        $rows = $this->payrollRows();

        return [
            'rows' => $rows,
            'total' => $rows->sum(fn (array $row) => $row['amount'] ?? 0),
            'employeeOptions' => Employee::orderBy('name')->get(),
            'locationOptions' => Attendance::query()
                ->whereNotNull('location')
                ->distinct()
                ->orderBy('location')
                ->pluck('location'),
        ];
    }

    public function payrollRows(): \Illuminate\Support\Collection
    {
        return Employee::query()
            ->when($this->employeeId, fn ($q) => $q->where('id', $this->employeeId))
            ->orderBy('name')
            ->get()
            ->map(fn (Employee $employee) => $this->payrollRowFor($employee))
            ->filter()
            ->values();
    }

    private function payrollRowFor(Employee $employee): ?array
    {
        $attendances = Attendance::where('employee_id', $employee->id)
            ->when($this->location, fn ($q) => $q->where('location', $this->location))
            ->whereDate('check_in', '>=', $this->startDate)
            ->whereDate('check_in', '<=', $this->endDate)
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

        return [
            'employee' => $employee,
            'hours' => $hours,
            'amount' => $amount,
        ];
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/'));
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading size="xl">{{ __('tenant.attendance_payroll.heading') }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ __('tenant.attendance_payroll.subheading') }}</flux:text>
                    </div>
                    <div class="flex gap-2">
                        <flux:button href="{{ url('/attendance') }}" wire:navigate>
                            {{ __('tenant.attendance_payroll.back_to_attendance') }}
                        </flux:button>
                        <flux:button
                            href="{{ url('/attendance/payroll/print').'?'.http_build_query(['employeeId' => $employeeId, 'location' => $location, 'startDate' => $startDate, 'endDate' => $endDate]) }}"
                            target="_blank"
                            icon="printer"
                        >
                            {{ __('tenant.attendance_payroll.print') }}
                        </flux:button>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <flux:select wire:model.live="employeeId" class="max-w-xs">
                        <flux:select.option value="">{{ __('tenant.attendance.all_employees') }}</flux:select.option>
                        @foreach ($employeeOptions as $employee)
                            <flux:select.option value="{{ $employee->id }}">{{ $employee->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="location" class="max-w-xs">
                        <flux:select.option value="">{{ __('tenant.attendance.all_locations') }}</flux:select.option>
                        @foreach ($locationOptions as $option)
                            <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:field class="w-auto">
                        <flux:label class="text-xs">{{ __('tenant.attendance.from_label') }}</flux:label>
                        <flux:input wire:model.live="startDate" type="date" class="max-w-[10rem]" />
                    </flux:field>

                    <flux:field class="w-auto">
                        <flux:label class="text-xs">{{ __('tenant.attendance.to_label') }}</flux:label>
                        <flux:input wire:model.live="endDate" type="date" class="max-w-[10rem]" />
                    </flux:field>
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.employee_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.employees.role_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.employees.salary_period_label') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.hours_worked_label') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance_payroll.amount_label') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($rows as $row)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4 font-medium">{{ $row['employee']->name }}</td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $row['employee']->role ?? '—' }}</td>
                                    <td class="px-6 py-4 text-zinc-500">
                                        {{ $row['employee']->salary_period ? __('tenant.employees.period_'.$row['employee']->salary_period) : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-zinc-500">
                                        {{ sprintf('%dh %02dm', intdiv((int) round($row['hours'] * 60), 60), (int) round($row['hours'] * 60) % 60) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium">
                                        @if ($row['amount'] !== null)
                                            ${{ number_format($row['amount'], 2) }}
                                        @else
                                            <flux:badge color="amber" size="sm">{{ __('tenant.attendance_payroll.no_salary') }}</flux:badge>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-zinc-500">
                                        {{ __('tenant.attendance_payroll.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($rows->isNotEmpty())
                            <tfoot class="bg-zinc-50 dark:bg-zinc-900">
                                <tr>
                                    <td colspan="4" class="px-6 py-3 text-right font-semibold uppercase tracking-wider text-xs text-zinc-500">
                                        {{ __('tenant.attendance_payroll.total_label') }}
                                    </td>
                                    <td class="px-6 py-3 text-right font-semibold">${{ number_format($total, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </flux:main>
    </div>
</div>
