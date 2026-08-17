<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.tenant')] class extends Component {
    use WithPagination;

    public string $employeeId = '';

    public function mount(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'employees'), 403);
    }

    public function with(): array
    {
        return [
            'attendances' => Attendance::with('employee')
                ->when($this->employeeId, fn ($q) => $q->where('employee_id', $this->employeeId))
                ->latest('check_in')
                ->paginate(15),
            'employeeOptions' => Employee::orderBy('name')->get(),
        ];
    }

    public function updatedEmployeeId(): void
    {
        $this->resetPage();
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/'));
    }

    public function hoursWorked(Attendance $attendance): ?string
    {
        if (! $attendance->check_in || ! $attendance->check_out) {
            return null;
        }

        $minutes = $attendance->check_in->diffInMinutes($attendance->check_out);

        return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
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
                        <flux:heading size="xl">{{ __('tenant.attendance.heading') }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ __('tenant.attendance.subheading') }}</flux:text>
                    </div>
                </div>

                <div class="mt-6 max-w-xs">
                    <flux:select wire:model.live="employeeId">
                        <flux:select.option value="">{{ __('tenant.attendance.all_employees') }}</flux:select.option>
                        @foreach ($employeeOptions as $employee)
                            <flux:select.option value="{{ $employee->id }}">{{ $employee->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.employee_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.date_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.check_in_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.check_out_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.hours_worked_label') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($attendances as $attendance)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4 font-medium">{{ $attendance->employee->name }}</td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $attendance->check_in?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $attendance->check_in?->format('H:i') ?? '—' }}</td>
                                    <td class="px-6 py-4 text-zinc-500">
                                        @if ($attendance->check_out)
                                            {{ $attendance->check_out->format('H:i') }}
                                        @else
                                            <flux:badge color="amber" size="sm">{{ __('tenant.attendance.in_progress') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $this->hoursWorked($attendance) ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-zinc-500">
                                        {{ __('tenant.attendance.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($attendances->hasPages())
                        <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                            {{ $attendances->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </flux:main>
    </div>
</div>
