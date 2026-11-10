<?php

use App\Models\Category;
use App\Models\Homeowner;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Tenant;
use App\ProjectStatus;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    /**
     * @return array{cards: list<array{route: string, icon: string, value: ?int, label: string, hint: string}>}
     */
    public function with(): array
    {
        $tenantsThisMonth = Tenant::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'cards' => [
                ['route' => 'admin.tenants.index', 'icon' => 'building-office-2', 'value' => Tenant::count(), 'label' => 'Negocios', 'hint' => $tenantsThisMonth.' nuevos este mes'],
                ['route' => 'admin.homeowners.index', 'icon' => 'home-modern', 'value' => Homeowner::count(), 'label' => 'Dueños de casa', 'hint' => Homeowner::where('created_at', '>=', now()->startOfMonth())->count().' nuevos este mes'],
                ['route' => 'admin.projects.index', 'icon' => 'briefcase', 'value' => Project::count(), 'label' => 'Proyectos', 'hint' => Project::where('status', ProjectStatus::Open)->count().' abiertos'],
                ['route' => 'admin.categories.index', 'icon' => 'tag', 'value' => Category::count(), 'label' => 'Categorías', 'hint' => 'Categorías y subcategorías'],
                ['route' => 'admin.plans.index', 'icon' => 'credit-card', 'value' => Plan::count(), 'label' => 'Planes', 'hint' => 'Precios y recursos'],
                ['route' => 'admin.settings.index', 'icon' => 'cog-6-tooth', 'value' => null, 'label' => 'Configuraciones', 'hint' => 'Ajustes y notificaciones'],
            ],
        ];
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Dashboard</flux:heading>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($cards as $card)
            <a href="{{ route($card['route']) }}" wire:navigate data-dashboard-card class="group flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-6 transition hover:border-yellow-400 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:icon :name="$card['icon']" class="size-8 text-zinc-400 transition group-hover:text-yellow-500" />
                <div class="min-w-0 flex-1">
                    @if ($card['value'] !== null)
                        <flux:heading size="lg">{{ $card['value'] }}</flux:heading>
                    @endif
                    <flux:subheading>{{ $card['label'] }}</flux:subheading>
                    <flux:text class="text-xs">{{ $card['hint'] }}</flux:text>
                </div>
                <flux:icon.chevron-right class="size-5 text-zinc-300 transition group-hover:translate-x-1 group-hover:text-yellow-500" />
            </a>
        @endforeach
    </div>

    <div class="mt-8">
        <a href="{{ route('admin.settings.notifications') }}" wire:navigate class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-6 transition hover:border-yellow-400 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:icon name="envelope" class="size-8 text-zinc-400" />
            <div>
                <flux:heading size="lg">Notificaciones a negocios</flux:heading>
                <flux:subheading>Enviar un mensaje por email a uno, varios, o todos los negocios</flux:subheading>
            </div>
        </a>
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
