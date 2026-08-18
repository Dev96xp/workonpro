<?php

use App\Models\Location;
use App\Models\Tenant;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.tenant')] class extends Component {
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    public string $latitude = '';

    public string $longitude = '';

    public string $radius_feet = '100';

    public function mount(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'employees'), 403);
    }

    public function with(): array
    {
        return [
            'locations' => Location::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
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
        $location = Location::findOrFail($id);
        $this->editingId = $id;
        $this->name = $location->name;
        $this->latitude = $location->latitude !== null ? (string) $location->latitude : '';
        $this->longitude = $location->longitude !== null ? (string) $location->longitude : '';
        $this->radius_feet = (string) $location->radius_feet;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('locations', 'name')->ignore($this->editingId)],
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_feet' => 'required|integer|min:10|max:5000',
        ]);

        $data = [
            'name' => $this->name,
            'latitude' => $this->latitude !== '' ? $this->latitude : null,
            'longitude' => $this->longitude !== '' ? $this->longitude : null,
            'radius_feet' => $this->radius_feet,
        ];

        if ($this->editingId) {
            Location::findOrFail($this->editingId)->update($data);
        } else {
            Location::create($data);
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
            Location::findOrFail($this->deletingId)->delete();
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

    private function resetForm(): void
    {
        $this->name = '';
        $this->latitude = '';
        $this->longitude = '';
        $this->radius_feet = '100';
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading size="xl">{{ __('tenant.locations.heading') }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ __('tenant.locations.subheading') }}</flux:text>
                    </div>
                    <flux:button wire:click="openCreate" variant="primary" icon="plus" class="sm:w-auto">
                        {{ __('tenant.locations.new') }}
                    </flux:button>
                </div>

                <div class="mt-6">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('tenant.locations.search_placeholder') }}" icon="magnifying-glass" />
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.locations.coordinates_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.locations.radius_label') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($locations as $location)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4 font-medium">{{ $location->name }}</td>
                                    <td class="px-6 py-4 text-zinc-500">
                                        @if ($location->hasCoordinates())
                                            <span class="font-mono text-xs">{{ $location->latitude }}, {{ $location->longitude }}</span>
                                        @else
                                            <flux:badge color="amber" size="sm">{{ __('tenant.locations.no_coordinates') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-zinc-500">{{ $location->radius_feet }} ft</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <flux:button wire:click="openEdit({{ $location->id }})" size="sm" icon="pencil" />
                                            <flux:button wire:click="confirmDelete({{ $location->id }})" size="sm" icon="trash" variant="danger" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-zinc-500">
                                        {{ __('tenant.locations.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($locations->hasPages())
                        <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                            {{ $locations->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </flux:main>
    </div>

    {{-- Modal Crear/Editar --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <flux:heading size="lg">{{ $editingId ? __('tenant.locations.edit') : __('tenant.locations.new') }}</flux:heading>

        <form wire:submit="save" class="mt-4 space-y-4">
            <flux:field>
                <flux:label>{{ __('tenant.common.name') }} <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="name" placeholder="Almacén 1" />
                <flux:error name="name" />
            </flux:field>

            <div
                x-data="{
                    capturing: false,
                    error: '',
                    capture() {
                        this.capturing = true;
                        this.error = '';
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                this.capturing = false;
                                $wire.set('latitude', position.coords.latitude.toFixed(7));
                                $wire.set('longitude', position.coords.longitude.toFixed(7));
                            },
                            () => {
                                this.capturing = false;
                                this.error = @js(__('tenant.locations.location_capture_error'));
                            },
                            { enableHighAccuracy: true, timeout: 10000 }
                        );
                    },
                }"
                class="rounded-lg border border-dashed border-zinc-300 p-4 dark:border-zinc-600"
            >
                <flux:button type="button" x-on:click="capture()" x-bind:disabled="capturing" size="sm" icon="map-pin">
                    <span x-show="!capturing">{{ __('tenant.locations.use_current_location') }}</span>
                    <span x-show="capturing" x-cloak>{{ __('tenant.locations.locating') }}</span>
                </flux:button>
                <p class="mt-2 text-xs text-zinc-500">{{ __('tenant.locations.use_current_location_hint') }}</p>
                <p class="mt-2 text-xs text-red-500" x-show="error" x-text="error" x-cloak></p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('tenant.locations.latitude_label') }}</flux:label>
                    <flux:input wire:model="latitude" placeholder="19.4326077" />
                    <flux:error name="latitude" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('tenant.locations.longitude_label') }}</flux:label>
                    <flux:input wire:model="longitude" placeholder="-99.1332080" />
                    <flux:error name="longitude" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('tenant.locations.radius_label') }}</flux:label>
                <flux:input wire:model="radius_feet" type="number" min="10" max="5000" />
                <flux:error name="radius_feet" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? __('tenant.common.save_changes') : __('tenant.locations.new') }}</span>
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
            <flux:heading size="lg">{{ __('tenant.locations.confirm_delete') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">{{ __('tenant.common.cannot_undo') }}</flux:text>
        </div>

        <div class="mt-6 flex justify-center gap-3">
            <flux:button wire:click="$set('showDeleteModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
            <flux:button wire:click="delete" variant="danger">{{ __('tenant.common.delete') }}</flux:button>
        </div>
    </flux:modal>
</div>
