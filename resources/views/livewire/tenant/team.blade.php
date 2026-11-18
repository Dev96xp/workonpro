<?php

use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.tenant')] class extends Component {
    use WithFileUploads;

    public bool $available = false;

    public bool $hasEmployeesModule = false;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $role = '';

    public string $email = '';

    public string $phone = '';

    public string $whatsapp = '';

    public $photo = null;

    public ?string $existingPhotoUrl = null;

    public bool $removePhoto = false;

    public bool $card_enabled = true;

    public string $limitMessage = '';

    public function mount(): void
    {
        $this->available = Tenant::hasTeamCards(tenant('plan')) && Tenant::hasFeature(tenant('plan'), 'businesscard');
        $this->hasEmployeesModule = Tenant::hasFeature(tenant('plan'), 'employees');
    }

    public function openCreate(): void
    {
        $this->ensureAvailable();
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->ensureAvailable();
        $this->resetForm();

        $employee = Employee::findOrFail($id);
        $this->editingId = $employee->id;
        $this->name = $employee->name;
        $this->role = $employee->role ?? '';
        $this->email = $employee->email;
        $this->phone = $employee->phone ?? '';
        $this->whatsapp = $employee->whatsapp ?? '';
        $this->existingPhotoUrl = $employee->photoUrl();
        $this->card_enabled = $employee->card_enabled;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->ensureAvailable();

        $this->validate([
            'name' => 'required|string|max:100',
            'role' => 'nullable|string|max:100',
            'email' => ['required', 'email', 'max:100', Rule::unique('employees', 'email')->ignore($this->editingId)],
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'photo' => 'nullable|image|max:10240',
        ]);

        if ($this->card_enabled && ! $this->cardFitsInPlan($this->editingId)) {
            $this->addError('card_enabled', __('team.limit_reached'));

            return;
        }

        $data = [
            'name' => $this->name,
            'role' => $this->role !== '' ? $this->role : null,
            'email' => $this->email,
            'phone' => $this->phone !== '' ? $this->phone : null,
            'whatsapp' => $this->whatsapp !== '' ? $this->whatsapp : null,
            'card_enabled' => $this->card_enabled,
        ];

        $employee = $this->editingId
            ? tap(Employee::findOrFail($this->editingId))->update($data)
            : Employee::create([...$data, 'password' => Str::password(24)]);

        if ($this->photo || $this->removePhoto) {
            $employee->replacePhoto($this->photo);
        }

        if ($employee->card_enabled) {
            $employee->ensureCardSlug();
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleCard(int $id): void
    {
        $this->ensureAvailable();
        $this->limitMessage = '';

        $employee = Employee::findOrFail($id);

        if (! $employee->card_enabled && ! $this->cardFitsInPlan($employee->id)) {
            $this->limitMessage = __('team.limit_reached');

            return;
        }

        $employee->update(['card_enabled' => ! $employee->card_enabled]);

        if ($employee->card_enabled) {
            $employee->ensureCardSlug();
        }
    }

    public function delete(int $id): void
    {
        $this->ensureAvailable();
        abort_if($this->hasEmployeesModule, 403);

        $employee = Employee::findOrFail($id);
        $employee->replacePhoto(null);
        $employee->delete();
    }

    public function clearPhoto(): void
    {
        $this->photo = null;
        $this->existingPhotoUrl = null;
        $this->removePhoto = true;
    }

    /**
     * @return array{people: \Illuminate\Database\Eloquent\Collection<int, Employee>, used: int, limit: ?int}
     */
    public function with(): array
    {
        if (! $this->available) {
            return ['people' => collect(), 'used' => 0, 'limit' => 0];
        }

        $limit = Tenant::teamCardLimit(tenant('plan'));
        $used = Employee::query()->cardCandidates()->count();

        return [
            'people' => Employee::query()->orderBy('name')->get(),
            'used' => $limit === null ? $used : min($used, $limit),
            'limit' => $limit,
        ];
    }

    /**
     * A person who already has a working card keeps it; anyone else needs a free slot.
     */
    private function cardFitsInPlan(?int $employeeId): bool
    {
        $alreadyHasCard = $employeeId
            && Employee::query()->cardCandidates()->whereKey($employeeId)->exists();

        return $alreadyHasCard || Employee::cardSlotsLeft() !== 0;
    }

    private function ensureAvailable(): void
    {
        abort_unless($this->available, 403);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->role = '';
        $this->email = '';
        $this->phone = '';
        $this->whatsapp = '';
        $this->photo = null;
        $this->existingPhotoUrl = null;
        $this->removePhoto = false;
        $this->card_enabled = true;
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading size="xl">{{ __('team.heading') }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ __('team.subheading') }}</flux:text>
                    </div>
                    @if ($available)
                        <flux:button wire:click="openCreate" variant="primary" icon="plus">{{ __('team.new') }}</flux:button>
                    @endif
                </div>

                @unless ($available)
                    <div data-team-locked class="mt-6 flex flex-col gap-4 rounded-2xl border border-yellow-400/40 bg-yellow-50 p-6 sm:flex-row sm:items-center dark:bg-yellow-400/10">
                        <flux:icon.identification class="size-10 shrink-0 text-yellow-500" />
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-zinc-900 dark:text-yellow-300">{{ __('team.locked_title') }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('team.locked_body') }}</p>
                        </div>
                        <flux:button href="{{ url('/plan') }}" wire:navigate variant="primary" icon="sparkles">{{ __('team.upgrade') }}</flux:button>
                    </div>
                @else
                    <div class="mt-6 flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 sm:flex-row sm:items-center dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-zinc-900 dark:text-white" data-team-usage>
                                {{ $limit === null ? __('team.usage_unlimited', ['used' => $used]) : __('team.usage', ['used' => $used, 'limit' => $limit]) }}
                            </p>
                            @if ($limit !== null)
                                <div class="mt-2 h-2 max-w-sm overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <div class="h-full rounded-full bg-yellow-400" style="width: {{ $limit > 0 ? round($used / $limit * 100) : 100 }}%"></div>
                                </div>
                            @endif
                        </div>
                        @if ($limit !== null && $used >= $limit)
                            <flux:button href="{{ url('/plan') }}" wire:navigate size="sm" icon="sparkles">{{ __('team.upgrade') }}</flux:button>
                        @endif
                    </div>

                    @if ($hasEmployeesModule)
                        <flux:text class="mt-3 text-sm">{{ __('team.employees_note') }}</flux:text>
                    @endif

                    @if ($limitMessage)
                        <div class="mt-4 rounded-lg bg-yellow-50 px-4 py-3 text-sm font-semibold text-yellow-800 dark:bg-yellow-400/10 dark:text-yellow-300" data-team-limit-message>
                            {{ $limitMessage }} <a href="{{ url('/plan') }}" wire:navigate class="underline">{{ __('team.upgrade') }}</a>
                        </div>
                    @endif

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @forelse ($people as $person)
                            @php($isPublic = $person->hasPublicCard())
                            <div wire:key="person-{{ $person->id }}" data-team-person="{{ $person->id }}" class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                                <div class="flex items-center gap-3">
                                    @if ($person->photoUrl())
                                        <img src="{{ $person->photoUrl() }}" alt="{{ $person->name }}" class="size-14 shrink-0 rounded-full object-cover" />
                                    @else
                                        <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-lg font-bold text-yellow-400">{{ $person->initials() }}</span>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-bold text-zinc-900 dark:text-white">{{ $person->name }}</p>
                                        <p class="truncate text-sm text-zinc-500">{{ $person->role ?? '—' }}</p>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    @if ($isPublic)
                                        <flux:badge size="sm" color="green" icon="check">{{ __('team.card_on') }}</flux:badge>
                                    @elseif ($person->card_enabled && ! $person->isActive())
                                        <flux:badge size="sm" color="zinc">{{ __('team.card_inactive') }}</flux:badge>
                                    @elseif ($person->card_enabled)
                                        <flux:badge size="sm" color="amber">{{ __('team.card_over_limit') }}</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="zinc">{{ __('team.card_off') }}</flux:badge>
                                    @endif
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-700">
                                    @if ($isPublic)
                                        <div
                                            class="flex items-center gap-1"
                                            x-data="{
                                                copied: false,
                                                copy() {
                                                    navigator.clipboard.writeText(@js($person->cardUrl()));
                                                    this.copied = true;
                                                    setTimeout(() => this.copied = false, 1500);
                                                },
                                            }"
                                        >
                                            <flux:button href="{{ $person->cardUrl() }}" target="_blank" size="sm" icon="identification">{{ __('team.open') }}</flux:button>
                                            <flux:button x-on:click="copy()" size="sm" square aria-label="{{ __('team.copy') }}">
                                                <flux:icon.clipboard-document-check variant="mini" x-show="copied" x-cloak />
                                                <flux:icon.clipboard-document variant="mini" x-show="!copied" />
                                            </flux:button>
                                        </div>
                                    @endif

                                    <flux:spacer />

                                    <flux:button wire:click="toggleCard({{ $person->id }})" size="sm" variant="{{ $person->card_enabled ? 'ghost' : 'primary' }}">
                                        {{ $person->card_enabled ? __('team.turn_off') : __('team.turn_on') }}
                                    </flux:button>
                                    <flux:button wire:click="openEdit({{ $person->id }})" size="sm" icon="pencil" />
                                    @unless ($hasEmployeesModule)
                                        <flux:button wire:click="delete({{ $person->id }})" wire:confirm="{{ __('team.confirm_delete') }}" size="sm" icon="trash" variant="danger" />
                                    @endunless
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500 sm:col-span-2 xl:col-span-3 dark:border-zinc-700">
                                {{ __('team.empty') }}
                            </div>
                        @endforelse
                    </div>
                @endunless
            </div>
        </flux:main>
    </div>

    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <flux:heading size="lg">{{ $editingId ? __('team.edit') : __('team.new') }}</flux:heading>

        <form wire:submit="save" class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.common.name') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.employees.role_label') }}</flux:label>
                    <flux:input wire:model="role" />
                    <flux:error name="role" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.common.email') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="email" type="email" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('tenant.employees.phone_label') }}</flux:label>
                    <flux:input wire:model="phone" type="tel" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('tenant.employees.whatsapp_label') }}</flux:label>
                    <flux:input wire:model="whatsapp" type="tel" />
                    <flux:error name="whatsapp" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.employees.photo_label') }}</flux:label>
                    <div class="flex items-center gap-4">
                        @if ($photo && method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                            <img src="{{ $photo->temporaryUrl() }}" class="size-16 shrink-0 rounded-full object-cover" alt="" />
                        @elseif ($existingPhotoUrl)
                            <img src="{{ $existingPhotoUrl }}" class="size-16 shrink-0 rounded-full object-cover" alt="" />
                        @else
                            <span class="flex size-16 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-lg font-bold text-yellow-400">{{ $name ? (new Employee(['name' => $name]))->initials() : '?' }}</span>
                        @endif
                        <div class="min-w-0 flex-1 space-y-2">
                            <input type="file" wire:model="photo" accept="image/*" class="block w-full text-sm text-zinc-500 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-700 dark:file:text-zinc-200" />
                            @if ($photo || $existingPhotoUrl)
                                <flux:button type="button" wire:click="clearPhoto" size="xs" variant="ghost" icon="x-mark">{{ __('tenant.employees.remove_photo') }}</flux:button>
                            @endif
                        </div>
                    </div>
                    <flux:description>{{ __('tenant.employees.photo_hint') }}</flux:description>
                    <flux:error name="photo" />
                </flux:field>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:switch wire:model="card_enabled" label="{{ __('tenant.employees.card_switch') }}" />
                <flux:error name="card_enabled" />
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">{{ __('tenant.common.save_changes') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
