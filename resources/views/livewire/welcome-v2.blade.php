<?php

use App\Models\Category;
use App\Models\ServiceListing;
use App\Models\Tenant;
use App\Services\IpGeolocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] #[Title('Workon')] class extends Component
{
    public string $search = '';

    /** @var array<int, array{id: string, name: string, image: ?string, url: ?string, initial: string}> */
    public array $spotlight = [];

    public ?string $heroImage = null;

    public ?string $visitorCity = null;

    public function mount(): void
    {
        abort_unless(app()->environment('local', 'testing') || auth('super_admin')->check(), 404);

        $this->heroImage = $this->imageOfTheDay();
        $this->visitorCity = $this->detectVisitorCity();

        $this->spotlight = $this->activeTenants()
            ->whereNotNull('featured_image_url')
            ->inRandomOrder()
            ->limit(16)
            ->get()
            ->map(fn (Tenant $tenant) => $this->presentTenant($tenant))
            ->all();
    }

    public function with(): array
    {
        $categoryLabels = Category::query()
            ->get()
            ->mapWithKeys(fn (Category $category) => [
                $category->slug => app()->getLocale() === 'en' && $category->name_en ? $category->name_en : $category->name,
            ]);

        $categoriesByTenant = ServiceListing::query()
            ->where('is_active', true)
            ->get(['tenant_id', 'category'])
            ->groupBy('tenant_id')
            ->map(fn (Collection $listings) => $listings->pluck('category')->filter()->unique()->values());

        $tenants = $this->activeTenants()
            ->when(trim($this->search), fn ($query, string $term) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('signup_city', 'like', "%{$term}%")))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Tenant $tenant) => [$tenant->id => $this->presentTenant($tenant)]);

        $categoryChips = Category::query()
            ->where('is_active', true)
            ->whereIn('slug', $categoriesByTenant->flatten()->unique())
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Category $category) => ['slug' => $category->slug, 'label' => $categoryLabels->get($category->slug)]);

        return [
            'tenantsCount' => $this->activeTenants()->count(),
            'freshTenants' => $this->activeTenants()
                ->where('created_at', '>=', now()->subWeek())
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Tenant $tenant) => $this->presentTenant($tenant)),
            'categoryChips' => $categoryChips,
            'categoryBands' => $categoryChips
                ->map(fn (array $chip) => [
                    ...$chip,
                    'tenants' => $tenants
                        ->filter(fn (array $tenant, string $id) => $categoriesByTenant->get($id, collect())->contains($chip['slug']))
                        ->values()
                        ->all(),
                ])
                ->filter(fn (array $band) => count($band['tenants']) > 0)
                ->values(),
        ];
    }

    /**
     * @return Builder<Tenant>
     */
    private function activeTenants(): Builder
    {
        return Tenant::query()->where('status', 'active')->with('domains');
    }

    /**
     * Prefers the city Cloudflare already resolved, then falls back to an IP lookup using the
     * visitor's real address, since behind Cloudflare request()->ip() is the proxy's address.
     */
    private function detectVisitorCity(): ?string
    {
        if ($cloudflareCity = request()->header('CF-IPCity')) {
            return $cloudflareCity;
        }

        $ip = request()->header('CF-Connecting-IP') ?: request()->ip();

        return Cache::remember("visitor-city:{$ip}", now()->addDay(), fn () => app(IpGeolocationService::class)->city((string) $ip));
    }

    /**
     * Picks one featured image per calendar day, so every visitor sees the same hero until midnight.
     */
    private function imageOfTheDay(): ?string
    {
        $images = $this->activeTenants()
            ->whereNotNull('featured_image_url')
            ->orderBy('id')
            ->pluck('featured_image_url');

        if ($images->isEmpty()) {
            return null;
        }

        return $images->get(crc32(today()->toDateString()) % $images->count());
    }

    /**
     * @return array{id: string, name: string, image: ?string, url: ?string, initial: string}
     */
    private function presentTenant(Tenant $tenant): array
    {
        $domain = $tenant->domains->first()?->domain;
        $initial = Str::upper(Str::substr(Str::ascii((string) $tenant->name), 0, 1));

        return [
            'id' => $tenant->id,
            'name' => (string) $tenant->name,
            'image' => $tenant->featured_image_url,
            'url' => $domain ? request()->getScheme().'://'.$domain.'.'.parse_url(config('app.url'), PHP_URL_HOST) : null,
            'initial' => ctype_alpha($initial) ? $initial : '#',
        ];
    }
}; ?>

<div class="wv2 min-h-screen overflow-x-hidden bg-zinc-950 text-white">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:400,600,800" rel="stylesheet" />
    <style>
        .wv2 { font-family: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; }
        .wv2-grid { background-image: linear-gradient(rgb(255 255 255 / .04) 1px, transparent 1px), linear-gradient(90deg, rgb(255 255 255 / .04) 1px, transparent 1px); background-size: 48px 48px; }
        .wv2-float { animation: wv2-float 6s ease-in-out infinite; }
        .wv2-float-slow { animation: wv2-float 9s ease-in-out infinite; }
        .wv2-marquee { animation: wv2-marquee 40s linear infinite; }
        .wv2-marquee-wrap:hover .wv2-marquee { animation-play-state: paused; }
        .wv2-slide { animation: wv2-slide 20s linear infinite paused; }
        .wv2-slide-wrap:hover .wv2-slide { animation-play-state: running; }
        .wv2-glow { animation: wv2-glow 8s ease-in-out infinite; }
        @keyframes wv2-float { 0%, 100% { transform: translateY(0) rotate(var(--r, 0deg)); } 50% { transform: translateY(-14px) rotate(var(--r, 0deg)); } }
        @keyframes wv2-marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        @keyframes wv2-slide { 0% { transform: translateX(0); } 90%, 100% { transform: translateX(min(0px, calc(100vw - 100%))); } }
        @keyframes wv2-glow { 0%, 100% { transform: scale(1); opacity: .35; } 50% { transform: scale(1.15); opacity: .55; } }
        @media (prefers-reduced-motion: reduce) {
            .wv2-float, .wv2-float-slow, .wv2-marquee, .wv2-slide, .wv2-glow { animation: none; }
        }
    </style>

    {{-- Navbar --}}
    <header class="fixed inset-x-0 top-4 z-50 px-4">
        <div class="mx-auto flex max-w-5xl items-center justify-between rounded-full border border-white/10 bg-zinc-900/70 py-2 pl-5 pr-2 backdrop-blur-xl">
            <a href="{{ route('home') }}" class="text-xl font-extrabold tracking-tighter text-yellow-400">Workon</a>
            <div class="flex items-center gap-2 sm:gap-4">
                <div class="flex items-center gap-1 text-xs font-bold uppercase">
                    <a href="{{ route('lang.switch', 'es') }}" class="{{ app()->getLocale() === 'es' ? 'text-yellow-400' : 'text-zinc-500 hover:text-yellow-400' }}">ES</a>
                    <span class="text-zinc-700">/</span>
                    <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'text-yellow-400' : 'text-zinc-500 hover:text-yellow-400' }}">EN</a>
                </div>
                <a href="{{ route('search') }}" class="flex size-9 items-center justify-center rounded-full text-zinc-300 transition hover:bg-white/10 hover:text-yellow-400" title="{{ __('welcome_v2.nav.search') }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </a>
                <a href="{{ route('register.plans') }}" class="rounded-full bg-yellow-400 px-5 py-2 text-sm font-extrabold text-zinc-950 transition hover:scale-105 hover:bg-yellow-300">
                    {{ __('welcome_v2.nav.join') }}
                </a>
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative flex min-h-[100svh] items-center justify-center overflow-hidden px-4 pb-16 pt-32">
        @if ($heroImage)
            <img src="{{ $heroImage }}" alt="" class="pointer-events-none absolute inset-0 size-full scale-105 object-cover">
            <div class="pointer-events-none absolute inset-0 bg-zinc-950/75"></div>
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-zinc-950/60 via-transparent to-zinc-950"></div>
        @endif
        <div class="wv2-grid pointer-events-none absolute inset-0"></div>
        <div class="wv2-glow pointer-events-none absolute left-1/2 top-1/2 size-[28rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-yellow-400 blur-[120px] sm:size-[40rem]"></div>

        {{-- Floating category tags --}}
        @foreach ($categoryChips->take(6) as $index => $chip)
            @php
                $positions = ['left-[6%] top-[22%]', 'right-[8%] top-[18%]', 'left-[10%] bottom-[24%]', 'right-[6%] bottom-[30%]', 'left-[30%] top-[14%]', 'right-[28%] bottom-[14%]'];
                $rotations = ['-8deg', '6deg', '4deg', '-5deg', '3deg', '-4deg'];
            @endphp
            <span class="{{ $index % 2 ? 'wv2-float-slow' : 'wv2-float' }} absolute hidden rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-zinc-200 backdrop-blur-md md:block {{ $positions[$index] }}" style="--r: {{ $rotations[$index] }}; animation-delay: -{{ $index * 1.3 }}s">
                #{{ Str::lower(Str::replace(' ', '', $chip['label'])) }}
            </span>
        @endforeach

        <div class="relative flex flex-col items-center text-center">
            <div class="mb-6 flex flex-wrap items-center justify-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-yellow-400/30 bg-yellow-400/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-yellow-400">
                    <span class="size-2 animate-pulse rounded-full bg-yellow-400"></span>
                    {{ __('welcome_v2.hero.badge') }}
                </span>
                @if ($visitorCity)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-1.5 text-xs font-extrabold uppercase tracking-widest text-zinc-950">
                        📍 {{ __('welcome_v2.hero.visitor_city', ['city' => $visitorCity]) }}
                    </span>
                @endif
            </div>

            <div class="relative">
                <h1 class="text-[24vw] font-extrabold leading-[0.8] tracking-[-0.06em] text-yellow-400 drop-shadow-[0_10px_60px_rgba(250,204,21,0.35)] md:text-[13rem] lg:text-[16rem]">
                    Workon
                </h1>

                {{-- Counter --}}
                <div
                    x-data="{ count: 0, target: {{ $tenantsCount }} }"
                    x-init="let step = Math.max(1, Math.ceil(target / 60)); let timer = setInterval(() => { count = Math.min(target, count + step); if (count >= target) clearInterval(timer) }, 25)"
                    class="wv2-float mx-auto mt-8 w-fit rotate-[-4deg] rounded-3xl border-2 border-zinc-950 bg-white px-6 py-4 text-left text-zinc-950 shadow-[6px_6px_0_0_#facc15] md:absolute md:-right-16 md:-top-14 md:mt-0 lg:-right-24"
                    style="--r: -4deg"
                >
                    <p class="text-5xl font-extrabold leading-none tracking-tighter">+<span x-text="count">{{ $tenantsCount }}</span></p>
                    <p class="mt-1 max-w-[10rem] text-sm font-semibold leading-tight">{{ __('welcome_v2.hero.counter_label') }}</p>
                    <p class="mt-1 text-xs font-bold uppercase tracking-widest text-zinc-500">{{ __('welcome_v2.hero.counter_tag') }} 🚀</p>
                </div>
            </div>

            <p class="mt-10 max-w-lg text-lg text-zinc-400 sm:text-xl">{{ __('welcome_v2.hero.subtitle') }}</p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="#directory" class="group inline-flex items-center justify-center gap-2 rounded-full bg-yellow-400 px-8 py-4 font-extrabold text-zinc-950 transition hover:scale-105 hover:bg-yellow-300">
                    {{ __('welcome_v2.hero.cta_explore') }}
                    <svg class="size-5 transition group-hover:translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                    </svg>
                </a>
                <a href="{{ route('register.plans') }}" class="inline-flex items-center justify-center rounded-full border border-white/20 px-8 py-4 font-bold text-white transition hover:border-yellow-400 hover:text-yellow-400">
                    {{ __('welcome_v2.hero.cta_join') }} ✨
                </a>
            </div>
        </div>
    </section>

    {{-- Stories --}}
    @if (count($spotlight))
        <section class="relative border-y border-white/10 bg-zinc-900 py-10">
            <div class="wv2-marquee-wrap relative overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_8%,black_92%,transparent)]">
                <div class="wv2-marquee flex w-max gap-6 px-3 sm:gap-8">
                    @foreach ([...$spotlight, ...$spotlight] as $tenant)
                        <a href="{{ $tenant['url'] ?? '#' }}" target="_blank" class="group flex w-24 shrink-0 flex-col items-center gap-2 sm:w-28">
                            <span class="rounded-full bg-gradient-to-tr from-yellow-500 via-yellow-300 to-amber-500 p-[3px] transition duration-300 group-hover:rotate-6 group-hover:scale-110">
                                <img src="{{ $tenant['image'] }}" alt="{{ $tenant['name'] }}" loading="lazy" class="size-20 rounded-full border-4 border-zinc-900 object-cover sm:size-24">
                            </span>
                            <span class="w-full truncate text-center text-xs font-semibold text-zinc-300 group-hover:text-yellow-400">{{ $tenant['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- New this week --}}
    @if ($freshTenants->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 pt-20 sm:px-6">
            <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('welcome_v2.fresh.title') }} 🔥</h2>
            <div class="-mx-4 mt-6 flex snap-x gap-4 overflow-x-auto px-4 pb-4 [scrollbar-width:none] sm:mx-0 sm:px-0">
                @foreach ($freshTenants as $tenant)
                    <a href="{{ $tenant['url'] ?? '#' }}" target="_blank" class="group flex w-64 shrink-0 snap-start items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 transition hover:border-yellow-400/60 hover:bg-white/10">
                        @if ($tenant['image'])
                            <img src="{{ $tenant['image'] }}" alt="{{ $tenant['name'] }}" loading="lazy" class="size-14 rounded-xl object-cover">
                        @else
                            <span class="flex size-14 items-center justify-center rounded-xl bg-yellow-400 text-2xl font-extrabold text-zinc-950">{{ $tenant['initial'] }}</span>
                        @endif
                        <div class="min-w-0">
                            <span class="rounded-full bg-yellow-400 px-2 py-0.5 text-[10px] font-extrabold uppercase text-zinc-950">{{ __('welcome_v2.fresh.badge') }}</span>
                            <p class="mt-1 truncate font-bold group-hover:text-yellow-400">{{ $tenant['name'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Businesses by category --}}
    <section id="directory" class="scroll-mt-24 py-20">
        {{-- Search: floating at the bottom on small screens only --}}
        <div class="pointer-events-none fixed inset-x-0 bottom-4 z-50 px-3 md:hidden">
            <label class="pointer-events-auto mx-auto flex w-full items-center gap-3 rounded-full border border-white/15 bg-zinc-900/80 py-2 pl-5 pr-2 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.8)] backdrop-blur-xl transition focus-within:border-yellow-400 focus-within:shadow-[0_0_0_4px_rgba(250,204,21,0.15),0_20px_60px_-15px_rgba(0,0,0,0.8)]">
                <svg class="size-5 shrink-0 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    x-on:input.debounce.400ms="document.getElementById('directory').scrollIntoView({ behavior: 'smooth' })"
                    placeholder="{{ __('welcome_v2.search.placeholder') }}"
                    class="min-w-0 flex-1 bg-transparent py-2 text-base font-semibold text-white placeholder:text-zinc-500 focus:outline-none [&::-webkit-search-cancel-button]:hidden"
                >
                @if (trim($search))
                    <button type="button" wire:click="$set('search', '')" class="flex size-10 shrink-0 items-center justify-center rounded-full text-zinc-400 transition hover:bg-white/10 hover:text-white" title="{{ __('welcome_v2.search.clear') }}">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
                <a href="#directory" class="shrink-0 rounded-full bg-yellow-400 px-5 py-2.5 text-sm font-extrabold text-zinc-950 transition hover:scale-105 hover:bg-yellow-300">
                    {{ __('welcome_v2.search.button') }}
                </a>
            </label>
        </div>

        {{-- Category bands --}}
        <div class="space-y-10" wire:loading.class="opacity-50">
            @forelse ($categoryBands as $band)
                <div>
                    <div class="mb-4 flex items-center gap-3 px-4 sm:px-6">
                        <h3 class="text-xl font-extrabold tracking-tight sm:text-2xl">{{ $band['label'] }}</h3>
                        <span class="rounded-full bg-yellow-400 px-2.5 py-0.5 text-xs font-extrabold text-zinc-950">{{ count($band['tenants']) }}</span>
                    </div>

                    <div class="wv2-slide-wrap relative overflow-x-auto [scrollbar-width:none] [mask-image:linear-gradient(to_right,black_92%,transparent)] md:overflow-hidden">
                        <div class="wv2-slide flex w-max gap-6 px-4 pb-1 pt-2 sm:gap-8 sm:px-6" style="animation-duration: {{ 6 + count($band['tenants']) * 2 }}s">
                            @foreach ($band['tenants'] as $tenant)
                                <a href="{{ $tenant['url'] ?? '#' }}" target="_blank" class="group flex w-24 shrink-0 flex-col items-center gap-2 sm:w-28">
                                    <span class="rounded-[1.4rem] bg-gradient-to-tr from-yellow-500 via-yellow-300 to-amber-500 p-[3px] transition duration-300 group-hover:-rotate-6 group-hover:scale-110">
                                        @if ($tenant['image'])
                                            <img src="{{ $tenant['image'] }}" alt="{{ $tenant['name'] }}" loading="lazy" class="size-20 rounded-[1.2rem] border-4 border-zinc-950 object-cover sm:size-24">
                                        @else
                                            <span class="flex size-20 items-center justify-center rounded-[1.2rem] border-4 border-zinc-950 bg-zinc-800 text-3xl font-extrabold text-yellow-400 sm:size-24">{{ $tenant['initial'] }}</span>
                                        @endif
                                    </span>
                                    <span class="w-full truncate text-center text-xs font-semibold text-zinc-300 group-hover:text-yellow-400">{{ $tenant['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="mx-4 rounded-3xl border border-dashed border-white/15 p-12 text-center text-zinc-400 sm:mx-6">
                    {{ trim($search) ? __('welcome_v2.directory.no_results', ['term' => trim($search)]) : __('welcome_v2.directory.empty') }}
                </div>
            @endforelse
        </div>
    </section>

    {{-- CTA --}}
    <section class="px-4 pb-20 sm:px-6">
        <div class="relative mx-auto max-w-6xl overflow-hidden rounded-[2.5rem] bg-yellow-400 px-6 py-16 text-center text-zinc-950 sm:px-12 sm:py-20">
            <div class="pointer-events-none absolute -right-10 -top-10 size-48 rounded-full bg-white/30 blur-2xl"></div>
            <h2 class="relative text-4xl font-extrabold tracking-tighter sm:text-6xl">{{ __('welcome_v2.cta.title') }}</h2>
            <p class="relative mx-auto mt-4 max-w-md text-lg font-medium text-zinc-800">{{ __('welcome_v2.cta.subtitle') }}</p>
            <a href="{{ route('register.plans') }}" class="group relative mt-8 inline-flex items-center gap-2 rounded-full bg-zinc-950 px-10 py-5 text-lg font-extrabold text-yellow-400 transition hover:scale-105">
                {{ __('welcome_v2.cta.button') }}
                <svg class="size-5 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-white/10 pb-32 pt-10 md:pb-10">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 sm:px-6 md:flex-row">
            <span class="text-xl font-extrabold tracking-tighter text-yellow-400">Workon</span>
            <p class="text-sm text-zinc-600">© {{ date('Y') }} Workon. {{ __('welcome.footer.rights') }}</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('terms') }}" class="text-sm text-zinc-500 transition hover:text-yellow-400">{{ __('welcome.footer.terms') }}</a>
                <a href="{{ route('privacy') }}" class="text-sm text-zinc-500 transition hover:text-yellow-400">{{ __('welcome.footer.privacy') }}</a>
            </div>
        </div>
    </footer>
</div>
