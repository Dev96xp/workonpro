<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.tenant')] class extends Component {
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|exists:product_categories,id')]
    public string $product_category_id = '';

    #[Validate('required|numeric|min:0')]
    public string $unit_price = '';

    #[Validate('nullable|string|max:20')]
    public string $unit = '';

    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'invoices'), 403);
    }

    public function with(): array
    {
        return [
            'products' => Product::query()
                ->with('category')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
            'categoryOptions' => ProductCategory::orderBy('name')->get(),
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->editingId = $id;
        $this->name = $product->name;
        $this->product_category_id = (string) $product->product_category_id;
        $this->unit_price = (string) $product->unit_price;
        $this->unit = $product->unit ?? '';
        $this->is_active = $product->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'product_category_id' => $this->product_category_id,
            'unit_price' => $this->unit_price,
            'unit' => $this->unit ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Product::findOrFail($this->editingId)->update($data);
        } else {
            Product::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Product::findOrFail($this->deletingId)->delete();
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/'));
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->product_category_id = '';
        $this->unit_price = '';
        $this->unit = '';
        $this->is_active = true;
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading size="xl">{{ __('tenant.products.heading') }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ __('tenant.products.subheading') }}</flux:text>
                    </div>
                    <flux:button wire:click="openCreate" variant="primary" icon="plus" class="sm:w-auto">
                        {{ __('tenant.products.new') }}
                    </flux:button>
                </div>

                <div class="mt-6">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('tenant.products.search_placeholder') }}" icon="magnifying-glass" />
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.category') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.price') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.products.unit_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.status') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($products as $product)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4 font-medium">{{ $product->name }}</td>
                                    <td class="px-6 py-4 text-zinc-500">
                                        <flux:badge size="sm">{{ $product->category->name }}</flux:badge>
                                    </td>
                                    <td class="px-6 py-4 text-zinc-500">${{ number_format($product->unit_price, 2) }}</td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $product->unit ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        @if ($product->is_active)
                                            <flux:badge color="green" size="sm">{{ __('tenant.common.active') }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">{{ __('tenant.common.inactive') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <flux:button wire:click="openEdit({{ $product->id }})" size="sm" icon="pencil" />
                                            <flux:button wire:click="confirmDelete({{ $product->id }})" size="sm" icon="trash" variant="danger" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                                        {{ __('tenant.products.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($products->hasPages())
                        <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </flux:main>
    </div>

    {{-- Modal Crear/Editar --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <flux:heading size="lg">{{ $editingId ? __('tenant.products.edit') : __('tenant.products.new') }}</flux:heading>

        <form wire:submit="save" class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.common.name') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="name" placeholder="Ej: Concreto" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.common.category') }} <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model="product_category_id">
                        <flux:select.option value="">{{ __('tenant.products.select_category') }}</flux:select.option>
                        @foreach ($categoryOptions as $option)
                            <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="product_category_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('tenant.common.price') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="unit_price" type="number" step="0.01" min="0" placeholder="0.00" />
                    <flux:error name="unit_price" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('tenant.products.unit_label') }}</flux:label>
                    <flux:input wire:model="unit" placeholder="Ej: m³, saco, hora" />
                    <flux:error name="unit" />
                </flux:field>

                <flux:field class="flex items-end">
                    <flux:checkbox wire:model="is_active" label="{{ __('tenant.products.active_checkbox') }}" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? __('tenant.common.save_changes') : __('tenant.products.new') }}</span>
                    <span wire:loading>{{ __('tenant.common.saving') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal Confirmar Eliminar --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="text-center">
            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                <flux:icon.trash class="size-6 text-red-600 dark:text-red-400" />
            </div>
            <flux:heading size="lg">{{ __('tenant.products.confirm_delete') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">{{ __('tenant.common.cannot_undo') }}</flux:text>
        </div>

        <div class="mt-6 flex justify-center gap-3">
            <flux:button wire:click="$set('showDeleteModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
            <flux:button wire:click="delete" variant="danger">{{ __('tenant.common.delete') }}</flux:button>
        </div>
    </flux:modal>
</div>
