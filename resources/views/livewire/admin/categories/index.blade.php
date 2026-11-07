<?php

use App\Models\Category;
use App\Models\ServiceListing;
use App\Models\Subcategory;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public function toggleActive(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function toggleSubcategoryActive(int $subcategoryId): void
    {
        $subcategory = Subcategory::findOrFail($subcategoryId);
        $subcategory->update(['is_active' => ! $subcategory->is_active]);
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = Category::withCount('subcategories')->findOrFail($categoryId);

        if ($category->subcategories_count > 0 || ServiceListing::where('category', $category->slug)->exists()) {
            return;
        }

        $category->delete();
    }

    public function deleteSubcategory(int $subcategoryId): void
    {
        $subcategory = Subcategory::findOrFail($subcategoryId);

        if (ServiceListing::where('subcategory', $subcategory->slug)->exists()) {
            return;
        }

        $subcategory->delete();
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()->with('subcategories')->orderBy('sort_order')->get(),
            'usedSubcategorySlugs' => ServiceListing::query()->whereNotNull('subcategory')->distinct()->pluck('subcategory'),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Categorías de servicio</flux:heading>
            <flux:text class="mt-1">{{ $categories->count() }} categorías · {{ $categories->sum(fn ($category) => $category->subcategories->count()) }} subcategorías</flux:text>
        </div>
        <flux:button href="{{ route('admin.categories.create') }}" variant="primary" icon="plus" wire:navigate>
            Nueva categoría
        </flux:button>
    </div>

    <div class="flex flex-col gap-3">
        @forelse ($categories as $category)
            @php($canDeleteCategory = $category->subcategories->isEmpty())
            <details wire:key="category-{{ $category->id }}" wire:ignore.self class="group rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <summary class="flex cursor-pointer list-none items-center gap-4 px-4 py-3">
                    <flux:icon.chevron-right class="size-4 text-zinc-400 transition group-open:rotate-90" />
                    <div class="flex-1">
                        <span class="font-medium">{{ $category->name }}</span>
                        <span class="ml-2 font-mono text-xs text-zinc-400">{{ $category->slug }}</span>
                    </div>
                    <flux:badge size="sm" color="zinc">{{ $category->subcategories->count() }} subcategorías</flux:badge>
                    <span class="w-10 text-right text-sm text-zinc-400">#{{ $category->sort_order }}</span>
                    <flux:button wire:click.prevent="toggleActive({{ $category->id }})" size="sm" variant="ghost">
                        @if ($category->is_active)
                            <flux:badge color="green" size="sm">Activa</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactiva</flux:badge>
                        @endif
                    </flux:button>
                    <flux:button href="{{ route('admin.categories.edit', $category) }}" size="sm" variant="ghost" icon="pencil" wire:navigate />
                    <flux:button
                        wire:click.prevent="deleteCategory({{ $category->id }})"
                        wire:confirm="¿Eliminar esta categoría?"
                        size="sm"
                        variant="ghost"
                        icon="trash"
                        class="text-red-500"
                        :disabled="! $canDeleteCategory"
                        title="{{ $canDeleteCategory ? 'Eliminar' : 'Primero elimine o mueva sus subcategorías' }}"
                    />
                </summary>

                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Subcategoría</flux:table.column>
                            <flux:table.column>Inglés</flux:table.column>
                            <flux:table.column>Slug</flux:table.column>
                            <flux:table.column>Orden</flux:table.column>
                            <flux:table.column>Estado</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @forelse ($category->subcategories as $subcategory)
                                @php($isUsed = $usedSubcategorySlugs->contains($subcategory->slug))
                                <flux:table.row wire:key="subcategory-{{ $subcategory->id }}">
                                    <flux:table.cell class="font-medium">{{ $subcategory->name }}</flux:table.cell>
                                    <flux:table.cell class="text-zinc-500">{{ $subcategory->name_en ?? '—' }}</flux:table.cell>
                                    <flux:table.cell class="font-mono text-xs text-zinc-400">{{ $subcategory->slug }}</flux:table.cell>
                                    <flux:table.cell>{{ $subcategory->sort_order }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button wire:click="toggleSubcategoryActive({{ $subcategory->id }})" size="sm" variant="ghost">
                                            @if ($subcategory->is_active)
                                                <flux:badge color="green" size="sm">Activa</flux:badge>
                                            @else
                                                <flux:badge color="zinc" size="sm">Inactiva</flux:badge>
                                            @endif
                                        </flux:button>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <div class="flex items-center justify-end gap-2">
                                            <flux:button href="{{ route('admin.subcategories.edit', $subcategory) }}" size="sm" variant="ghost" icon="pencil" wire:navigate />
                                            <flux:button
                                                wire:click="deleteSubcategory({{ $subcategory->id }})"
                                                wire:confirm="¿Eliminar esta subcategoría?"
                                                size="sm"
                                                variant="ghost"
                                                icon="trash"
                                                class="text-red-500"
                                                :disabled="$isUsed"
                                                title="{{ $isUsed ? 'No se puede eliminar: hay servicios usando esta subcategoría' : 'Eliminar' }}"
                                            />
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="6" class="text-center text-zinc-400">Sin subcategorías todavía.</flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>

                    <div class="mt-3">
                        <flux:button href="{{ route('admin.categories.subcategories.create', $category) }}" size="sm" icon="plus" wire:navigate>
                            Agregar subcategoría
                        </flux:button>
                    </div>
                </div>
            </details>
        @empty
            <flux:card class="text-center text-zinc-400">No hay categorías registradas.</flux:card>
        @endforelse
    </div>
</div>
