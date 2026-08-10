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
            <div class="flex items-center gap-5">
                <div class="flex items-center gap-1 text-xs font-bold uppercase">
                    <a href="{{ route('lang.switch', 'es') }}" class="{{ app()->getLocale() === 'es' ? 'text-yellow-400' : 'text-zinc-500 hover:text-yellow-400' }}">ES</a>
                    <span class="text-zinc-600">/</span>
                    <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'text-yellow-400' : 'text-zinc-500 hover:text-yellow-400' }}">EN</a>
                </div>
                <a href="{{ route('home') }}" class="text-sm font-medium text-zinc-400 transition hover:text-yellow-400">
                    {{ __('search.back') }}
                </a>
            </div>
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
                    <span class="text-xs font-bold uppercase tracking-[0.2em] text-yellow-400">{{ __('search.badge') }}</span>
                    <div class="h-px w-8 bg-yellow-400"></div>
                </div>
                <flux:heading size="xl" class="font-black tracking-tight text-white sm:text-4xl lg:text-5xl">{{ __('search.title') }}</flux:heading>
                <flux:text class="mx-auto mt-3 max-w-md text-zinc-500 sm:text-lg">{{ __('search.subtitle') }}</flux:text>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-5xl px-6 pb-16 sm:px-8">

        {{-- Filters --}}
        <div class="mb-16 flex flex-col gap-3 sm:flex-row sm:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('search.search_placeholder') }}"
                icon="magnifying-glass"
                class="sm:flex-1"
            />

            <div class="flex gap-3">
                <flux:select wire:model.live="category" class="sm:w-48">
                    <flux:select.option value="">{{ __('search.category_placeholder') }}</flux:select.option>
                    @foreach ($categoryOptions as $option)
                        <flux:select.option value="{{ $option->slug }}">{{ $option->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="city" class="sm:w-44">
                    <flux:select.option value="">{{ __('search.city_placeholder') }}</flux:select.option>
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
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                @if ($featuredImage)
                                    <img src="{{ $featuredImage }}" alt="" class="size-8 shrink-0 rounded-sm object-cover" />
                                @endif
                                <div>
                                    @if ($listing->category)
                                        <span class="text-xs font-medium tracking-wide text-yellow-400/90 uppercase">{{ $categoryLabels[$listing->category] ?? $listing->category }}</span>
                                    @endif
                                    <flux:heading size="lg" class="mt-1 text-white">{{ $listing->name }}</flux:heading>
                                </div>
                            </div>
                            @if ($listing->price !== null)
                                <span class="whitespace-nowrap text-sm font-semibold text-white">${{ number_format($listing->price, 2) }}</span>
                            @endif
                        </div>

                        @if ($listing->description)
                            <flux:text class="mt-1.5 leading-relaxed text-zinc-500">{{ Str::limit($listing->description, 110) }}</flux:text>
                        @endif

                        <div class="mt-3 flex items-center justify-between border-t border-white/10 pt-3">
                            <div class="text-sm text-zinc-500">
                                <span class="text-zinc-300">{{ $listing->tenant?->name }}</span>
                                @if ($listing->city)
                                    <span> · {{ $listing->city }}</span>
                                @endif
                                @if ($listing->phone)
                                    <a href="tel:{{ $listing->phone }}" class="mt-1 flex items-center gap-1.5 text-zinc-400 transition hover:text-yellow-400">
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a2.25 2.25 0 00-2.309.652l-.658.658c-.4.4-1.023.494-1.505.201a11.264 11.264 0 01-4.923-4.923c-.293-.482-.199-1.105.201-1.505l.658-.658a2.25 2.25 0 00.652-2.309l-1.106-4.423A1.125 1.125 0 0010.618 3.75H9.375A2.25 2.25 0 007.125 6l.375.75z" />
                                        </svg>
                                        {{ $listing->phone }}
                                    </a>
                                @endif
                            </div>
                            @if ($url)
                                <a href="{{ $url }}" target="_blank" class="text-sm font-medium text-yellow-400 transition group-hover:translate-x-0.5 hover:text-yellow-300">
                                    {{ __('search.view_business') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-20 text-center text-zinc-600">
                    {{ __('search.no_results') }}
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
