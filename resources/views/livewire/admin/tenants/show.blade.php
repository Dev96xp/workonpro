<?php

use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public Tenant $tenant;

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant->load('domains');
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button href="{{ route('admin.tenants.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
            <flux:heading size="xl">{{ $tenant->name }}</flux:heading>
        </div>
        <flux:button href="{{ route('admin.tenants.edit', $tenant) }}" variant="primary" icon="pencil" wire:navigate>
            Editar
        </flux:button>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <flux:card>
            <flux:heading size="lg" class="mb-4">Información general</flux:heading>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <flux:subheading>ID / Tenant</flux:subheading>
                    <span class="font-mono text-sm">{{ $tenant->id }}</span>
                </div>
                <div class="flex justify-between">
                    <flux:subheading>Nombre</flux:subheading>
                    <span class="text-sm">{{ $tenant->name }}</span>
                </div>
                <div class="flex justify-between">
                    <flux:subheading>Registrado</flux:subheading>
                    <span class="text-sm">{{ $tenant->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </dl>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">Dominios</flux:heading>
            @forelse ($tenant->domains as $domain)
                <div class="flex items-center justify-between rounded bg-zinc-100 px-3 py-2 dark:bg-zinc-700">
                    <span class="font-mono text-sm">{{ $domain->domain }}</span>
                    <flux:badge color="green" size="sm">Activo</flux:badge>
                </div>
            @empty
                <p class="text-sm text-zinc-400">Sin dominios configurados.</p>
            @endforelse
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">Base de datos</flux:heading>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <flux:subheading>Nombre BD</flux:subheading>
                    <span class="font-mono text-sm">tenant{{ $tenant->id }}</span>
                </div>
            </dl>
        </flux:card>
    </div>
</div>
