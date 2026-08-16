<?php

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tenant')] class extends Component {
    public int $year;

    public int $month;

    public string $selectedDate;

    // Modal: crear/editar cita
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Validate('required|exists:clients,id')]
    public string $client_id = '';

    #[Validate('required|string|max:150')]
    public string $title = '';

    #[Validate('required|date')]
    public string $date = '';

    #[Validate('required')]
    public string $start_hour = '';

    #[Validate('required')]
    public string $start_minute = '';

    #[Validate('required')]
    public string $end_hour = '';

    #[Validate('required')]
    public string $end_minute = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    #[Validate('required|in:active,cancelled,attended,no_show')]
    public string $status = Appointment::STATUS_ACTIVE;

    // Modal: confirmar archivado
    public bool $showArchiveModal = false;
    public ?int $archivingId = null;

    public function mount(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'appointments'), 403);

        $this->year = (int) now()->format('Y');
        $this->month = (int) now()->format('n');
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function with(): array
    {
        $monthStart = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        $weekDays = collect(range(0, 6))->map(
            fn (int $i) => $gridStart->copy()->addDays($i)->locale(app()->getLocale())->translatedFormat('D')
        );

        $appointmentsByDay = Appointment::with('client')
            ->visible()
            ->whereBetween('starts_at', [$gridStart, $gridEnd])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Appointment $appointment) => $appointment->starts_at->format('Y-m-d'));

        $hourOptions = collect(range(0, 23))->map(fn (int $h) => sprintf('%02d', $h));
        $minuteOptions = ['00', '15', '30', '45'];

        return [
            'monthStart' => $monthStart,
            'days' => $days,
            'weekDays' => $weekDays,
            'appointmentsByDay' => $appointmentsByDay,
            'selectedAppointments' => $appointmentsByDay->get($this->selectedDate, collect()),
            'clientOptions' => Client::orderBy('name')->get(),
            'hourOptions' => $hourOptions,
            'minuteOptions' => $minuteOptions,
        ];
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = (int) $date->format('Y');
        $this->month = (int) $date->format('n');
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = (int) $date->format('Y');
        $this->month = (int) $date->format('n');
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    private function combinedStart(): ?Carbon
    {
        if (! $this->date || $this->start_hour === '' || $this->start_minute === '') {
            return null;
        }

        try {
            return Carbon::parse("{$this->date} {$this->start_hour}:{$this->start_minute}");
        } catch (\Exception) {
            return null;
        }
    }

    private function combinedEnd(): ?Carbon
    {
        if (! $this->date || $this->end_hour === '' || $this->end_minute === '') {
            return null;
        }

        try {
            return Carbon::parse("{$this->date} {$this->end_hour}:{$this->end_minute}");
        } catch (\Exception) {
            return null;
        }
    }

    public function overlapWarning(): ?string
    {
        $starts = $this->combinedStart();
        $ends = $this->combinedEnd();

        if (! $starts || ! $ends) {
            return null;
        }

        if ($ends->lessThanOrEqualTo($starts)) {
            return null;
        }

        $overlapping = Appointment::with('client')
            ->where('id', '!=', $this->editingId ?? 0)
            ->where('starts_at', '<', $ends)
            ->where('ends_at', '>', $starts)
            ->first();

        return $overlapping
            ? __('tenant.appointments.overlap_warning', ['client' => $overlapping->client->name])
            : null;
    }

    public function openCreate(?string $date = null): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->date = $date ?? $this->selectedDate;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $appointment = Appointment::findOrFail($id);
        $this->editingId = $id;
        $this->client_id = (string) $appointment->client_id;
        $this->title = $appointment->title;
        $this->date = $appointment->starts_at->format('Y-m-d');
        $this->start_hour = $appointment->starts_at->format('H');
        $this->start_minute = $appointment->starts_at->format('i');
        $this->end_hour = $appointment->ends_at->format('H');
        $this->end_minute = $appointment->ends_at->format('i');
        $this->notes = $appointment->notes ?? '';
        $this->status = $appointment->status;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $starts = $this->combinedStart();
        $ends = $this->combinedEnd();

        if ($ends->lessThanOrEqualTo($starts)) {
            $this->addError('end_hour', __('tenant.appointments.end_after_start'));

            return;
        }

        $data = [
            'client_id' => $this->client_id,
            'title' => $this->title,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'notes' => $this->notes ?: null,
            'status' => $this->status,
        ];

        if ($this->editingId) {
            Appointment::findOrFail($this->editingId)->update($data);
        } else {
            Appointment::create($data);
        }

        $this->selectedDate = $this->date;
        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmArchive(int $id): void
    {
        $this->archivingId = $id;
        $this->showArchiveModal = true;
    }

    public function archive(): void
    {
        if ($this->archivingId) {
            Appointment::findOrFail($this->archivingId)->update(['status' => Appointment::STATUS_ARCHIVED]);
        }

        $this->showArchiveModal = false;
        $this->archivingId = null;
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/'));
    }

    private function resetForm(): void
    {
        $this->client_id = '';
        $this->title = '';
        $this->date = '';
        $this->start_hour = '';
        $this->start_minute = '';
        $this->end_hour = '';
        $this->end_minute = '';
        $this->notes = '';
        $this->status = Appointment::STATUS_ACTIVE;
        $this->resetValidation();
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
                        <flux:heading size="xl">{{ __('tenant.appointments.heading') }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ __('tenant.appointments.subheading') }}</flux:text>
                    </div>
                    <flux:button wire:click="openCreate" variant="primary" icon="plus" class="sm:w-auto">
                        {{ __('tenant.appointments.new') }}
                    </flux:button>
                </div>

                {{-- Navegación de mes --}}
                <div class="mt-6 flex items-center justify-center gap-4">
                    <flux:button type="button" wire:click="previousMonth" size="sm" icon="chevron-left" variant="ghost" />
                    <flux:heading size="lg" class="w-48 text-center capitalize">
                        {{ $monthStart->locale(app()->getLocale())->translatedFormat('F Y') }}
                    </flux:heading>
                    <flux:button type="button" wire:click="nextMonth" size="sm" icon="chevron-right" variant="ghost" />
                </div>

                {{-- Grilla del calendario --}}
                <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="grid grid-cols-7 border-b border-zinc-200 dark:border-zinc-700">
                        @foreach ($weekDays as $label)
                            <div class="px-2 py-2 text-center text-xs font-semibold uppercase text-zinc-500">{{ $label }}</div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-7">
                        @foreach ($days as $day)
                            @php
                                $dateKey = $day->format('Y-m-d');
                                $dayAppointments = $appointmentsByDay->get($dateKey, collect());
                                $isCurrentMonth = $day->month === $monthStart->month;
                                $isSelected = $dateKey === $selectedDate;
                                $isToday = $dateKey === now()->format('Y-m-d');
                            @endphp
                            <button
                                type="button"
                                wire:click="selectDate('{{ $dateKey }}')"
                                class="flex h-20 flex-col items-center justify-start gap-1 border-b border-r border-zinc-100 p-2 text-sm transition dark:border-zinc-700
                                    {{ $isCurrentMonth ? 'text-zinc-900 dark:text-white' : 'text-zinc-300 dark:text-zinc-600' }}
                                    {{ $isSelected ? 'bg-yellow-50 dark:bg-yellow-400/10' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/50' }}"
                            >
                                <span class="flex size-6 items-center justify-center rounded-full {{ $isToday ? 'bg-yellow-400 font-bold text-zinc-900' : '' }}">
                                    {{ $day->day }}
                                </span>
                                @if ($dayAppointments->isNotEmpty())
                                    <span class="w-full rounded bg-yellow-100 py-0.5 text-center text-xs font-semibold text-yellow-700 dark:bg-yellow-400/20 dark:text-yellow-300">
                                        {{ $dayAppointments->count() }}
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Panel del día seleccionado --}}
                <div class="mt-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:heading size="lg" class="capitalize">
                            {{ \Illuminate\Support\Carbon::parse($selectedDate)->locale(app()->getLocale())->translatedFormat('l, d F Y') }}
                        </flux:heading>
                        <flux:button type="button" wire:click="openCreate('{{ $selectedDate }}')" size="sm" icon="plus">
                            {{ __('tenant.appointments.new') }}
                        </flux:button>
                    </div>

                    <div class="mt-4 space-y-2">
                        @forelse ($selectedAppointments as $appointment)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold">
                                            {{ $appointment->starts_at->format('H:i') }} - {{ $appointment->ends_at->format('H:i') }} · {{ $appointment->title }}
                                        </p>
                                        <flux:badge size="sm" :color="match ($appointment->status) {
                                            'attended' => 'green',
                                            'cancelled' => 'red',
                                            'no_show' => 'amber',
                                            default => 'zinc',
                                        }">
                                            {{ __('tenant.appointments.status_'.$appointment->status) }}
                                        </flux:badge>
                                    </div>
                                    <p class="text-xs text-zinc-500">{{ $appointment->client->name }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <flux:button type="button" wire:click="openEdit({{ $appointment->id }})" size="sm" icon="pencil" />
                                    <flux:button type="button" wire:click="confirmArchive({{ $appointment->id }})" size="sm" icon="archive-box" />
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">{{ __('tenant.appointments.empty_day') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </flux:main>
    </div>

    {{-- Modal Crear/Editar --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <flux:heading size="lg">{{ $editingId ? __('tenant.appointments.edit') : __('tenant.appointments.new') }}</flux:heading>

        <form wire:submit="save" class="mt-4 space-y-4">
            @php $warning = $this->overlapWarning(); @endphp
            @if ($warning)
                <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                    {{ $warning }}
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.appointments.client_label') }} <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model="client_id">
                        <flux:select.option value="">{{ __('tenant.appointments.select_client') }}</flux:select.option>
                        @foreach ($clientOptions as $client)
                            <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="client_id" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.appointments.title_label') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="title" placeholder="Ej: Visita técnica" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.appointments.date_label') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model.live="date" type="date" />
                    <flux:error name="date" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('tenant.appointments.start_time_label') }} <span class="text-red-500">*</span></flux:label>
                    <div class="flex gap-2">
                        <flux:select wire:model.live="start_hour">
                            <flux:select.option value="">{{ __('tenant.appointments.hour_label') }}</flux:select.option>
                            @foreach ($hourOptions as $hour)
                                <flux:select.option value="{{ $hour }}">{{ $hour }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="start_minute">
                            <flux:select.option value="">{{ __('tenant.appointments.minute_label') }}</flux:select.option>
                            @foreach ($minuteOptions as $minute)
                                <flux:select.option value="{{ $minute }}">{{ $minute }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:error name="start_hour" />
                    <flux:error name="start_minute" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('tenant.appointments.end_time_label') }} <span class="text-red-500">*</span></flux:label>
                    <div class="flex gap-2">
                        <flux:select wire:model.live="end_hour">
                            <flux:select.option value="">{{ __('tenant.appointments.hour_label') }}</flux:select.option>
                            @foreach ($hourOptions as $hour)
                                <flux:select.option value="{{ $hour }}">{{ $hour }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="end_minute">
                            <flux:select.option value="">{{ __('tenant.appointments.minute_label') }}</flux:select.option>
                            @foreach ($minuteOptions as $minute)
                                <flux:select.option value="{{ $minute }}">{{ $minute }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:error name="end_hour" />
                    <flux:error name="end_minute" />
                </flux:field>

                @if ($editingId)
                    <flux:field class="sm:col-span-2">
                        <flux:label>{{ __('tenant.appointments.status_label') }}</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="active">{{ __('tenant.appointments.status_active') }}</flux:select.option>
                            <flux:select.option value="cancelled">{{ __('tenant.appointments.status_cancelled') }}</flux:select.option>
                            <flux:select.option value="attended">{{ __('tenant.appointments.status_attended') }}</flux:select.option>
                            <flux:select.option value="no_show">{{ __('tenant.appointments.status_no_show') }}</flux:select.option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>
                @endif

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.appointments.notes_label') }}</flux:label>
                    <flux:textarea wire:model="notes" rows="2" />
                    <flux:error name="notes" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? __('tenant.common.save_changes') : __('tenant.appointments.new') }}</span>
                    <span wire:loading>{{ __('tenant.common.saving') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal Confirmar Archivado --}}
    <flux:modal wire:model="showArchiveModal" class="max-w-sm">
        <div class="text-center">
            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900">
                <flux:icon.archive-box class="size-6 text-amber-600 dark:text-amber-400" />
            </div>
            <flux:heading size="lg">{{ __('tenant.appointments.confirm_archive') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">{{ __('tenant.appointments.archive_hint') }}</flux:text>
        </div>

        <div class="mt-6 flex justify-center gap-3">
            <flux:button wire:click="$set('showArchiveModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
            <flux:button wire:click="archive" variant="primary">{{ __('tenant.appointments.archive') }}</flux:button>
        </div>
    </flux:modal>
</div>
