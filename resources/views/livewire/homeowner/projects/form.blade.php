<?php

use App\Models\Category;
use App\Models\Project;
use App\Models\Subcategory;
use App\ProjectBudget;
use App\ProjectUrgency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.homeowner')] #[Title('Workon')] class extends Component {
    public ?Project $project = null;

    public string $title = '';

    public string $description = '';

    public string $subcategory_id = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public string $urgency = '';

    public string $budget = '';

    public function mount(?Project $project = null): void
    {
        $homeowner = auth('homeowner')->user();

        if ($project?->exists) {
            abort_unless($project->homeowner_id === $homeowner->id, 404);

            $this->project = $project;
            $this->title = $project->title;
            $this->description = $project->description;
            $this->subcategory_id = (string) $project->subcategory_id;
            $this->city = $project->city;
            $this->state = $project->state;
            $this->zip = (string) $project->zip;
            $this->urgency = $project->urgency->value;
            $this->budget = (string) $project->budget?->value;

            return;
        }

        $this->city = $homeowner->city;
        $this->state = $homeowner->state;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'subcategory_id' => ['required', Rule::exists('subcategories', 'id')->where('is_active', true)],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:20'],
            'urgency' => ['required', Rule::enum(ProjectUrgency::class)],
            'budget' => ['nullable', Rule::enum(ProjectBudget::class)],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $attributes = [
            ...$validated,
            'subcategory_id' => (int) $validated['subcategory_id'],
            'zip' => $validated['zip'] ?: null,
            'budget' => $validated['budget'] ?: null,
        ];

        $project = $this->project
            ? tap($this->project)->update($attributes)
            : auth('homeowner')->user()->projects()->create($attributes);

        session()->flash('status', __($this->project ? 'homeowner.form.updated' : 'homeowner.form.created'));

        $this->redirectRoute('homeowner.projects.show', $project);
    }

    /**
     * @return array{categoryOptions: Collection<int, Category>, urgencies: array<int, ProjectUrgency>, budgets: array<int, ProjectBudget>}
     */
    public function with(): array
    {
        return [
            'categoryOptions' => Category::query()
                ->where('is_active', true)
                ->with(['subcategories' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('sort_order')
                ->get()
                ->filter(fn (Category $category) => $category->subcategories->isNotEmpty()),
            'urgencies' => ProjectUrgency::cases(),
            'budgets' => ProjectBudget::cases(),
        ];
    }
}; ?>

<div class="mx-auto max-w-2xl">
    <a href="{{ $project ? route('homeowner.projects.show', $project) : route('homeowner.dashboard') }}" class="text-sm text-zinc-400 hover:text-yellow-400">← {{ __('homeowner.form.back') }}</a>

    <h1 class="mt-4 text-3xl font-black tracking-tight">{{ $project ? __('homeowner.form.edit_heading') : __('homeowner.form.create_heading') }}</h1>
    @unless ($project)
        <p class="mt-2 text-zinc-400">{{ __('homeowner.form.create_subheading') }}</p>
    @endunless

    <form wire:submit="save" class="mt-8 space-y-5">
        <flux:input wire:model="title" :label="__('homeowner.fields.title')" :placeholder="__('homeowner.fields.title_placeholder')" autofocus />

        <flux:field>
            <flux:label>{{ __('homeowner.fields.subcategory') }}</flux:label>
            <flux:select wire:model="subcategory_id" :placeholder="__('homeowner.fields.subcategory_placeholder')">
                @foreach ($categoryOptions as $option)
                    <optgroup label="{{ $option->label() }}">
                        @foreach ($option->subcategories as $suboption)
                            <flux:select.option value="{{ $suboption->id }}">{{ $suboption->label() }}</flux:select.option>
                        @endforeach
                    </optgroup>
                @endforeach
            </flux:select>
            <flux:error name="subcategory_id" />
        </flux:field>

        <flux:textarea wire:model="description" rows="5" :label="__('homeowner.fields.description')" :placeholder="__('homeowner.fields.description_placeholder')" />

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="city" :label="__('homeowner.fields.city')" />
            <flux:input wire:model="state" :label="__('homeowner.fields.state')" />
            <flux:input wire:model="zip" :label="__('homeowner.fields.zip')" />
        </div>

        <flux:field>
            <flux:label>{{ __('homeowner.fields.urgency') }}</flux:label>
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach ($urgencies as $option)
                    <label wire:key="urgency-{{ $option->value }}" class="flex cursor-pointer items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold transition has-[:checked]:border-yellow-400 has-[:checked]:text-yellow-400">
                        <input type="radio" wire:model="urgency" value="{{ $option->value }}" class="accent-yellow-400">
                        {{ $option->label() }}
                    </label>
                @endforeach
            </div>
            <flux:error name="urgency" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('homeowner.fields.budget') }} <span class="text-zinc-500">— {{ __('homeowner.fields.optional') }}</span></flux:label>
            <flux:select wire:model="budget" :placeholder="__('homeowner.fields.budget_placeholder')">
                @foreach ($budgets as $option)
                    <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="budget" />
        </flux:field>

        <button type="submit" class="w-full rounded-full bg-yellow-400 py-4 font-extrabold text-zinc-950 transition hover:bg-yellow-300">
            {{ $project ? __('homeowner.form.save') : __('homeowner.form.publish') }}
        </button>
    </form>
</div>
