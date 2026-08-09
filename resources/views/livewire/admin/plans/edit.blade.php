<?php

use App\Models\Plan;
use App\Models\PlanItem;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public Plan $plan;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('integer|min:0')]
    public int $sort_order = 0;

    public bool $is_active = false;

    public array $itemQuantities = [];

    public string $newItemName = '';

    public string $newItemQuantity = '';

    public function mount(Plan $plan): void
    {
        $this->plan = $plan;
        $this->name = $plan->name;
        $this->sort_order = $plan->sort_order;
        $this->is_active = $plan->is_active;

        foreach ($plan->items as $item) {
            $this->itemQuantities[$item->id] = $item->quantity === null ? '' : (string) $item->quantity;
        }
    }

    public function save(): void
    {
        $this->validate();

        if ($this->is_active && ! $this->plan->is_active && Plan::where('is_active', true)->count() >= Plan::MAX_ACTIVE) {
            $this->addError('is_active', 'Ya tenés ' . Plan::MAX_ACTIVE . ' planes activos. Desactivá uno antes de activar otro.');

            return;
        }

        $this->plan->update([
            'name' => $this->name,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        $this->redirectRoute('admin.plans.index', navigate: true);
    }

    public function saveItem(int $itemId): void
    {
        $value = trim($this->itemQuantities[$itemId] ?? '');

        if ($value !== '' && (! ctype_digit($value) || (int) $value < 1)) {
            $this->addError("itemQuantities.{$itemId}", 'Debe ser un número entero mayor a 0, o vacío para ilimitado.');

            return;
        }

        PlanItem::whereKey($itemId)->update(['quantity' => $value === '' ? null : (int) $value]);
        $this->plan->load('items');
    }

    public function removeItem(int $itemId): void
    {
        PlanItem::whereKey($itemId)->delete();
        unset($this->itemQuantities[$itemId]);
        $this->plan->load('items');
    }

    public function addItem(): void
    {
        $this->validate([
            'newItemName' => 'required|string|in:' . implode(',', array_keys(PlanItem::RESOURCE_LABELS)),
        ]);

        if ($this->plan->items()->where('name', $this->newItemName)->exists()) {
            $this->addError('newItemName', 'Este plan ya tiene ese elemento.');

            return;
        }

        $value = trim($this->newItemQuantity);

        if ($value !== '' && (! ctype_digit($value) || (int) $value < 1)) {
            $this->addError('newItemQuantity', 'Debe ser un número entero mayor a 0, o vacío para ilimitado.');

            return;
        }

        $item = $this->plan->items()->create([
            'name' => $this->newItemName,
            'quantity' => $value === '' ? null : (int) $value,
        ]);

        $this->itemQuantities[$item->id] = $value;
        $this->newItemName = '';
        $this->newItemQuantity = '';
        $this->plan->load('items');
    }

    public function with(): array
    {
        return [
            'availableResources' => collect(PlanItem::RESOURCE_LABELS)
                ->except($this->plan->items->pluck('name'))
                ->all(),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex items-center gap-4">
        <flux:button href="{{ route('admin.plans.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <flux:heading size="xl">Editar: {{ $plan->name }}</flux:heading>
    </div>

    <div class="max-w-lg space-y-8">
        {{-- Datos del plan --}}
        <flux:card>
            <form wire:submit="save" class="flex flex-col gap-6">
                <flux:input wire:model="name" label="Nombre" required />

                <flux:field>
                    <flux:label>Slug</flux:label>
                    <flux:input value="{{ $plan->slug }}" readonly disabled />
                    <flux:description>El slug no se puede editar: es la clave que usa el registro y Stripe.</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Orden</flux:label>
                    <flux:input wire:model="sort_order" type="number" min="0" />
                    <flux:error name="sort_order" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="is_active" label="Plan activo" />
                    <flux:description>Máximo {{ \App\Models\Plan::MAX_ACTIVE }} planes activos al mismo tiempo.</flux:description>
                    <flux:error name="is_active" />
                </flux:field>

                <div class="flex justify-end gap-3">
                    <flux:button href="{{ route('admin.plans.index') }}" variant="ghost" wire:navigate>Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Guardar cambios</flux:button>
                </div>
            </form>
        </flux:card>

        {{-- Elementos del plan --}}
        <flux:card>
            <flux:heading size="lg">Elementos</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500">Cantidad de cada cosa que puede crear un negocio en este plan. Vacío = ilimitado.</flux:text>

            <div class="mt-4 space-y-3">
                @forelse ($plan->items as $item)
                    <div class="flex items-end gap-3">
                        <flux:field class="flex-1">
                            <flux:label>{{ $item->label() }}</flux:label>
                            <flux:input wire:model="itemQuantities.{{ $item->id }}" placeholder="Ilimitado" />
                            <flux:error name="itemQuantities.{{ $item->id }}" />
                        </flux:field>
                        <flux:button wire:click="saveItem({{ $item->id }})" size="sm">Guardar</flux:button>
                        <flux:button wire:click="removeItem({{ $item->id }})" wire:confirm="¿Quitar este elemento del plan?" size="sm" variant="ghost" icon="trash" class="text-red-500" />
                    </div>
                @empty
                    <flux:text class="text-sm text-zinc-400">Este plan todavía no tiene elementos.</flux:text>
                @endforelse
            </div>

            @if (count($availableResources) > 0)
                <div class="mt-6 flex items-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <flux:field class="flex-1">
                        <flux:label>Nuevo elemento</flux:label>
                        <flux:select wire:model="newItemName">
                            <flux:select.option value="">Selecciona...</flux:select.option>
                            @foreach ($availableResources as $key => $label)
                                <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="newItemName" />
                    </flux:field>
                    <flux:field class="w-32">
                        <flux:label>Cantidad</flux:label>
                        <flux:input wire:model="newItemQuantity" placeholder="Ilimitado" />
                        <flux:error name="newItemQuantity" />
                    </flux:field>
                    <flux:button wire:click="addItem" variant="primary" size="sm">Agregar</flux:button>
                </div>
            @endif
        </flux:card>
    </div>
</div>
