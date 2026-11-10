<?php

use App\Models\Category;
use App\Models\Project;
use App\Models\ProjectInterest;
use App\ProjectInterestStatus;
use App\ProjectStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $categoryId = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'categoryId'], true)) {
            $this->resetPage();
        }
    }

    public function toggleProjectHidden(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $project->update(['is_hidden' => ! $project->is_hidden]);
    }

    public function deleteProject(int $projectId): void
    {
        Project::findOrFail($projectId)->delete();
    }

    /**
     * @return array{projects: LengthAwarePaginator<int, Project>, categories: Collection<int, Category>, counts: array{total: int, open: int, in_progress: int, closed: int, interests: int}}
     */
    public function with(): array
    {
        return [
            'projects' => Project::query()
                ->with(['homeowner', 'subcategory.category', 'interests' => fn ($query) => $query->with('tenant')->latest()])
                ->withCount('interests')
                ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('title', 'like', "%{$this->search}%")
                    ->orWhere('city', 'like', "%{$this->search}%")
                    ->orWhereHas('homeowner', fn ($query) => $query
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%"))))
                ->when($this->status === 'hidden', fn ($query) => $query->where('is_hidden', true))
                ->when($this->status && $this->status !== 'hidden', fn ($query) => $query->where('status', $this->status))
                ->when($this->categoryId, fn ($query) => $query->whereHas('subcategory', fn ($query) => $query->where('category_id', $this->categoryId)))
                ->latest()
                ->paginate(20),
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'counts' => [
                'total' => Project::count(),
                'open' => Project::where('status', ProjectStatus::Open)->count(),
                'in_progress' => Project::where('status', ProjectStatus::InProgress)->count(),
                'closed' => Project::where('status', ProjectStatus::Closed)->count(),
                'interests' => ProjectInterest::count(),
            ],
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">Proyectos</flux:heading>
        <flux:text class="mt-1">Todos los proyectos publicados por los dueños de casa.</flux:text>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
        @foreach ([
            ['Total', $counts['total'], 'text-zinc-900 dark:text-white'],
            ['Abiertos', $counts['open'], 'text-green-500'],
            ['En progreso', $counts['in_progress'], 'text-yellow-500'],
            ['Cerrados', $counts['closed'], 'text-zinc-400'],
            ['Respuestas', $counts['interests'], 'text-sky-500'],
        ] as [$label, $value, $color])
            <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900" data-project-stat>
                <div class="text-xs text-zinc-500">{{ $label }}</div>
                <div class="text-2xl font-bold {{ $color }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-[1fr_12rem_14rem]">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por título, ciudad o dueño de casa..." icon="magnifying-glass" />
        <flux:select wire:model.live="status">
            <flux:select.option value="">Todos los estados</flux:select.option>
            @foreach (ProjectStatus::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
            <flux:select.option value="hidden">Ocultos</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="categoryId">
            <flux:select.option value="">Todas las categorías</flux:select.option>
            @foreach ($categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="flex flex-col gap-3">
        @forelse ($projects as $project)
            <details wire:key="project-{{ $project->id }}" wire:ignore.self @class(['group rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900', 'opacity-60' => $project->is_hidden])>
                <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3">
                    <flux:icon.chevron-right class="size-4 text-zinc-400 transition group-open:rotate-90" />
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium">{{ $project->title }}</div>
                        <div class="truncate text-xs text-zinc-400">
                            {{ $project->subcategory?->category?->label() }} › {{ $project->subcategory?->label() }}
                            · {{ $project->homeowner?->name }}
                        </div>
                    </div>
                    <span class="text-sm text-zinc-500">{{ collect([$project->city, $project->state])->filter()->implode(', ') }}</span>
                    <flux:badge size="sm" :color="match ($project->status) { ProjectStatus::Open => 'green', ProjectStatus::InProgress => 'yellow', ProjectStatus::Closed => 'zinc' }">{{ $project->status->label() }}</flux:badge>
                    @if ($project->is_hidden)
                        <flux:badge size="sm" color="red">Oculto</flux:badge>
                    @endif
                    <flux:badge size="sm" color="sky">{{ $project->interests_count }} {{ $project->interests_count === 1 ? 'respuesta' : 'respuestas' }}</flux:badge>
                    <span class="w-20 text-right text-sm text-zinc-400">{{ $project->created_at->format('d/m/Y') }}</span>
                    <flux:button
                        wire:click.prevent="toggleProjectHidden({{ $project->id }})"
                        size="sm"
                        variant="ghost"
                        :icon="$project->is_hidden ? 'eye' : 'eye-slash'"
                        title="{{ $project->is_hidden ? 'Mostrar a los contratistas' : 'Ocultar a los contratistas' }}"
                    />
                    <flux:button
                        wire:click.prevent="deleteProject({{ $project->id }})"
                        wire:confirm="¿Eliminar el proyecto «{{ $project->title }}»? También se borrarán sus respuestas."
                        size="sm"
                        variant="ghost"
                        icon="trash"
                        class="text-red-500"
                        title="Eliminar"
                    />
                </summary>

                <div class="grid gap-4 border-t border-zinc-200 px-4 py-4 text-sm lg:grid-cols-2 dark:border-zinc-700">
                    <div>
                        <p class="whitespace-pre-line text-zinc-600 dark:text-zinc-300">{{ $project->description }}</p>
                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div><dt class="text-zinc-400">Para cuándo</dt><dd>{{ $project->urgency->label() }}</dd></div>
                            <div><dt class="text-zinc-400">Presupuesto</dt><dd>{{ $project->budget?->label() ?? '—' }}</dd></div>
                            <div class="col-span-2">
                                <dt class="text-zinc-400">Dueño de casa</dt>
                                <dd>
                                    {{ $project->homeowner?->name }} ·
                                    <a href="mailto:{{ $project->homeowner?->email }}" class="hover:text-yellow-500 hover:underline">{{ $project->homeowner?->email }}</a>
                                    @if ($project->homeowner?->phone)
                                        · {{ $project->homeowner->phone }}
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <div class="mb-2 font-medium">Contratistas que respondieron</div>
                        @forelse ($project->interests as $interest)
                            <div wire:key="interest-{{ $interest->id }}" class="mb-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium">{{ $interest->tenant?->name ?? $interest->tenant_id }}</span>
                                    <flux:badge size="sm" :color="match ($interest->status) { ProjectInterestStatus::Pending => 'yellow', ProjectInterestStatus::Accepted => 'green', ProjectInterestStatus::Declined => 'zinc' }">{{ $interest->status->label() }}</flux:badge>
                                </div>
                                <p class="mt-1 text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit($interest->message, 160) }}</p>
                            </div>
                        @empty
                            <p class="text-zinc-400">Todavía nadie ha respondido.</p>
                        @endforelse
                    </div>
                </div>
            </details>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 py-10 text-center text-zinc-400 dark:border-zinc-700">
                {{ $search || $status || $categoryId ? 'No hay proyectos que coincidan con los filtros.' : 'Todavía no hay proyectos publicados.' }}
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $projects->links() }}
    </div>
</div>
