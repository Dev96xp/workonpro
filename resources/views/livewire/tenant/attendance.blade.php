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

    public string $location = '';

    public string $startDate = '';

    public string $endDate = '';

    public bool $showEditModal = false;

    public ?int $editingId = null;

    public string $editCheckIn = '';

    public string $editCheckOut = '';

    public function mount(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'employees'), 403);

        $this->startDate = now()->subDays(6)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function with(): array
    {
        return [
            'attendances' => Attendance::with('employee')
                ->when($this->employeeId, fn ($q) => $q->where('employee_id', $this->employeeId))
                ->when($this->location, fn ($q) => $q->where('location', $this->location))
                ->when($this->startDate, fn ($q) => $q->whereDate('check_in', '>=', $this->startDate))
                ->when($this->endDate, fn ($q) => $q->whereDate('check_in', '<=', $this->endDate))
                ->latest('check_in')
                ->paginate(15),
            'employeeOptions' => Employee::orderBy('name')->get(),
            'locationOptions' => Attendance::query()
                ->whereNotNull('location')
                ->distinct()
                ->orderBy('location')
                ->pluck('location'),
        ];
    }

    public function updatedEmployeeId(): void
    {
        $this->resetPage();
    }

    public function updatedLocation(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function openEdit(int $id): void
    {
        $attendance = Attendance::findOrFail($id);
        $this->editingId = $id;
        $this->editCheckIn = $attendance->check_in?->format('Y-m-d\TH:i') ?? '';
        $this->editCheckOut = $attendance->check_out?->format('Y-m-d\TH:i') ?? '';
        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editCheckIn' => 'required|date',
            'editCheckOut' => 'nullable|date|after_or_equal:editCheckIn',
        ]);

        Attendance::findOrFail($this->editingId)->update([
            'check_in' => $this->editCheckIn,
            'check_out' => $this->editCheckOut !== '' ? $this->editCheckOut : null,
        ]);

        $this->showEditModal = false;
        $this->editingId = null;
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
                    <div class="flex gap-2">
                        <flux:button href="{{ url('/attendance/payroll') }}" wire:navigate icon="banknotes">
                            {{ __('tenant.nav.payroll') }}
                        </flux:button>
                        <flux:button
                            href="{{ url('/attendance/print').'?'.http_build_query(['employeeId' => $employeeId, 'location' => $location, 'startDate' => $startDate, 'endDate' => $endDate]) }}"
                            target="_blank"
                            icon="printer"
                        >
                            {{ __('tenant.attendance.print') }}
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
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.location_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.date_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.check_in_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.check_out_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.attendance.hours_worked_label') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($attendances as $attendance)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4 font-medium">{{ $attendance->employee->name }}</td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $attendance->location ?? '—' }}</td>
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
                                    <td class="px-6 py-4 text-right">
                                        <flux:button wire:click="openEdit({{ $attendance->id }})" size="sm" icon="pencil" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
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

    {{-- Modal Editar registro --}}
    <flux:modal wire:model="showEditModal" class="w-full max-w-md">
        <flux:heading size="lg">{{ __('tenant.attendance.edit') }}</flux:heading>

        <form wire:submit="saveEdit" class="mt-4 space-y-4">
            <flux:field>
                <flux:label>{{ __('tenant.attendance.check_in_label') }}</flux:label>
                <flux:input wire:model="editCheckIn" type="datetime-local" />
                <flux:error name="editCheckIn" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('tenant.attendance.check_out_label') }}</flux:label>
                <flux:input wire:model="editCheckOut" type="datetime-local" />
                <flux:error name="editCheckOut" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showEditModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('tenant.common.save_changes') }}</span>
                    <span wire:loading>{{ __('tenant.common.saving') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
