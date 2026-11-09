<?php

use App\Models\Project;
use App\ProjectInterestStatus;
use App\ProjectStatus;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.homeowner')] #[Title('Workon')] class extends Component {
    /**
     * @return array{projects: Collection<int, Project>}
     */
    public function with(): array
    {
        return [
            'projects' => auth('homeowner')->user()->projects()
                ->with('subcategory')
                ->withCount([
                    'interests',
                    'interests as pending_interests_count' => fn ($query) => $query->where('status', ProjectInterestStatus::Pending),
                ])
                ->latest()
                ->get(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-zinc-400">{{ __('homeowner.dashboard.greeting', ['name' => \Illuminate\Support\Str::before(auth('homeowner')->user()->name, ' ')]) }}</p>
            <h1 class="text-3xl font-black tracking-tight">{{ __('homeowner.dashboard.heading') }}</h1>
        </div>
        <a href="{{ route('homeowner.projects.create') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-yellow-400 px-6 py-3 font-extrabold text-zinc-950 transition hover:scale-105 hover:bg-yellow-300">
            + {{ __('homeowner.dashboard.new_project') }}
        </a>
    </div>

    @if ($projects->isEmpty())
        <div class="mt-10 rounded-2xl border border-dashed border-white/15 p-10 text-center">
            <p class="text-xl font-bold">{{ __('homeowner.dashboard.empty_title') }}</p>
            <p class="mt-2 text-zinc-400">{{ __('homeowner.dashboard.empty_body') }}</p>
            <a href="{{ route('homeowner.projects.create') }}" class="mt-6 inline-flex rounded-full bg-yellow-400 px-6 py-3 font-extrabold text-zinc-950 transition hover:bg-yellow-300">
                {{ __('homeowner.dashboard.new_project') }}
            </a>
        </div>
    @else
        <div class="mt-8 grid gap-4">
            @foreach ($projects as $project)
                <a href="{{ route('homeowner.projects.show', $project) }}" wire:key="project-{{ $project->id }}" class="group block rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:border-yellow-400/60 hover:bg-white/10">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-lg font-bold group-hover:text-yellow-400">{{ $project->title }}</p>
                            <p class="mt-1 text-sm text-zinc-400">
                                {{ $project->subcategory?->label() }} · {{ $project->city }}, {{ $project->state }} · {{ $project->urgency->label() }}
                            </p>
                        </div>
                        <span @class([
                            'rounded-full px-3 py-1 text-xs font-bold',
                            'bg-green-500/15 text-green-400' => $project->status === ProjectStatus::Open,
                            'bg-yellow-400/15 text-yellow-400' => $project->status === ProjectStatus::InProgress,
                            'bg-zinc-700 text-zinc-300' => $project->status === ProjectStatus::Closed,
                        ])>{{ $project->status->label() }}</span>
                    </div>
                    <p class="mt-3 text-sm text-zinc-300">
                        {{ trans_choice('homeowner.dashboard.interested_count', $project->interests_count, ['count' => $project->interests_count]) }}
                        @if ($project->pending_interests_count)
                            <span class="ml-2 rounded-full bg-yellow-400 px-2 py-0.5 text-xs font-extrabold text-zinc-950">{{ trans_choice('homeowner.dashboard.new_count', $project->pending_interests_count, ['count' => $project->pending_interests_count]) }}</span>
                        @endif
                    </p>
                </a>
            @endforeach
        </div>
    @endif
</div>
