<?php

use App\Models\Category;
use App\Models\ServiceListing;
use App\Models\Subcategory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public Subcategory $subcategory;

    #[Validate('required|exists:categories,id')]
    public int $category_id = 0;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $name_en = '';

    #[Validate('integer|min:0')]
    public int $sort_order = 0;

    public bool $is_active = true;

    public function mount(Subcategory $subcategory): void
    {
        $this->subcategory = $subcategory;
        $this->category_id = $subcategory->category_id;
        $this->name = $subcategory->name;
        $this->name_en = $subcategory->name_en ?? '';
        $this->sort_order = $subcategory->sort_order;
        $this->is_active = $subcategory->is_active;
    }

    public function save(): void
    {
        $this->validate();

        $movedToAnotherCategory = $this->subcategory->category_id !== $this->category_id;

        $this->subcategory->update([
            'category_id' => $this->category_id,
            'name' => $this->name,
            'name_en' => $this->name_en ?: null,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        if ($movedToAnotherCategory) {
            ServiceListing::query()
                ->where('subcategory', $this->subcategory->slug)
                ->update(['category' => $this->subcategory->category->slug]);
        }

        $this->redirectRoute('admin.categories.index', navigate: true);
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex items-center gap-4">
        <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <flux:heading size="xl">Editar: {{ $subcategory->name }}</flux:heading>
    </div>

    <flux:card class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:select wire:model="category_id" label="Categoría">
                @foreach ($categories as $option)
                    <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="name" label="Nombre (español)" required />

            <flux:input wire:model="name_en" label="Nombre (inglés)" />

            <flux:field>
                <flux:label>Slug</flux:label>
                <flux:input value="{{ $subcategory->slug }}" readonly disabled />
                <flux:description>El slug no se puede editar: es la clave guardada en los servicios existentes.</flux:description>
            </flux:field>

            <flux:field>
                <flux:label>Orden</flux:label>
                <flux:input wire:model="sort_order" type="number" min="0" />
                <flux:error name="sort_order" />
            </flux:field>

            <flux:checkbox wire:model="is_active" label="Subcategoría activa" />

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('admin.categories.index') }}" variant="ghost" wire:navigate>Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar cambios</flux:button>
            </div>
        </form>
    </flux:card>
</div>
