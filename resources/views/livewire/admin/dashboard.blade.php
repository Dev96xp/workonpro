<?php

use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public int $totalTenants = 0;
    public int $tenantsThisMonth = 0;
    public int $totalDomains = 0;

    public function mount(): void
    {
        $this->totalTenants = Tenant::count();
        $this->tenantsThisMonth = Tenant::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $this->totalDomains = \Stancl\Tenancy\Database\Models\Domain::count();
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Dashboard</flux:heading>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card>
            <div class="flex items-center gap-4">
                <flux:icon name="building-office-2" class="size-8 text-zinc-400" />
                <div>
                    <flux:heading size="lg">{{ $totalTenants }}</flux:heading>
                    <flux:subheading>Total Negocios</flux:subheading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <flux:icon name="calendar" class="size-8 text-zinc-400" />
                <div>
                    <flux:heading size="lg">{{ $tenantsThisMonth }}</flux:heading>
                    <flux:subheading>Nuevos este mes</flux:subheading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center gap-4">
                <flux:icon name="globe-alt" class="size-8 text-zinc-400" />
                <div>
                    <flux:heading size="lg">{{ $totalDomains }}</flux:heading>
                    <flux:subheading>Dominios activos</flux:subheading>
                </div>
            </div>
        </flux:card>
    </div>

    <div class="mt-8">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="lg">Negocios recientes</flux:heading>
            <flux:button href="{{ route('admin.tenants.index') }}" variant="ghost" size="sm" wire:navigate>
                Ver todos
            </flux:button>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Negocio</flux:table.column>
                <flux:table.column>Subdominio</flux:table.column>
                <flux:table.column>Registrado</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse (App\Models\Tenant::with('domains')->latest()->limit(5)->get() as $tenant)
                    <flux:table.row>
                        <flux:table.cell>{{ $tenant->name }}</flux:table.cell>
                        <flux:table.cell>{{ $tenant->domains->first()?->domain ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $tenant->created_at->diffForHumans() }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell class="text-center text-zinc-400">No hay negocios registrados aún.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
