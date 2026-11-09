<?php

use App\Models\Homeowner;
use App\Models\Project;
use App\ProjectStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteHomeowner(int $homeownerId): void
    {
        Homeowner::findOrFail($homeownerId)->delete();
    }

    public function toggleProjectHidden(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $project->update(['is_hidden' => ! $project->is_hidden]);
    }

    /**
     * @return array{homeowners: LengthAwarePaginator<int, Homeowner>, totalHomeowners: int}
     */
    public function with(): array
    {
        return [
            'homeowners' => Homeowner::query()
                ->with(['projects' => fn ($query) => $query->with('subcategory.category')->withCount('interests')->latest()])
                ->withCount([
                    'projects',
                    'projects as open_projects_count' => fn ($query) => $query->where('status', ProjectStatus::Open),
                ])
                ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('city', 'like', "%{$this->search}%")))
                ->latest()
                ->paginate(15),
            'totalHomeowners' => Homeowner::count(),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Dueños de casa</flux:heading>
            <flux:text class="mt-1">{{ $totalHomeowners }} {{ $totalHomeowners === 1 ? 'registrado' : 'registrados' }}</flux:text>
        </div>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, correo o ciudad..." icon="magnifying-glass" />
    </div>

    <div class="flex flex-col gap-3">
        @forelse ($homeowners as $homeowner)
            <details wire:key="homeowner-{{ $homeowner->id }}" wire:ignore.self class="group rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3">
                    <flux:icon.chevron-right class="size-4 text-zinc-400 transition group-open:rotate-90" />
                    <div class="min-w-0 flex-1">
                        <div class="font-medium">{{ $homeowner->name }}</div>
                        <div class="text-xs text-zinc-400">
                            <a href="mailto:{{ $homeowner->email }}" class="hover:text-yellow-500 hover:underline">{{ $homeowner->email }}</a>
                            @if ($homeowner->phone)
                                · {{ $homeowner->phone }}
                            @endif
                        </div>
                    </div>
                    <span class="text-sm text-zinc-500">{{ collect([$homeowner->city, $homeowner->state])->filter()->implode(', ') ?: '—' }}</span>
                    <flux:badge size="sm" color="zinc">{{ $homeowner->projects_count }} {{ $homeowner->projects_count === 1 ? 'proyecto' : 'proyectos' }}</flux:badge>
                    @if ($homeowner->open_projects_count)
                        <flux:badge size="sm" color="green">{{ $homeowner->open_projects_count }} {{ $homeowner->open_projects_count === 1 ? 'abierto' : 'abiertos' }}</flux:badge>
                    @endif
                    <span class="w-20 text-right text-sm text-zinc-400">{{ $homeowner->created_at->format('d/m/Y') }}</span>
                    <flux:button
                        wire:click.prevent="deleteHomeowner({{ $homeowner->id }})"
                        wire:confirm="¿Eliminar a {{ $homeowner->name }}? También se borrarán sus proyectos."
                        size="sm"
                        variant="ghost"
                        icon="trash"
                        class="text-red-500"
                        title="Eliminar"
                    />
                </summary>

                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Proyecto</flux:table.column>
                            <flux:table.column>Categoría</flux:table.column>
                            <flux:table.column>Ciudad</flux:table.column>
                            <flux:table.column>Para cuándo</flux:table.column>
                            <flux:table.column>Presupuesto</flux:table.column>
                            <flux:table.column>Estado</flux:table.column>
                            <flux:table.column>Interesados</flux:table.column>
                            <flux:table.column>Publicado</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @forelse ($homeowner->projects as $project)
                                <flux:table.row wire:key="project-{{ $project->id }}" @class(['opacity-50' => $project->is_hidden])>
                                    <flux:table.cell class="max-w-xs">
                                        <div class="truncate font-medium" title="{{ $project->title }}">{{ $project->title }}</div>
                                        <div class="truncate text-xs text-zinc-400" title="{{ $project->description }}">{{ \Illuminate\Support\Str::limit($project->description, 80) }}</div>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm">
                                        <div>{{ $project->subcategory?->label() }}</div>
                                        <div class="text-xs text-zinc-400">{{ $project->subcategory?->category?->label() }}</div>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm">{{ collect([$project->city, $project->state])->filter()->implode(', ') }}</flux:table.cell>
                                    <flux:table.cell class="text-sm">{{ $project->urgency->label() }}</flux:table.cell>
                                    <flux:table.cell class="text-sm">{{ $project->budget?->label() ?? '—' }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge size="sm" :color="match ($project->status) { ProjectStatus::Open => 'green', ProjectStatus::InProgress => 'yellow', ProjectStatus::Closed => 'zinc' }">{{ $project->status->label() }}</flux:badge>
                                        @if ($project->is_hidden)
                                            <flux:badge size="sm" color="red" class="ml-1">Oculto</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $project->interests_count }}</flux:table.cell>
                                    <flux:table.cell class="text-sm">{{ $project->created_at->format('d/m/Y') }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button
                                            wire:click="toggleProjectHidden({{ $project->id }})"
                                            size="sm"
                                            variant="ghost"
                                            :icon="$project->is_hidden ? 'eye' : 'eye-slash'"
                                            title="{{ $project->is_hidden ? 'Mostrar a los contratistas' : 'Ocultar a los contratistas' }}"
                                        />
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="9" class="text-center text-zinc-400">Todavía no ha publicado proyectos.</flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>
            </details>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 py-10 text-center text-zinc-400 dark:border-zinc-700">
                {{ $search ? 'No hay dueños de casa que coincidan con la búsqueda.' : 'Todavía no hay dueños de casa registrados.' }}
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $homeowners->links() }}
    </div>
</div>
