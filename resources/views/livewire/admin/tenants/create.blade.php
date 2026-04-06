<?php

use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:63|alpha_dash|unique:tenants,id')]
    public string $subdomain = '';

    public function save(): void
    {
        $this->validate();

        $tenant = Tenant::create([
            'id' => $this->subdomain,
            'name' => $this->name,
        ]);

        $tenant->domains()->create([
            'domain' => $this->subdomain . '.' . config('app.domain', 'workonpro.test'),
        ]);

        $this->redirectRoute('admin.tenants.show', $tenant, navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 flex items-center gap-4">
        <flux:button href="{{ route('admin.tenants.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <flux:heading size="xl">Nuevo Negocio</flux:heading>
    </div>

    <flux:card class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:input wire:model="name" label="Nombre del negocio" placeholder="Ej: Constructora Acme" required />

            <flux:field>
                <flux:label>Subdominio</flux:label>
                <div class="flex items-center gap-2">
                    <flux:input wire:model="subdomain" placeholder="acme" class="flex-1" required />
                    <span class="text-sm text-zinc-400">.workonpro.test</span>
                </div>
                <flux:error name="subdomain" />
                <flux:description>Solo letras, números y guiones. Este será el ID del tenant.</flux:description>
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button href="{{ route('admin.tenants.index') }}" variant="ghost" wire:navigate>Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Crear negocio</flux:button>
            </div>
        </form>
    </flux:card>
</div>
