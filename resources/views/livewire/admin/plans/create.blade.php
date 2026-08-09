<?php

use App\Models\Plan;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('integer|min:0')]
    public int $sort_order = 0;

    public bool $is_active = false;

    public function save(): void
    {
        $this->validate();

        if ($this->is_active && Plan::where('is_active', true)->count() >= Plan::MAX_ACTIVE) {
            $this->addError('is_active', 'Ya tenés ' . Plan::MAX_ACTIVE . ' planes activos. Desactivá uno antes de activar otro.');

            return;
        }

        $plan = Plan::create([
            'name' => $this->name,
            'slug' => $this->uniqueSlug(),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        $this->redirectRoute('admin.plans.edit', $plan, navigate: true);
    }

    private function uniqueSlug(): string
    {
        $base = Str::slug($this->name);
        $slug = $base;
        $suffix = 2;

        while (Plan::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}; ?>

<div>
    <div class="mb-6 flex items-center gap-4">
        <flux:button href="{{ route('admin.plans.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <flux:heading size="xl">Nuevo plan</flux:heading>
    </div>

    <flux:card class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:input wire:model="name" label="Nombre" placeholder="Ej: Ultra" required />

            <flux:field>
                <flux:label>Orden</flux:label>
                <flux:input wire:model="sort_order" type="number" min="0" />
                <flux:description>Los planes con número más bajo aparecen primero.</flux:description>
                <flux:error name="sort_order" />
            </flux:field>

            <flux:field>
                <flux:checkbox wire:model="is_active" label="Plan activo" />
                <flux:description>Máximo {{ \App\Models\Plan::MAX_ACTIVE }} planes activos al mismo tiempo.</flux:description>
                <flux:error name="is_active" />
            </flux:field>

            <flux:text class="text-sm text-zinc-500">
                Después de crear el plan vas a poder agregarle sus elementos (imágenes, servicios, cupones) y sus cantidades.
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('admin.plans.index') }}" variant="ghost" wire:navigate>Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Crear plan</flux:button>
            </div>
        </form>
    </flux:card>
</div>
