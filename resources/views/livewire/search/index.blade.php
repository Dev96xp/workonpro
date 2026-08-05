<?php

use App\Enums\ServiceCategory;
use App\Models\ServiceListing;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.guest')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $category = '';

    public string $city = '';

    public function with(): array
    {
        return [
            'listings' => ServiceListing::query()
                ->where('is_active', true)
                ->with('tenant.domains')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%"))
                ->when($this->category, fn ($q) => $q->where('category', $this->category))
                ->when($this->city, fn ($q) => $q->where('city', $this->city))
                ->orderBy('name')
                ->paginate(12),
            'centralDomain' => parse_url(config('app.url'), PHP_URL_HOST),
            'cities' => ServiceListing::query()
                ->whereNotNull('city')
                ->distinct()
                ->orderBy('city')
                ->pluck('city'),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedCity(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="min-h-screen bg-zinc-900">
    <div class="mx-auto max-w-5xl px-6 py-24 sm:px-8">
        {{-- Header --}}
        <div class="mb-14 text-center">
            <flux:heading size="xl" class="font-black tracking-tight text-white">Buscar servicios</flux:heading>
            <flux:text class="mx-auto mt-3 max-w-md text-zinc-500">Encuentra el negocio que necesitas entre todos los que ya confían en Workon</flux:text>
        </div>

        {{-- Filters --}}
        <div class="mb-16 flex flex-col gap-3 sm:flex-row sm:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="¿Qué servicio buscas?"
                icon="magnifying-glass"
                class="sm:flex-1"
            />

            <div class="flex gap-3">
                <flux:select wire:model.live="category" class="sm:w-48">
                    <flux:select.option value="">Categoría</flux:select.option>
                    @foreach (ServiceCategory::cases() as $option)
                        <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="city" class="sm:w-44">
                    <flux:select.option value="">Ciudad</flux:select.option>
                    @foreach ($cities as $option)
                        <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        {{-- Results --}}
        <div class="grid gap-6 sm:grid-cols-2">
            @forelse ($listings as $listing)
                @php
                    $domain = $listing->tenant?->domains->first()?->domain;
                    $url = $domain ? request()->getScheme() . '://' . $domain . '.' . $centralDomain : null;
                @endphp
                <article class="group rounded-2xl bg-white/[0.03] p-6 transition hover:bg-white/[0.05]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            @if ($listing->category)
                                <span class="text-xs font-medium tracking-wide text-yellow-400/90 uppercase">{{ $listing->category->label() }}</span>
                            @endif
                            <flux:heading size="lg" class="mt-1 text-white">{{ $listing->name }}</flux:heading>
                        </div>
                        @if ($listing->price !== null)
                            <span class="whitespace-nowrap text-sm font-semibold text-white">${{ number_format($listing->price, 2) }}</span>
                        @endif
                    </div>

                    @if ($listing->description)
                        <flux:text class="mt-2 leading-relaxed text-zinc-500">{{ Str::limit($listing->description, 110) }}</flux:text>
                    @endif

                    <div class="mt-4 flex items-center justify-between border-t border-white/10 pt-4">
                        <div class="text-sm text-zinc-500">
                            <span class="text-zinc-300">{{ $listing->tenant?->name }}</span>
                            @if ($listing->city)
                                <span> · {{ $listing->city }}</span>
                            @endif
                        </div>
                        @if ($url)
                            <a href="{{ $url }}" target="_blank" class="text-sm font-medium text-yellow-400 transition group-hover:translate-x-0.5 hover:text-yellow-300">
                                Ver negocio →
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="col-span-full py-20 text-center text-zinc-600">
                    No se encontraron servicios con esos filtros.
                </div>
            @endforelse
        </div>

        @if ($listings->hasPages())
            <div class="mt-14">
                {{ $listings->links() }}
            </div>
        @endif
    </div>
</div>
