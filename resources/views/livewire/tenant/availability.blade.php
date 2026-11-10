<?php

use App\Models\TenantAvailability;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tenant')] class extends Component {
    public bool $is_active = true;

    public string $timezone = 'America/New_York';

    public string $note = '';

    public string $hourly_rate = '';

    /** @var array<int, array{enabled: bool, start: string, end: string}> Keyed by Carbon day (0 = Sunday). */
    public array $days = [];

    public string $saved = '';

    public function mount(): void
    {
        $availability = TenantAvailability::firstWhere('tenant_id', tenant('id'));
        $slots = $availability?->sortedSlots()->keyBy('day') ?? collect();

        foreach ($this->weekOrder() as $day) {
            $slot = $slots->get($day);

            $this->days[$day] = [
                'enabled' => $slot !== null,
                'start' => $slot['start'] ?? '09:00',
                'end' => $slot['end'] ?? '17:00',
            ];
        }

        if ($availability) {
            $this->is_active = $availability->is_active;
            $this->timezone = $availability->timezone;
            $this->note = $availability->note ?? '';
            $this->hourly_rate = $availability->hourly_rate !== null ? (string) $availability->hourly_rate : '';
        }
    }

    public function save(): void
    {
        $rules = [
            'is_active' => ['boolean'],
            'timezone' => ['required', 'timezone:all'],
            'note' => ['nullable', 'string', 'max:150'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ];

        foreach ($this->days as $day => $slot) {
            if ($slot['enabled']) {
                $rules["days.{$day}.start"] = ['required', 'date_format:H:i'];
                $rules["days.{$day}.end"] = ['required', 'date_format:H:i', "after:days.{$day}.start"];
            }
        }

        $this->validate($rules, [
            'days.*.end.after' => __('availability.end_after_start'),
        ]);

        TenantAvailability::updateOrCreate(
            ['tenant_id' => tenant('id')],
            [
                'is_active' => $this->is_active,
                'timezone' => $this->timezone,
                'note' => $this->note ?: null,
                'hourly_rate' => $this->hourly_rate !== '' ? $this->hourly_rate : null,
                'slots' => collect($this->days)
                    ->filter(fn (array $slot) => $slot['enabled'])
                    ->map(fn (array $slot, int $day) => ['day' => $day, 'start' => $slot['start'], 'end' => $slot['end']])
                    ->values()
                    ->all(),
            ]
        );

        $this->saved = __('availability.saved');
    }

    public function with(): array
    {
        $availability = TenantAvailability::firstWhere('tenant_id', tenant('id'));

        return [
            'availableNow' => $availability?->isAvailableAt(now()) ?? false,
            'isPaused' => ! ($availability?->is_active ?? false),
            'timezones' => collect([DateTimeZone::AMERICA, DateTimeZone::EUROPE])
                ->flatMap(fn (int $region) => DateTimeZone::listIdentifiers($region))
                ->push($this->timezone)
                ->unique()
                ->sort()
                ->values(),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function weekOrder(): array
    {
        return [1, 2, 3, 4, 5, 6, 0];
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="sm:p-6">
                <flux:heading size="xl">{{ __('availability.heading') }}</flux:heading>
                <flux:text class="max-w-2xl text-zinc-500">{{ __('availability.subheading') }}</flux:text>

                <div class="mt-8 max-w-2xl space-y-6">

                    {{-- Estado actual --}}
                    <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        @if ($availableNow)
                            <span class="relative flex size-3">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex size-3 rounded-full bg-green-500"></span>
                            </span>
                            <span class="font-semibold text-green-600 dark:text-green-400">{{ __('availability.status_now') }}</span>
                        @else
                            <span class="inline-flex size-3 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                            <span class="font-semibold text-zinc-500">{{ $isPaused ? __('availability.status_paused') : __('availability.status_not_now') }}</span>
                        @endif
                    </div>

                    <form wire:submit="save" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                        @if ($saved)
                            <div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                {{ $saved }}
                            </div>
                        @endif

                        <flux:field>
                            <flux:switch wire:model="is_active" label="{{ __('availability.active_switch') }}" />
                            <flux:description>{{ __('availability.active_hint') }}</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('availability.timezone_label') }}</flux:label>
                            <flux:select wire:model="timezone">
                                @foreach ($timezones as $zone)
                                    <flux:select.option value="{{ $zone }}">{{ str_replace('_', ' ', $zone) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:description>{{ __('availability.timezone_hint') }}</flux:description>
                            <flux:error name="timezone" />
                        </flux:field>

                        {{-- Horario semanal --}}
                        <div>
                            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-400">{{ __('availability.schedule_heading') }}</p>
                            <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                @foreach ($days as $day => $slot)
                                    <div wire:key="day-{{ $day }}" class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center">
                                        <div class="sm:w-40">
                                            <flux:checkbox wire:model.live="days.{{ $day }}.enabled" label="{{ __('availability.days.'.$day) }}" />
                                        </div>
                                        @if ($slot['enabled'])
                                            <div class="flex flex-1 items-start gap-3">
                                                <flux:field class="flex-1">
                                                    <flux:input type="time" wire:model="days.{{ $day }}.start" aria-label="{{ __('availability.from') }}" />
                                                    <flux:error name="days.{{ $day }}.start" />
                                                </flux:field>
                                                <span class="pt-2 text-zinc-400">–</span>
                                                <flux:field class="flex-1">
                                                    <flux:input type="time" wire:model="days.{{ $day }}.end" aria-label="{{ __('availability.to') }}" />
                                                    <flux:error name="days.{{ $day }}.end" />
                                                </flux:field>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <flux:field class="sm:col-span-2">
                                <flux:label>{{ __('availability.note_label') }}</flux:label>
                                <flux:input wire:model="note" placeholder="{{ __('availability.note_placeholder') }}" />
                                <flux:error name="note" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('availability.rate_label') }}</flux:label>
                                <flux:input wire:model="hourly_rate" type="number" step="0.01" min="0" icon="currency-dollar" />
                                <flux:error name="hourly_rate" />
                            </flux:field>
                        </div>

                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                <span wire:loading.remove>{{ __('availability.save') }}</span>
                                <span wire:loading>{{ __('tenant.common.saving') }}</span>
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </flux:main>
    </div>
</div>
