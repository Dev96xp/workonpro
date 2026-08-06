<?php

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public Category $category;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $name_en = '';

    #[Validate('integer|min:0')]
    public int $sort_order = 0;

    public bool $is_active = true;

    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->name = $category->name;
        $this->name_en = $category->name_en ?? '';
        $this->sort_order = $category->sort_order;
        $this->is_active = $category->is_active;
    }

    public function save(): void
    {
        $this->validate();

        $this->category->update([
            'name' => $this->name,
            'name_en' => $this->name_en ?: null,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        $this->redirectRoute('admin.categories.index', navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 flex items-center gap-4">
        <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <flux:heading size="xl">Editar: {{ $category->name }}</flux:heading>
    </div>

    <flux:card class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:input wire:model="name" label="Nombre" required />

            <flux:field>
                <flux:label>Slug</flux:label>
                <flux:input value="{{ $category->slug }}" readonly disabled />
                <flux:description>El slug no se puede editar: es la clave guardada en los servicios existentes.</flux:description>
            </flux:field>

            <flux:field>
                <flux:label>Orden</flux:label>
                <flux:input wire:model="sort_order" type="number" min="0" />
                <flux:error name="sort_order" />
            </flux:field>

            <flux:checkbox wire:model="is_active" label="Categoría activa" />

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" wire:navigate>Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar cambios</flux:button>
            </div>
        </form>
    </flux:card>
</div>
