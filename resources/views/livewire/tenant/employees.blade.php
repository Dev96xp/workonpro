<?php

use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('components.layouts.tenant')] class extends Component {
    use WithFileUploads, WithPagination;

    public string $search = '';

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|email|max:100')]
    public string $email = '';

    public string $password = '';

    public string $role = '';

    public string $salary = '';

    public string $salary_period = Employee::PERIOD_MONTHLY;

    public string $status = Employee::STATUS_ACTIVE;

    public string $phone = '';

    public string $whatsapp = '';

    public $photo = null;

    public ?string $existingPhotoUrl = null;

    public bool $removePhoto = false;

    public bool $card_enabled = false;

    public bool $hasCards = false;

    public function mount(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'employees'), 403);

        $this->hasCards = Tenant::hasTeamCards(tenant('plan')) && Tenant::hasFeature(tenant('plan'), 'businesscard');
    }

    public function with(): array
    {
        return [
            'employees' => Employee::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $this->editingId = $id;
        $this->name = $employee->name;
        $this->email = $employee->email;
        $this->password = '';
        $this->role = $employee->role ?? '';
        $this->salary = $employee->salary !== null ? (string) $employee->salary : '';
        $this->salary_period = $employee->salary_period ?? Employee::PERIOD_MONTHLY;
        $this->status = $employee->status;
        $this->phone = $employee->phone ?? '';
        $this->whatsapp = $employee->whatsapp ?? '';
        $this->existingPhotoUrl = $employee->photoUrl();
        $this->card_enabled = $employee->card_enabled;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:100', Rule::unique('employees', 'email')->ignore($this->editingId)],
            'password' => $this->editingId ? 'nullable|min:8' : 'required|min:8',
            'role' => 'nullable|string|max:100',
            'salary' => 'nullable|numeric|min:0',
            'salary_period' => 'nullable|in:'.implode(',', Employee::SALARY_PERIODS),
            'status' => 'required|in:'.implode(',', Employee::STATUSES),
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'photo' => 'nullable|image|max:10240',
        ]);

        if ($this->hasCards && $this->card_enabled && ! $this->cardFitsInPlan()) {
            $this->addError('card_enabled', __('team.limit_reached'));

            return;
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role !== '' ? $this->role : null,
            'salary' => $this->salary !== '' ? $this->salary : null,
            'salary_period' => $this->salary !== '' ? $this->salary_period : null,
            'status' => $this->status,
            'phone' => $this->phone !== '' ? $this->phone : null,
            'whatsapp' => $this->whatsapp !== '' ? $this->whatsapp : null,
            'card_enabled' => $this->hasCards && $this->card_enabled,
        ];

        if ($this->password !== '') {
            $data['password'] = $this->password;
        }

        if ($this->editingId) {
            $employee = Employee::findOrFail($this->editingId);
            $employee->update($data);
        } else {
            $employee = Employee::create($data);
        }

        if ($this->photo || $this->removePhoto) {
            $employee->replacePhoto($this->photo);
        }

        if ($employee->card_enabled) {
            $employee->ensureCardSlug();
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $employee = Employee::findOrFail($this->deletingId);
            $employee->delete();
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/'));
    }

    public function clearPhoto(): void
    {
        $this->photo = null;
        $this->existingPhotoUrl = null;
        $this->removePhoto = true;
    }

    /**
     * Turning a card on needs a free slot, unless this person already had one.
     */
    private function cardFitsInPlan(): bool
    {
        $alreadyHasCard = $this->editingId
            && Employee::query()->cardCandidates()->whereKey($this->editingId)->exists();

        return $alreadyHasCard || Employee::cardSlotsLeft() !== 0;
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = '';
        $this->salary = '';
        $this->salary_period = Employee::PERIOD_MONTHLY;
        $this->status = Employee::STATUS_ACTIVE;
        $this->phone = '';
        $this->whatsapp = '';
        $this->photo = null;
        $this->existingPhotoUrl = null;
        $this->removePhoto = false;
        $this->card_enabled = false;
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
                        <flux:heading size="xl">{{ __('tenant.employees.heading') }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ __('tenant.employees.subheading') }}</flux:text>
                    </div>
                    <flux:button wire:click="openCreate" variant="primary" icon="plus" class="sm:w-auto">
                        {{ __('tenant.employees.new') }}
                    </flux:button>
                </div>

                <div class="mt-6">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('tenant.employees.search_placeholder') }}" icon="magnifying-glass" />
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.employees.code_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.email') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.employees.role_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.employees.salary_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.status') }}</th>
                                @if ($hasCards)
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.employees.card_column') }}</th>
                                @endif
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($employees as $employee)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4 font-medium">
                                        <div class="flex items-center gap-3">
                                            @if ($employee->photoUrl())
                                                <img src="{{ $employee->photoUrl() }}" alt="{{ $employee->name }}" class="size-8 shrink-0 rounded-full object-cover" />
                                            @else
                                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-xs font-bold text-yellow-400">{{ $employee->initials() }}</span>
                                            @endif
                                            {{ $employee->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div
                                            class="flex items-center gap-1"
                                            x-data="{
                                                copied: false,
                                                copy() {
                                                    navigator.clipboard.writeText(@js($employee->employee_code));
                                                    this.copied = true;
                                                    setTimeout(() => this.copied = false, 1500);
                                                },
                                            }"
                                        >
                                            <span class="font-mono text-xs text-zinc-500">{{ $employee->employee_code ?? '—' }}</span>
                                            @if ($employee->employee_code)
                                                <flux:button x-on:click="copy()" variant="subtle" size="xs" square aria-label="{{ __('tenant.employees.copy_code') }}">
                                                    <flux:icon.clipboard-document-check variant="mini" x-show="copied" x-cloak />
                                                    <flux:icon.clipboard-document variant="mini" x-show="!copied" />
                                                </flux:button>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $employee->email }}</td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $employee->role ?? '—' }}</td>
                                    <td class="px-6 py-4 text-zinc-500">
                                        @if ($employee->salary !== null)
                                            ${{ number_format((float) $employee->salary, 2) }}
                                            @if ($employee->salary_period)
                                                <span class="text-xs">/ {{ __('tenant.employees.period_'.$employee->salary_period) }}</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $statusColors = [
                                                'active' => 'green',
                                                'inactive' => 'zinc',
                                                'vacation' => 'blue',
                                                'suspended' => 'amber',
                                            ];
                                        @endphp
                                        <flux:badge color="{{ $statusColors[$employee->status] ?? 'zinc' }}" size="sm">
                                            {{ __('tenant.employees.status_'.$employee->status) }}
                                        </flux:badge>
                                    </td>
                                    @if ($hasCards)
                                        <td class="px-6 py-4" data-employee-card="{{ $employee->id }}">
                                            @if ($employee->hasPublicCard())
                                                <div
                                                    class="flex items-center gap-1"
                                                    x-data="{
                                                        copied: false,
                                                        copy() {
                                                            navigator.clipboard.writeText(@js($employee->cardUrl()));
                                                            this.copied = true;
                                                            setTimeout(() => this.copied = false, 1500);
                                                        },
                                                    }"
                                                >
                                                    <flux:button href="{{ $employee->cardUrl() }}" target="_blank" size="xs" icon="identification">{{ __('tenant.employees.card_open') }}</flux:button>
                                                    <flux:button x-on:click="copy()" variant="subtle" size="xs" square aria-label="{{ __('tenant.employees.card_copy') }}">
                                                        <flux:icon.clipboard-document-check variant="mini" x-show="copied" x-cloak />
                                                        <flux:icon.clipboard-document variant="mini" x-show="!copied" />
                                                    </flux:button>
                                                </div>
                                            @else
                                                <span class="text-xs text-zinc-400">{{ $employee->card_enabled ? __('tenant.employees.card_needs_active') : __('tenant.employees.card_off') }}</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <flux:button wire:click="openEdit({{ $employee->id }})" size="sm" icon="pencil" />
                                            <flux:button wire:click="confirmDelete({{ $employee->id }})" size="sm" icon="trash" variant="danger" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $hasCards ? 8 : 7 }}" class="px-6 py-12 text-center text-zinc-500">
                                        {{ __('tenant.employees.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($employees->hasPages())
                        <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                            {{ $employees->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </flux:main>
    </div>

    {{-- Modal Crear/Editar --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <flux:heading size="lg">{{ $editingId ? __('tenant.employees.edit') : __('tenant.employees.new') }}</flux:heading>

        <form wire:submit="save" class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.common.name') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="name" placeholder="Nombre completo" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.common.email') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="email" type="email" placeholder="empleado@ejemplo.com" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>
                        {{ __('tenant.employees.password_label') }}
                        @unless ($editingId)
                            <span class="text-red-500">*</span>
                        @endunless
                    </flux:label>
                    <flux:input wire:model="password" type="password" placeholder="{{ $editingId ? __('tenant.employees.password_placeholder_edit') : '' }}" />
                    <flux:error name="password" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.employees.role_label') }}</flux:label>
                    <flux:input wire:model="role" />
                    <flux:error name="role" />
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
                            <div wire:loading wire:target="photo" class="text-xs text-zinc-500">{{ __('tenant.common.saving') }}</div>
                            @if ($photo || $existingPhotoUrl)
                                <flux:button type="button" wire:click="clearPhoto" size="xs" variant="ghost" icon="x-mark">{{ __('tenant.employees.remove_photo') }}</flux:button>
                            @endif
                        </div>
                    </div>
                    <flux:description>{{ __('tenant.employees.photo_hint') }}</flux:description>
                    <flux:error name="photo" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('tenant.employees.salary_label') }}</flux:label>
                    <flux:input wire:model="salary" type="number" step="0.01" min="0" />
                    <flux:error name="salary" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('tenant.employees.salary_period_label') }}</flux:label>
                    <flux:select wire:model="salary_period">
                        @foreach (Employee::SALARY_PERIODS as $period)
                            <flux:select.option value="{{ $period }}">{{ __('tenant.employees.period_'.$period) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="salary_period" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.employees.status_label') }}</flux:label>
                    <flux:select wire:model="status">
                        @foreach (Employee::STATUSES as $statusOption)
                            <flux:select.option value="{{ $statusOption }}">{{ __('tenant.employees.status_'.$statusOption) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>
            </div>

            @if ($hasCards)
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:switch wire:model="card_enabled" label="{{ __('tenant.employees.card_switch') }}" />
                    <flux:text class="mt-1 text-xs">{{ __('tenant.employees.card_hint') }}</flux:text>
                    <flux:error name="card_enabled" />
                </div>
            @endif

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? __('tenant.common.save_changes') : __('tenant.employees.new') }}</span>
                    <span wire:loading>{{ __('tenant.common.saving') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal Confirmar Eliminar --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="text-center">
            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                <flux:icon.trash class="size-6 text-red-600 dark:text-red-400" />
            </div>
            <flux:heading size="lg">{{ __('tenant.employees.confirm_delete') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">{{ __('tenant.common.cannot_undo') }}</flux:text>
        </div>

        <div class="mt-6 flex justify-center gap-3">
            <flux:button wire:click="$set('showDeleteModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
            <flux:button wire:click="delete" variant="danger">{{ __('tenant.common.delete') }}</flux:button>
        </div>
    </flux:modal>
</div>
