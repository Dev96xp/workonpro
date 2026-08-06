<?php

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $name_en = '';

    #[Validate('integer|min:0')]
    public int $sort_order = 0;

    public function save(): void
    {
        $this->validate();

        Category::create([
            'name' => $this->name,
            'name_en' => $this->name_en ?: null,
            'slug' => $this->uniqueSlug(),
            'sort_order' => $this->sort_order,
            'is_active' => true,
        ]);

        $this->redirectRoute('admin.categories.index', navigate: true);
    }

    private function uniqueSlug(): string
    {
        $base = Str::slug($this->name_en ?: $this->name);
        $slug = $base;
        $suffix = 2;

        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}; ?>

<div>
    <div class="mb-6 flex items-center gap-4">
        <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <flux:heading size="xl">Nueva categoría</flux:heading>
    </div>

    <flux:card class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:input wire:model="name" label="Nombre (español)" placeholder="Ej: Cerrajería" required />

            <flux:field>
                <flux:label>Nombre (inglés)</flux:label>
                <flux:input wire:model="name_en" placeholder="Ej: Locksmith" />
                <flux:description>Opcional por ahora — se usará cuando el sitio soporte inglés.</flux:description>
                <flux:error name="name_en" />
            </flux:field>

            <flux:field>
                <flux:label>Orden</flux:label>
                <flux:input wire:model="sort_order" type="number" min="0" />
                <flux:description>Las categorías con número más bajo aparecen primero.</flux:description>
                <flux:error name="sort_order" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" wire:navigate>Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Crear categoría</flux:button>
            </div>
        </form>
    </flux:card>
</div>
