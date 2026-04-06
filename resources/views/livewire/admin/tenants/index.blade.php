<?php

use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function deleteTenant(string $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->delete();
        $this->dispatch('tenant-deleted');
    }

    public function with(): array
    {
        return [
            'tenants' => Tenant::with('domains')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('id', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Negocios</flux:heading>
        <flux:button href="{{ route('admin.tenants.create') }}" variant="primary" icon="plus" wire:navigate>
            Nuevo negocio
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o ID..." icon="magnifying-glass" />
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Negocio</flux:table.column>
            <flux:table.column>Subdominio</flux:table.column>
            <flux:table.column>Base de datos</flux:table.column>
            <flux:table.column>Registrado</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($tenants as $tenant)
                <flux:table.row>
                    <flux:table.cell>
                        <div class="font-medium">{{ $tenant->name }}</div>
                        <div class="text-xs text-zinc-400">{{ $tenant->id }}</div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $tenant->domains->first()?->domain ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">tenant{{ $tenant->id }}</flux:table.cell>
                    <flux:table.cell>{{ $tenant->created_at->format('d/m/Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button href="{{ route('admin.tenants.show', $tenant) }}" size="sm" variant="ghost" icon="eye" wire:navigate />
                            <flux:button href="{{ route('admin.tenants.edit', $tenant) }}" size="sm" variant="ghost" icon="pencil" wire:navigate />
                            <flux:button wire:click="deleteTenant('{{ $tenant->id }}')" wire:confirm="¿Eliminar este negocio y su base de datos?" size="sm" variant="ghost" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell class="text-center text-zinc-400">No hay negocios registrados.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $tenants->links() }}
    </div>
</div>
