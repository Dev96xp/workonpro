<?php

use App\Models\Category;
use App\Models\ServiceListing;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public function toggleActive(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        if (ServiceListing::where('category', $category->slug)->exists()) {
            return;
        }

        $category->delete();
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'usedSlugs' => ServiceListing::query()->distinct()->pluck('category'),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Categorías de servicio</flux:heading>
        <flux:button href="{{ route('admin.categories.create') }}" variant="primary" icon="plus" wire:navigate>
            Nueva categoría
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Nombre</flux:table.column>
            <flux:table.column>Slug</flux:table.column>
            <flux:table.column>Orden</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($categories as $category)
                <flux:table.row>
                    <flux:table.cell class="font-medium">{{ $category->name }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-400">{{ $category->slug }}</flux:table.cell>
                    <flux:table.cell>{{ $category->sort_order }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button wire:click="toggleActive({{ $category->id }})" size="sm" variant="ghost">
                            @if ($category->is_active)
                                <flux:badge color="green" size="sm">Activa</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Inactiva</flux:badge>
                            @endif
                        </flux:button>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-2">
                            <flux:button href="{{ route('admin.categories.edit', $category) }}" size="sm" variant="ghost" icon="pencil" wire:navigate />
                            <flux:button
                                wire:click="deleteCategory({{ $category->id }})"
                                wire:confirm="¿Eliminar esta categoría?"
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                class="text-red-500"
                                :disabled="$usedSlugs->contains($category->slug)"
                                title="{{ $usedSlugs->contains($category->slug) ? 'No se puede eliminar: hay servicios usando esta categoría' : 'Eliminar' }}"
                            />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell class="text-center text-zinc-400">No hay categorías registradas.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
