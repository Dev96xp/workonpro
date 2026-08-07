<?php

use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public string $search = '';

    public ?string $backupError = null;

    public function deleteTenant(string $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->delete();
        $this->dispatch('tenant-deleted');
    }

    public function downloadBackup(string $tenantId): mixed
    {
        $this->backupError = null;

        $tenant = Tenant::findOrFail($tenantId);
        $mysqldump = config('services.mysqldump.path');

        try {
            (new Process([$mysqldump, '--version']))->mustRun();
        } catch (ProcessFailedException) {
            $this->backupError = 'No se encontró la herramienta mysqldump en el servidor.';

            return null;
        }

        $connection = config('database.connections.mysql');
        $database = $tenant->database()->getName();
        $filename = $database.'_'.now()->format('Y-m-d_His').'.sql';

        return response()->streamDownload(function () use ($mysqldump, $connection, $database) {
            $process = new Process([
                $mysqldump,
                '--host='.$connection['host'],
                '--port='.$connection['port'],
                '--user='.$connection['username'],
                '--single-transaction',
                '--no-tablespaces',
                $database,
            ]);
            $process->setTimeout(300);
            $process->run(function ($type, $buffer) {
                echo $buffer;
            }, ['MYSQL_PWD' => $connection['password']]);
        }, $filename, ['Content-Type' => 'application/sql']);
    }

    public function with(): array
    {
        return [
            'tenants' => Tenant::with('domains')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('id', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(10),
            'centralDomain' => parse_url(config('app.url'), PHP_URL_HOST),
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

    @if ($backupError)
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
            {{ $backupError }}
        </div>
    @endif

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o ID..." icon="magnifying-glass" />
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Negocio</flux:table.column>
            <flux:table.column>Subdominio</flux:table.column>
            <flux:table.column>Base de datos</flux:table.column>
            <flux:table.column>Ciudad de registro</flux:table.column>
            <flux:table.column>Registrado</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($tenants as $tenant)
                <flux:table.row>
                    <flux:table.cell>
                        @if ($tenant->domains->first())
                            <a href="{{ request()->getScheme() }}://{{ $tenant->domains->first()->domain }}.{{ $centralDomain }}" target="_blank" class="font-medium hover:text-yellow-500 hover:underline">
                                {{ $tenant->name }}
                            </a>
                        @else
                            <div class="font-medium">{{ $tenant->name }}</div>
                        @endif
                        <div class="text-xs text-zinc-400">{{ $tenant->id }}</div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $tenant->domains->first()?->domain ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">tenant{{ $tenant->id }}</flux:table.cell>
                    <flux:table.cell>{{ $tenant->signup_city ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $tenant->created_at->format('d/m/Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            @if ($tenant->domains->first())
                                <flux:button
                                    href="{{ request()->getScheme() }}://{{ $tenant->domains->first()->domain }}.{{ $centralDomain }}"
                                    target="_blank"
                                    size="sm"
                                    variant="ghost"
                                    icon="arrow-top-right-on-square"
                                    title="Abrir sitio del negocio"
                                />
                            @endif
                            <flux:button href="{{ route('admin.tenants.show', $tenant) }}" size="sm" variant="ghost" icon="eye" wire:navigate />
                            <flux:button href="{{ route('admin.tenants.edit', $tenant) }}" size="sm" variant="ghost" icon="pencil" wire:navigate />
                            <flux:button wire:click="deleteTenant('{{ $tenant->id }}')" wire:confirm="¿Eliminar este negocio y su base de datos?" size="sm" variant="ghost" icon="trash" class="text-red-500" />
                            <flux:button
                                wire:click="downloadBackup('{{ $tenant->id }}')"
                                wire:confirm="¿Generar y descargar un backup completo de la base de datos de este negocio?"
                                size="sm"
                                variant="ghost"
                                icon="archive-box-arrow-down"
                                title="Descargar backup de la base de datos"
                            />
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
