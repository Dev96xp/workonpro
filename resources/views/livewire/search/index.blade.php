<?php

use App\Models\Category;
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
            'categoryOptions' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'categoryLabels' => Category::query()->pluck('name', 'slug'),
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
    {{-- Navbar --}}
    <header class="border-b border-white/10">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5 sm:px-8">
            <a href="{{ route('home') }}" class="text-2xl font-black tracking-tighter text-yellow-400 sm:text-3xl">Workon</a>
            <a href="{{ route('home') }}" class="text-sm font-medium text-zinc-400 transition hover:text-yellow-400">
                ← Volver al inicio
            </a>
        </div>
    </header>

    {{-- Decorative accent between navbar and heading --}}
    <div class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -right-24 -top-10 h-44 w-64 rotate-12 rounded-[40%_60%_70%_30%/40%_50%_60%_50%] bg-yellow-400/10 blur-3xl sm:-right-32 sm:-top-16 sm:h-96 sm:w-96 lg:-right-40 lg:h-[28rem] lg:w-[28rem]"></div>
            <div class="absolute -left-16 top-0 h-28 w-56 -rotate-6 rounded-[60%_40%_30%_70%/60%_30%_70%_40%] bg-yellow-400/5 blur-2xl sm:-left-20 sm:h-56 sm:w-72 lg:-left-28 lg:h-72 lg:w-96"></div>
        </div>

        <div class="relative mx-auto max-w-5xl px-6 pt-12 sm:px-8 sm:pt-20 lg:pt-20">
            {{-- Header --}}
            <div class="mb-10 text-center sm:mb-14">
                <div class="mb-4 inline-flex items-center gap-3">
                    <div class="h-px w-8 bg-yellow-400"></div>
                    <span class="text-xs font-bold uppercase tracking-[0.2em] text-yellow-400">Directorio</span>
                    <div class="h-px w-8 bg-yellow-400"></div>
                </div>
                <flux:heading size="xl" class="font-black tracking-tight text-white sm:text-4xl lg:text-5xl">Buscar servicios</flux:heading>
                <flux:text class="mx-auto mt-3 max-w-md text-zinc-500 sm:text-lg">Encuentra el negocio que necesitas entre todos los que ya confían en Workon</flux:text>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-5xl px-6 pb-16 sm:px-8">

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
                    @foreach ($categoryOptions as $option)
                        <flux:select.option value="{{ $option->slug }}">{{ $option->name }}</flux:select.option>
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
                    $featuredImage = $listing->tenant?->featured_image_url;
                @endphp
                <article class="group overflow-hidden rounded-2xl bg-white/[0.03] transition hover:bg-white/[0.05]">
                    @if ($featuredImage)
                        <div class="relative h-36 w-full overflow-hidden">
                            <div
                                class="h-full w-full bg-cover bg-center transition duration-500 group-hover:scale-110"
                                style="background-image: url('{{ $featuredImage }}');"
                            ></div>
                            <div class="absolute inset-0 bg-gradient-to-t from-zinc-900 via-transparent to-transparent"></div>
                            @if ($listing->category)
                                <span class="absolute left-3 top-3 rounded-full bg-yellow-400 px-2.5 py-1 text-xs font-bold tracking-wide text-zinc-900 uppercase">
                                    {{ $categoryLabels[$listing->category] ?? $listing->category }}
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                @if ($listing->category && ! $featuredImage)
                                    <span class="text-xs font-medium tracking-wide text-yellow-400/90 uppercase">{{ $categoryLabels[$listing->category] ?? $listing->category }}</span>
                                @endif
                                <flux:heading size="lg" class="{{ $featuredImage ? '' : 'mt-1' }} text-white">{{ $listing->name }}</flux:heading>
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
