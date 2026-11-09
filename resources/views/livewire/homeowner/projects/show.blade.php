<?php

use App\Mail\ProjectInterestAccepted;
use App\Models\Project;
use App\Models\ProjectInterest;
use App\Models\Tenant;
use App\ProjectInterestStatus;
use App\ProjectStatus;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.homeowner')] #[Title('Workon')] class extends Component {
    public Project $project;

    public function mount(Project $project): void
    {
        abort_unless($project->homeowner_id === auth('homeowner')->id(), 404);

        $this->project = $project;
    }

    public function respond(int $interestId, string $decision): void
    {
        $status = ProjectInterestStatus::from($decision);

        abort_if($status === ProjectInterestStatus::Pending, 422);

        $interest = $this->project->interests()->findOrFail($interestId);

        $interest->update([
            'status' => $status,
            'responded_at' => now(),
        ]);

        if ($status === ProjectInterestStatus::Accepted && $interest->tenant?->email) {
            try {
                Mail::to($interest->tenant->email)->send(new ProjectInterestAccepted($interest));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    public function changeStatus(string $status): void
    {
        $this->project->update(['status' => ProjectStatus::from($status)]);
    }

    public function tenantUrl(Tenant $tenant): ?string
    {
        $domain = $tenant->domains->first()?->domain;

        return $domain ? request()->getScheme().'://'.$domain.'.'.parse_url(config('app.url'), PHP_URL_HOST) : null;
    }

    /**
     * @return array{interests: \Illuminate\Database\Eloquent\Collection<int, ProjectInterest>}
     */
    public function with(): array
    {
        return [
            'interests' => $this->project->interests()->with('tenant.domains')->latest()->get(),
        ];
    }
}; ?>

<div>
    <a href="{{ route('homeowner.dashboard') }}" class="text-sm text-zinc-400 hover:text-yellow-400">← {{ __('homeowner.nav.my_projects') }}</a>

    @if (session('status'))
        <div class="mt-4 rounded-xl border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm font-semibold text-green-400">{{ session('status') }}</div>
    @endif

    <div class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-2xl font-black tracking-tight sm:text-3xl">{{ $project->title }}</h1>
                <p class="mt-1 text-sm text-zinc-400">
                    {{ $project->subcategory?->category?->label() }} › {{ $project->subcategory?->label() }}
                </p>
            </div>
            <span @class([
                'rounded-full px-3 py-1 text-xs font-bold',
                'bg-green-500/15 text-green-400' => $project->status === ProjectStatus::Open,
                'bg-yellow-400/15 text-yellow-400' => $project->status === ProjectStatus::InProgress,
                'bg-zinc-700 text-zinc-300' => $project->status === ProjectStatus::Closed,
            ])>{{ $project->status->label() }}</span>
        </div>

        <p class="mt-5 whitespace-pre-line text-zinc-200">{{ $project->description }}</p>

        <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-zinc-500">{{ __('homeowner.fields.location') }}</dt>
                <dd class="font-semibold">{{ collect([$project->city, $project->state, $project->zip])->filter()->implode(', ') }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500">{{ __('homeowner.fields.urgency') }}</dt>
                <dd class="font-semibold">{{ $project->urgency->label() }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500">{{ __('homeowner.fields.budget') }}</dt>
                <dd class="font-semibold">{{ $project->budget?->label() ?? '—' }}</dd>
            </div>
        </dl>

        @if ($project->is_hidden)
            <p class="mt-5 rounded-xl bg-red-500/10 px-4 py-3 text-sm text-red-400">{{ __('homeowner.show.hidden_notice') }}</p>
        @endif

        <div class="mt-6 flex flex-wrap gap-2 border-t border-white/10 pt-5">
            <a href="{{ route('homeowner.projects.edit', $project) }}" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold transition hover:border-yellow-400 hover:text-yellow-400">{{ __('homeowner.show.edit') }}</a>
            @if ($project->status !== ProjectStatus::InProgress)
                <button type="button" wire:click="changeStatus('{{ ProjectStatus::InProgress->value }}')" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold transition hover:border-yellow-400 hover:text-yellow-400">{{ __('homeowner.show.mark_in_progress') }}</button>
            @endif
            @if ($project->status !== ProjectStatus::Closed)
                <button type="button" wire:click="changeStatus('{{ ProjectStatus::Closed->value }}')" wire:confirm="{{ __('homeowner.show.close_confirm') }}" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold transition hover:border-yellow-400 hover:text-yellow-400">{{ __('homeowner.show.mark_closed') }}</button>
            @endif
            @if ($project->status !== ProjectStatus::Open)
                <button type="button" wire:click="changeStatus('{{ ProjectStatus::Open->value }}')" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold transition hover:border-yellow-400 hover:text-yellow-400">{{ __('homeowner.show.reopen') }}</button>
            @endif
        </div>
    </div>

    <h2 class="mt-10 text-xl font-extrabold">{{ __('homeowner.show.interested_heading') }}</h2>

    @if ($interests->isEmpty())
        <p class="mt-3 rounded-2xl border border-dashed border-white/15 p-6 text-zinc-400">{{ __('homeowner.show.no_interests') }}</p>
    @else
        <div class="mt-4 grid gap-4">
            @foreach ($interests as $interest)
                @php($url = $interest->tenant ? $this->tenantUrl($interest->tenant) : null)
                <div wire:key="interest-{{ $interest->id }}" class="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-lg font-bold">{{ $interest->tenant?->name }}</p>
                            <p class="text-sm text-zinc-400">{{ collect([$interest->tenant?->city, $interest->tenant?->state])->filter()->implode(', ') }}</p>
                        </div>
                        <span @class([
                            'rounded-full px-3 py-1 text-xs font-bold',
                            'bg-yellow-400 text-zinc-950' => $interest->status === ProjectInterestStatus::Pending,
                            'bg-green-500/15 text-green-400' => $interest->status === ProjectInterestStatus::Accepted,
                            'bg-zinc-700 text-zinc-300' => $interest->status === ProjectInterestStatus::Declined,
                        ])>{{ $interest->status->label() }}</span>
                    </div>

                    <p class="mt-3 whitespace-pre-line text-zinc-200">{{ $interest->message }}</p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        @if ($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold transition hover:border-yellow-400 hover:text-yellow-400">{{ __('homeowner.show.view_page') }} ↗</a>
                        @endif
                        @if ($interest->status === ProjectInterestStatus::Pending)
                            <button type="button" wire:click="respond({{ $interest->id }}, '{{ ProjectInterestStatus::Accepted->value }}')" class="rounded-full bg-yellow-400 px-4 py-2 text-sm font-extrabold text-zinc-950 transition hover:bg-yellow-300">{{ __('homeowner.show.accept') }}</button>
                            <button type="button" wire:click="respond({{ $interest->id }}, '{{ ProjectInterestStatus::Declined->value }}')" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold text-zinc-400 transition hover:border-red-400 hover:text-red-400">{{ __('homeowner.show.decline') }}</button>
                        @elseif ($interest->status === ProjectInterestStatus::Accepted)
                            <span class="text-sm text-green-400">{{ __('homeowner.show.accepted_hint') }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
