<?php

use App\Models\Tax;
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

    #[Validate('required|numeric|min:0|max:100')]
    public string $rate = '';

    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'invoices'), 403);
    }

    public function with(): array
    {
        return [
            'taxes' => Tax::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
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
        $tax = Tax::findOrFail($id);
        $this->editingId = $id;
        $this->name = $tax->name;
        $this->rate = (string) $tax->rate;
        $this->is_active = $tax->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'rate' => $this->rate,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Tax::findOrFail($this->editingId)->update($data);
        } else {
            Tax::create($data);
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
            Tax::findOrFail($this->deletingId)->delete();
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
        $this->rate = '';
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
                        <flux:heading size="xl">{{ __('tenant.taxes.heading') }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ __('tenant.taxes.subheading') }}</flux:text>
                    </div>
                    <flux:button wire:click="openCreate" variant="primary" icon="plus" class="sm:w-auto">
                        {{ __('tenant.taxes.new') }}
                    </flux:button>
                </div>

                <div class="mt-6">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('tenant.taxes.search_placeholder') }}" icon="magnifying-glass" />
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.taxes.rate_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.status') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($taxes as $tax)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4 font-medium">{{ $tax->name }}</td>
                                    <td class="px-6 py-4 text-zinc-500">{{ number_format($tax->rate, 2) }}%</td>
                                    <td class="px-6 py-4">
                                        @if ($tax->is_active)
                                            <flux:badge color="green" size="sm">{{ __('tenant.common.active') }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">{{ __('tenant.common.inactive') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <flux:button wire:click="openEdit({{ $tax->id }})" size="sm" icon="pencil" />
                                            <flux:button wire:click="confirmDelete({{ $tax->id }})" size="sm" icon="trash" variant="danger" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-zinc-500">
                                        {{ __('tenant.taxes.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($taxes->hasPages())
                        <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                            {{ $taxes->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </flux:main>
    </div>

    {{-- Modal Crear/Editar --}}
    <flux:modal wire:model="showModal" class="w-full max-w-sm">
        <flux:heading size="lg">{{ $editingId ? __('tenant.taxes.edit') : __('tenant.taxes.new') }}</flux:heading>

        <form wire:submit="save" class="mt-4 space-y-4">
            <flux:field>
                <flux:label>{{ __('tenant.common.name') }} <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="name" placeholder="Ej: Impuesto federal" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('tenant.taxes.rate_label') }} <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="rate" type="number" step="0.01" min="0" max="100" placeholder="7.00" />
                <flux:error name="rate" />
            </flux:field>

            <flux:field>
                <flux:checkbox wire:model="is_active" label="{{ __('tenant.taxes.active_checkbox') }}" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? __('tenant.common.save_changes') : __('tenant.taxes.new') }}</span>
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
            <flux:heading size="lg">{{ __('tenant.taxes.confirm_delete') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">{{ __('tenant.common.cannot_undo') }}</flux:text>
        </div>

        <div class="mt-6 flex justify-center gap-3">
            <flux:button wire:click="$set('showDeleteModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
            <flux:button wire:click="delete" variant="danger">{{ __('tenant.common.delete') }}</flux:button>
        </div>
    </flux:modal>
</div>
