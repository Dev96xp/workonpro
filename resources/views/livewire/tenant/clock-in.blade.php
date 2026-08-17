<?php

use App\Models\Attendance;
use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tenant')] class extends Component {
    public function mount(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'employees'), 403);
    }

    public function with(): array
    {
        return [
            'employee' => auth('employee')->user(),
            'openAttendance' => $this->openAttendance(),
        ];
    }

    public function toggle(): void
    {
        $open = $this->openAttendance();

        if ($open) {
            $open->update(['check_out' => now()]);
        } else {
            Attendance::create([
                'employee_id' => auth('employee')->id(),
                'check_in' => now(),
            ]);
        }
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
    </div>

    @if ($openAttendance)
        <flux:text class="text-sm text-zinc-500">
            {{ __('tenant.clock_in.checked_in_at', ['time' => $openAttendance->check_in->format('H:i')]) }}
        </flux:text>
        <flux:button wire:click="toggle" variant="danger" class="px-16 py-8 text-xl">
            {{ __('tenant.clock_in.check_out_button') }}
        </flux:button>
    @else
        <flux:button wire:click="toggle" variant="primary" class="px-16 py-8 text-xl">
            {{ __('tenant.clock_in.check_in_button') }}
        </flux:button>
    @endif

    <flux:button wire:click="logout" variant="ghost" size="sm">
        {{ __('tenant.clock_in.logout') }}
    </flux:button>
</div>
