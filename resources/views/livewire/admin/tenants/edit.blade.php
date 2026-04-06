<?php

use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public Tenant $tenant;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:253')]
    public string $domain = '';

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant->load('domains');
        $this->name = $tenant->name;
        $this->domain = $tenant->domains->first()?->domain ?? '';
    }

    public function save(): void
    {
        $this->validate();

        $this->tenant->update(['name' => $this->name]);

        if ($this->tenant->domains->first()) {
            $this->tenant->domains->first()->update(['domain' => $this->domain]);
        }

        $this->redirectRoute('admin.tenants.show', $this->tenant, navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 flex items-center gap-4">
        <flux:button href="{{ route('admin.tenants.show', $tenant) }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <flux:heading size="xl">Editar: {{ $tenant->name }}</flux:heading>
    </div>

    <flux:card class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:input wire:model="name" label="Nombre del negocio" required />

            <flux:field>
                <flux:label>Dominio</flux:label>
                <flux:input wire:model="domain" required />
                <flux:description>Dominio completo (ej: acme.workonpro.test)</flux:description>
                <flux:error name="domain" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('admin.tenants.show', $tenant) }}" variant="ghost" wire:navigate>Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar cambios</flux:button>
            </div>
        </form>
    </flux:card>
</div>
