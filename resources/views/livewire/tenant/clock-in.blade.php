<?php

use App\Models\Attendance;
use App\Models\Location;
use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tenant')] class extends Component {
    #[Url]
    public string $location = '';

    public string $locationError = '';

    public function mount(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'employees'), 403);
    }

    public function with(): array
    {
        return [
            'employee' => auth('employee')->user(),
            'openAttendance' => $this->openAttendance(),
            'requiresGeolocation' => $this->targetLocation()?->hasCoordinates() ?? false,
        ];
    }

    public function toggle(?float $latitude = null, ?float $longitude = null): void
    {
        $this->locationError = '';

        $target = $this->targetLocation();

        if ($target && $target->hasCoordinates()) {
            if ($latitude === null || $longitude === null) {
                $this->locationError = __('tenant.clock_in.geolocation_required');

                return;
            }

            if (! $target->isWithinRadius($latitude, $longitude)) {
                $this->locationError = __('tenant.clock_in.too_far', ['radius' => $target->radius_feet]);

                return;
            }
        }

        $open = $this->openAttendance();

        if ($open) {
            $open->update(['check_out' => now()]);
        } else {
            Attendance::create([
                'employee_id' => auth('employee')->id(),
                'location' => $this->location !== '' ? $this->location : null,
                'check_in' => now(),
            ]);
        }
    }

    private function targetLocation(): ?Location
    {
        return $this->location !== '' ? Location::where('name', $this->location)->first() : null;
    }

    public function logout(): void
    {
        auth('employee')->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/clock-in/login'));
    }

    private function openAttendance(): ?Attendance
    {
        return Attendance::where('employee_id', auth('employee')->id())
            ->whereDate('check_in', today())
            ->whereNull('check_out')
            ->latest('check_in')
            ->first();
    }
}; ?>

<div class="flex min-h-screen flex-col items-center justify-center gap-8 p-6 text-center">
    <div>
        <flux:heading size="xl" class="text-3xl font-bold">
            {{ __('tenant.clock_in.welcome', ['name' => $employee->name]) }}
        </flux:heading>
        <flux:text class="mt-2 text-zinc-500">
            {{ now()->locale(app()->getLocale())->translatedFormat('l, d F Y') }} · {{ now()->format('H:i') }}
        </flux:text>
        @if ($location !== '')
            <flux:badge size="sm" class="mt-3">{{ __('tenant.clock_in.marking_at', ['location' => $location]) }}</flux:badge>
        @endif
    </div>

    <div
        x-data="{
            requesting: false,
            requiresGeo: @js($requiresGeolocation),
            mark() {
                if (!this.requiresGeo) {
                    $wire.toggle();
                    return;
                }

                this.requesting = true;

                if (!navigator.geolocation) {
                    this.requesting = false;
                    $wire.set('locationError', @js(__('tenant.clock_in.geolocation_unsupported')));
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.requesting = false;
                        $wire.toggle(position.coords.latitude, position.coords.longitude);
                    },
                    () => {
                        this.requesting = false;
                        $wire.set('locationError', @js(__('tenant.clock_in.geolocation_denied')));
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            },
        }"
    >
        @if ($locationError !== '')
            <flux:text class="mb-4 text-sm text-red-600">{{ $locationError }}</flux:text>
        @endif

        @if ($openAttendance)
            <flux:text class="text-sm text-zinc-500">
                {{ __('tenant.clock_in.checked_in_at', ['time' => $openAttendance->check_in->format('H:i')]) }}
            </flux:text>
            <flux:button x-on:click="mark()" x-bind:disabled="requesting" variant="danger" class="px-16 py-8 text-xl">
                <span x-show="!requesting">{{ __('tenant.clock_in.check_out_button') }}</span>
                <span x-show="requesting" x-cloak>{{ __('tenant.clock_in.locating') }}</span>
            </flux:button>
        @else
            <flux:button x-on:click="mark()" x-bind:disabled="requesting" variant="primary" class="px-16 py-8 text-xl">
                <span x-show="!requesting">{{ __('tenant.clock_in.check_in_button') }}</span>
                <span x-show="requesting" x-cloak>{{ __('tenant.clock_in.locating') }}</span>
            </flux:button>
        @endif
    </div>

    <flux:button wire:click="logout" variant="ghost" size="sm">
        {{ __('tenant.clock_in.logout') }}
    </flux:button>
</div>
