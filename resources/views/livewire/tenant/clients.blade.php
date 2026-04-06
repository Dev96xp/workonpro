<?php

use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.tenant')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/'));
    }

    // Modal state
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    // Form fields
    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('nullable|email|max:100')]
    public string $email = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    #[Validate('nullable|string|max:100')]
    public string $company = '';

    #[Validate('nullable|string|max:255')]
    public string $address = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    public bool $is_active = true;

    public function with(): array
    {
        return [
            'clients' => Client::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('company', 'like', "%{$this->search}%"))
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
        $client = Client::findOrFail($id);
        $this->editingId = $id;
        $this->name = $client->name;
        $this->email = $client->email ?? '';
        $this->phone = $client->phone ?? '';
        $this->company = $client->company ?? '';
        $this->address = $client->address ?? '';
        $this->notes = $client->notes ?? '';
        $this->is_active = $client->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'      => $this->name,
            'email'     => $this->email ?: null,
            'phone'     => $this->phone ?: null,
            'company'   => $this->company ?: null,
            'address'   => $this->address ?: null,
            'notes'     => $this->notes ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Client::findOrFail($this->editingId)->update($data);
        } else {
            Client::create($data);
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
            Client::findOrFail($this->deletingId)->delete();
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->company = '';
        $this->address = '';
        $this->notes = '';
        $this->is_active = true;
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    {{-- Navbar --}}
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="p-6">
                {{-- Header --}}
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="xl">Clientes</flux:heading>
                        <flux:text class="text-zinc-500">Gestiona los clientes de tu negocio</flux:text>
                    </div>
                    <flux:button wire:click="openCreate" variant="primary" icon="plus">
                        Nuevo cliente
                    </flux:button>
                </div>

                {{-- Búsqueda --}}
                <div class="mt-6">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, email o empresa..." icon="magnifying-glass" />
                </div>

                {{-- Tabla --}}
                <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Teléfono</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Empresa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($clients as $client)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4 font-medium">{{ $client->name }}</td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $client->email ?? '—' }}</td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $client->phone ?? '—' }}</td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $client->company ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        @if ($client->is_active)
                                            <flux:badge color="green" size="sm">Activo</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Inactivo</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <flux:button wire:click="openEdit({{ $client->id }})" size="sm" icon="pencil" />
                                            <flux:button wire:click="confirmDelete({{ $client->id }})" size="sm" icon="trash" variant="danger" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                                        No hay clientes registrados aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($clients->hasPages())
                        <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                            {{ $clients->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </flux:main>
    </div>

    {{-- Modal Crear/Editar --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <flux:heading size="lg">{{ $editingId ? 'Editar cliente' : 'Nuevo cliente' }}</flux:heading>

        <form wire:submit="save" class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>Nombre <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="name" placeholder="Nombre completo" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input wire:model="email" type="email" placeholder="email@ejemplo.com" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono</flux:label>
                    <flux:input wire:model="phone" placeholder="+1 234 567 8900" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>Empresa</flux:label>
                    <flux:input wire:model="company" placeholder="Nombre de la empresa" />
                    <flux:error name="company" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>Dirección</flux:label>
                    <flux:input wire:model="address" placeholder="Dirección completa" />
                    <flux:error name="address" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>Notas</flux:label>
                    <flux:textarea wire:model="notes" placeholder="Notas adicionales..." rows="3" />
                    <flux:error name="notes" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="is_active" label="Cliente activo" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? 'Guardar cambios' : 'Crear cliente' }}</span>
                    <span wire:loading>Guardando...</span>
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
            <flux:heading size="lg">¿Eliminar cliente?</flux:heading>
            <flux:text class="mt-2 text-zinc-500">Esta acción no se puede deshacer.</flux:text>
        </div>

        <div class="mt-6 flex justify-center gap-3">
            <flux:button wire:click="$set('showDeleteModal', false)">Cancelar</flux:button>
            <flux:button wire:click="delete" variant="danger">Eliminar</flux:button>
        </div>
    </flux:modal>
</div>
