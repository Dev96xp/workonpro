<?php

use App\Models\Plan;
use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public ?string $limitError = null;

    public function toggleActive(int $planId): void
    {
        $this->limitError = null;
        $plan = Plan::findOrFail($planId);

        if (! $plan->is_active && Plan::where('is_active', true)->count() >= Plan::MAX_ACTIVE) {
            $this->limitError = 'Ya tenés ' . Plan::MAX_ACTIVE . ' planes activos. Desactivá uno antes de activar otro.';

            return;
        }

        $plan->update(['is_active' => ! $plan->is_active]);
    }

    public function deletePlan(int $planId): void
    {
        $plan = Plan::findOrFail($planId);

        if (Tenant::where('plan', $plan->slug)->exists()) {
            return;
        }

        $plan->delete();
    }

    public function with(): array
    {
        return [
            'plans' => Plan::with('items')->orderBy('sort_order')->get(),
            'usedSlugs' => Tenant::query()->distinct()->pluck('plan'),
            'activeCount' => Plan::where('is_active', true)->count(),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Planes</flux:heading>
            <flux:text class="text-zinc-500">{{ $activeCount }} / {{ \App\Models\Plan::MAX_ACTIVE }} planes activos</flux:text>
        </div>
        <flux:button href="{{ route('admin.plans.create') }}" variant="primary" icon="plus" wire:navigate>
            Nuevo plan
        </flux:button>
    </div>

    @if ($limitError)
        <div class="mb-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
            {{ $limitError }}
        </div>
    @endif

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Slug</flux:table.column>
            <flux:table.column>Elementos</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($plans as $plan)
                <flux:table.row>
                    <flux:table.cell class="font-medium">{{ $plan->name }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-400">{{ $plan->slug }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500">
                        @forelse ($plan->items as $item)
                            {{ $item->label() }}: {{ $item->quantity ?? '∞' }}@if (! $loop->last), @endif
                        @empty
                            —
                        @endforelse
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button wire:click="toggleActive({{ $plan->id }})" size="sm" variant="ghost">
                            @if ($plan->is_active)
                                <flux:badge color="green" size="sm">Activo</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Inactivo</flux:badge>
                            @endif
                        </flux:button>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-2">
                            <flux:button href="{{ route('admin.plans.edit', $plan) }}" size="sm" variant="ghost" icon="pencil" wire:navigate />
                            <flux:button
                                wire:click="deletePlan({{ $plan->id }})"
                                wire:confirm="¿Eliminar este plan?"
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                class="text-red-500"
                                :disabled="$usedSlugs->contains($plan->slug)"
                                title="{{ $usedSlugs->contains($plan->slug) ? 'No se puede eliminar: hay negocios en este plan' : 'Eliminar' }}"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell class="text-center text-zinc-400">No hay planes registrados.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
