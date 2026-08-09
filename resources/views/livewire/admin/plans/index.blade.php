<?php

use App\Models\Setting;
use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public array $limits = [];

    public array $planLabels = [
        'basic'      => 'Básico',
        'pro'        => 'Pro',
        'enterprise' => 'Enterprise',
    ];

    public array $resourceLabels = [
        'images'   => 'Imágenes',
        'services' => 'Servicios',
        'coupons'  => 'Cupones',
    ];

    public ?string $savedPlan = null;

    public function mount(): void
    {
        foreach (array_keys($this->planLabels) as $plan) {
            foreach (array_keys($this->resourceLabels) as $resource) {
                $value = Tenant::planLimit($plan, $resource);
                $this->limits[$plan][$resource] = $value === null ? '' : (string) $value;
            }
        }
    }

    public function save(string $plan): void
    {
        $this->savedPlan = null;

        foreach (array_keys($this->resourceLabels) as $resource) {
            $value = trim($this->limits[$plan][$resource] ?? '');

            if ($value !== '' && (! ctype_digit($value) || (int) $value < 1)) {
                $this->addError("limits.{$plan}.{$resource}", 'Debe ser un número entero mayor a 0, o vacío para ilimitado.');

                return;
            }
        }

        foreach (array_keys($this->resourceLabels) as $resource) {
            Setting::set("plan_limit_{$plan}_{$resource}", trim($this->limits[$plan][$resource] ?? ''));
        }

        $this->savedPlan = $plan;
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-1">Planes</flux:heading>
    <flux:text class="mb-6 text-zinc-500">
        Límites de recursos por plan. Un campo vacío significa ilimitado. Los cambios aplican de inmediato a todos los tenants de ese plan, sin necesidad de deploy.
    </flux:text>

    <div class="grid gap-6 md:grid-cols-3">
        @foreach ($planLabels as $plan => $label)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ $label }}</flux:heading>

                @if ($savedPlan === $plan)
                    <div class="mt-3 rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        ¡Límites guardados!
                    </div>
                @endif

                <div class="mt-4 space-y-4">
                    @foreach ($resourceLabels as $resource => $resourceLabel)
                        <flux:field>
                            <flux:label>{{ $resourceLabel }}</flux:label>
                            <flux:input wire:model="limits.{{ $plan }}.{{ $resource }}" placeholder="Ilimitado" />
                            <flux:error name="limits.{{ $plan }}.{{ $resource }}" />
                        </flux:field>
                    @endforeach
                </div>

                <flux:button wire:click="save('{{ $plan }}')" variant="primary" class="mt-5 w-full">
                    Guardar
                </flux:button>
            </div>
        @endforeach
    </div>
</div>
