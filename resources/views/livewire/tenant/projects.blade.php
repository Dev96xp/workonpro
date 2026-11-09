<?php

use App\Mail\ProjectInterestReceived;
use App\Models\Category;
use App\Models\Project;
use App\Models\ProjectInterest;
use App\Models\Tenant;
use App\ProjectInterestStatus;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.tenant')] class extends Component {
    use WithPagination;

    #[Url]
    public string $tab = 'available';

    #[Url]
    public string $category = '';

    public ?int $respondingProjectId = null;

    public bool $showInterestModal = false;

    public string $message = '';

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function canRespond(): bool
    {
        return Tenant::hasFeature(tenant('plan'), 'projects');
    }

    public function openInterest(int $projectId): void
    {
        abort_unless($this->canRespond(), 403);

        $this->respondingProjectId = Project::available()->findOrFail($projectId)->id;
        $this->message = '';
        $this->resetErrorBag();
        $this->showInterestModal = true;
    }

    public function sendInterest(): void
    {
        abort_unless($this->canRespond(), 403);

        $this->validate(['message' => ['required', 'string', 'min:10', 'max:1000']]);

        $project = Project::available()->findOrFail($this->respondingProjectId);

        if ($project->interests()->where('tenant_id', tenant('id'))->exists()) {
            $this->addError('message', __('projects.already_sent'));

            return;
        }

        $interest = $project->interests()->create([
            'tenant_id' => tenant('id'),
            'message' => $this->message,
            'status' => ProjectInterestStatus::Pending,
        ]);

        try {
            Mail::to($project->homeowner->email)->send(new ProjectInterestReceived($interest));
        } catch (\Throwable $exception) {
            report($exception);
        }

        $this->showInterestModal = false;
        $this->reset('respondingProjectId', 'message');
        session()->flash('status', __('projects.sent'));
    }

    public function with(): array
    {
        $myInterests = ProjectInterest::query()
            ->where('tenant_id', tenant('id'))
            ->with(['project.homeowner', 'project.subcategory.category'])
            ->latest()
            ->get();

        return [
            'canRespond' => $this->canRespond(),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'projects' => Project::available()
                ->with('subcategory.category')
                ->when($this->category, fn ($query) => $query->whereHas('subcategory.category', fn ($query) => $query->where('slug', $this->category)))
                ->orderByRaw('case when city = ? then 0 else 1 end', [(string) tenant('city')])
                ->latest()
                ->paginate(10),
            'myInterests' => $myInterests,
            'respondedProjectIds' => $myInterests->pluck('project_id'),
            'respondingProject' => $this->respondingProjectId ? Project::find($this->respondingProjectId) : null,
        ];
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="p-6">
            <div class="mb-6">
                <flux:heading size="xl">{{ __('projects.heading') }}</flux:heading>
                <flux:text class="mt-1">{{ __('projects.subheading') }}</flux:text>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">{{ session('status') }}</div>
            @endif

            @unless ($canRespond)
                <div class="mb-6 flex flex-col gap-3 rounded-xl border border-yellow-400/40 bg-yellow-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:bg-yellow-400/10" data-upgrade-banner>
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-yellow-300">{{ __('projects.upgrade_title') }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('projects.upgrade_body') }}</p>
                    </div>
                    <flux:badge color="yellow" icon="sparkles">Pro</flux:badge>
                </div>
            @endunless

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <flux:radio.group wire:model.live="tab" variant="segmented" size="sm">
                    <flux:radio value="available" :label="__('projects.tab_available')" />
                    <flux:radio value="mine" :label="__('projects.tab_mine').' ('.$myInterests->count().')'" />
                </flux:radio.group>

                @if ($tab === 'available')
                    <div class="w-full sm:w-64">
                        <flux:select wire:model.live="category" size="sm">
                            <flux:select.option value="">{{ __('projects.all_categories') }}</flux:select.option>
                            @foreach ($categories as $option)
                                <flux:select.option value="{{ $option->slug }}">{{ $option->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @endif
            </div>

            @if ($tab === 'available')
                <div class="grid gap-4">
                    @forelse ($projects as $project)
                        <div wire:key="project-{{ $project->id }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-lg font-semibold">{{ $project->title }}</p>
                                    <p class="text-sm text-zinc-500">
                                        {{ $project->subcategory?->category?->label() }} › {{ $project->subcategory?->label() }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if ($project->city === tenant('city'))
                                        <flux:badge size="sm" color="green" icon="map-pin">{{ __('projects.near_you') }}</flux:badge>
                                    @endif
                                    <flux:badge size="sm" :color="$project->urgency === \App\ProjectUrgency::Urgent ? 'red' : 'zinc'">{{ $project->urgency->label() }}</flux:badge>
                                </div>
                            </div>

                            <p class="mt-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $project->description }}</p>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-4 text-sm dark:border-zinc-700">
                                <div class="flex flex-wrap gap-x-5 gap-y-1 text-zinc-500">
                                    <span>📍 {{ collect([$project->city, $project->state])->filter()->implode(', ') }}</span>
                                    <span>💵 {{ $project->budget?->label() ?? __('projects.budget_unknown') }}</span>
                                    <span>🕒 {{ $project->created_at->diffForHumans() }}</span>
                                </div>

                                @if ($respondedProjectIds->contains($project->id))
                                    <flux:badge color="zinc" icon="check">{{ __('projects.already_responded') }}</flux:badge>
                                @elseif ($canRespond)
                                    <flux:button wire:click="openInterest({{ $project->id }})" variant="primary" size="sm" icon="hand-raised">{{ __('projects.interested') }}</flux:button>
                                @else
                                    <flux:button size="sm" icon="lock-closed" disabled>{{ __('projects.upgrade_to_respond') }}</flux:button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-zinc-300 py-12 text-center text-zinc-500 dark:border-zinc-700">
                            {{ __('projects.empty') }}
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">{{ $projects->links() }}</div>
            @else
                <div class="grid gap-4">
                    @forelse ($myInterests as $interest)
                        @php($project = $interest->project)
                        <div wire:key="interest-{{ $interest->id }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-lg font-semibold">{{ $project->title }}</p>
                                    <p class="text-sm text-zinc-500">{{ $project->subcategory?->label() }} · {{ collect([$project->city, $project->state])->filter()->implode(', ') }}</p>
                                </div>
                                <flux:badge size="sm" :color="match ($interest->status) { ProjectInterestStatus::Accepted => 'green', ProjectInterestStatus::Declined => 'zinc', default => 'yellow' }">
                                    {{ __('projects.interest_status.'.$interest->status->value) }}
                                </flux:badge>
                            </div>

                            <p class="mt-3 text-sm text-zinc-500">{{ __('projects.your_message') }}</p>
                            <p class="whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $interest->message }}</p>

                            @if ($interest->status === ProjectInterestStatus::Accepted)
                                <div class="mt-4 rounded-lg bg-green-50 p-4 text-sm dark:bg-green-900/20" data-homeowner-contact>
                                    <p class="font-semibold text-green-800 dark:text-green-300">{{ __('projects.contact_heading') }}</p>
                                    <p class="mt-1 font-medium">{{ $project->homeowner->name }}</p>
                                    <p><a href="mailto:{{ $project->homeowner->email }}" class="underline">{{ $project->homeowner->email }}</a></p>
                                    @if ($project->homeowner->phone)
                                        <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $project->homeowner->phone) }}" class="underline">{{ $project->homeowner->phone }}</a></p>
                                    @endif
                                    <p class="text-zinc-500">{{ collect([$project->city, $project->state, $project->zip])->filter()->implode(', ') }}</p>
                                </div>
                            @elseif ($interest->status === ProjectInterestStatus::Pending)
                                <p class="mt-4 text-sm text-zinc-500">{{ __('projects.waiting_hint') }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-zinc-300 py-12 text-center text-zinc-500 dark:border-zinc-700">
                            {{ __('projects.mine_empty') }}
                        </div>
                    @endforelse
                </div>
            @endif

            <flux:modal wire:model="showInterestModal" class="w-full max-w-lg">
                <form wire:submit="sendInterest" class="space-y-5">
                    <div>
                        <flux:heading size="lg">{{ __('projects.modal_title') }}</flux:heading>
                        @if ($respondingProject)
                            <flux:text class="mt-1">{{ $respondingProject->title }}</flux:text>
                        @endif
                    </div>

                    <flux:textarea wire:model="message" rows="5" :label="__('projects.message_label')" :placeholder="__('projects.message_placeholder')" />

                    <flux:text size="sm">{{ __('projects.privacy_hint') }}</flux:text>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('tenant.common.cancel') }}</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">{{ __('projects.send') }}</flux:button>
                    </div>
                </form>
            </flux:modal>
            </div>
        </flux:main>
    </div>
</div>
