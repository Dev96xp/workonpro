<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('welcome.meta.title') }}</title>
    @vite('resources/css/welcome.css')
    <style>
        .clip-hero  { clip-path: polygon(0 0, 100% 0, 100% 92%, 0 100%); }
        .clip-feat  { clip-path: polygon(0 3%, 100% 0, 100% 97%, 0 100%); }
        .clip-price { clip-path: polygon(0 0, 100% 4%, 100% 100%, 0 96%); }
        .clip-cta   { clip-path: polygon(0 5%, 100% 0, 100% 100%, 0 100%); }
        .card-slash { clip-path: polygon(0 0, 100% 0, 100% 92%, 96% 100%, 0 100%); }
        @media (max-width: 640px) {
            .clip-hero, .clip-feat, .clip-price, .clip-cta { clip-path: none; }
        }
    </style>
</head>
<body class="bg-stone-100 text-zinc-900 antialiased overflow-x-hidden">

    {{-- Navbar --}}
    <header class="fixed top-0 z-50 w-full border-b border-white/10 bg-zinc-900/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
            <span class="text-2xl font-black tracking-tighter text-yellow-400 sm:text-3xl">Workon</span>
            <nav class="flex items-center gap-3 sm:gap-8">
                <a href="#features" class="hidden text-sm font-medium text-zinc-400 transition hover:text-yellow-400 md:block">{{ __('welcome.nav.features') }}</a>
                <a href="#pricing"  class="hidden text-sm font-medium text-zinc-400 transition hover:text-yellow-400 md:block">{{ __('welcome.nav.pricing') }}</a>

                @auth('super_admin')
                    <details class="group relative hidden sm:block">
                        <summary class="flex cursor-pointer list-none items-center gap-2 text-sm font-medium text-zinc-400 transition hover:text-yellow-400">
                            <span class="flex size-7 items-center justify-center rounded-full bg-yellow-400 text-xs font-black text-zinc-900">
                                {{ Str::of(auth('super_admin')->user()->name)->explode(' ')->map(fn ($n) => Str::substr($n, 0, 1))->implode('') }}
                            </span>
                            {{ auth('super_admin')->user()->name }}
                            <svg class="size-3 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>

                        <div class="absolute right-0 z-10 mt-3 w-56 border border-zinc-800 bg-zinc-900 py-2 shadow-xl">
                            <div class="px-4 py-2">
                                <p class="truncate text-sm font-semibold text-white">{{ auth('super_admin')->user()->name }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ __('welcome.nav.admin') }}</p>
                            </div>
                            <div class="my-1 h-px bg-zinc-800"></div>
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-zinc-400 transition hover:bg-zinc-800 hover:text-yellow-400">{{ __('welcome.nav.panel') }}</a>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-zinc-400 transition hover:bg-zinc-800 hover:text-yellow-400">{{ __('welcome.nav.logout') }}</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ url('/admin/login') }}" class="hidden text-sm font-medium text-zinc-400 transition hover:text-yellow-400 sm:block">{{ __('welcome.nav.login') }}</a>
                @endauth

                {{-- Idioma --}}
                <div class="flex items-center gap-1 text-xs font-bold uppercase">
                    <a href="{{ route('lang.switch', 'es') }}" class="{{ app()->getLocale() === 'es' ? 'text-yellow-400' : 'text-zinc-500 hover:text-yellow-400' }}">ES</a>
                    <span class="text-zinc-600">/</span>
                    <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'text-yellow-400' : 'text-zinc-500 hover:text-yellow-400' }}">EN</a>
                </div>

                {{-- Buscar --}}
                <a href="{{ route('search') }}" class="flex items-center justify-center p-1 text-zinc-300 transition hover:text-yellow-400" title="{{ __('welcome.nav.search') }}">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </a>

                {{-- Mobile menu --}}
                <details class="group relative md:hidden">
                    <summary class="flex cursor-pointer list-none items-center justify-center p-1 text-zinc-300 transition hover:text-yellow-400">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </summary>

                    <div class="absolute right-0 z-10 mt-3 w-56 border border-zinc-800 bg-zinc-900 py-2 shadow-xl">
                        <a href="#features" class="block px-4 py-2 text-sm text-zinc-400 transition hover:bg-zinc-800 hover:text-yellow-400">{{ __('welcome.nav.features') }}</a>
                        <a href="#pricing" class="block px-4 py-2 text-sm text-zinc-400 transition hover:bg-zinc-800 hover:text-yellow-400">{{ __('welcome.nav.pricing') }}</a>
                        <a href="{{ route('search') }}" class="block px-4 py-2 text-sm text-zinc-400 transition hover:bg-zinc-800 hover:text-yellow-400">{{ __('welcome.nav.search') }}</a>
                        <div class="my-1 h-px bg-zinc-800"></div>
                        @auth('super_admin')
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-zinc-400 transition hover:bg-zinc-800 hover:text-yellow-400">{{ __('welcome.nav.panel') }}</a>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-zinc-400 transition hover:bg-zinc-800 hover:text-yellow-400">{{ __('welcome.nav.logout') }}</button>
                            </form>
                        @else
                            <a href="{{ url('/admin/login') }}" class="block px-4 py-2 text-sm text-zinc-400 transition hover:bg-zinc-800 hover:text-yellow-400">{{ __('welcome.nav.login') }}</a>
                        @endauth
                    </div>
                </details>

                <a href="{{ route('register.plans') }}" class="border-b-2 border-yellow-400 bg-yellow-400 px-4 py-2 text-sm font-black text-zinc-900 transition hover:bg-transparent hover:text-yellow-400 sm:px-6">
                    {{ __('welcome.nav.start') }}
                </a>
            </nav>
        </div>
    </header>

    {{-- Hero --}}
    <section class="clip-hero relative bg-zinc-900 pb-32 pt-28 sm:pb-40 sm:pt-32">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -right-32 top-20 h-72 w-72 rotate-12 rounded-[40%_60%_70%_30%/40%_50%_60%_50%] bg-yellow-400/10 blur-3xl sm:h-[600px] sm:w-[600px]"></div>
            <div class="absolute -left-20 bottom-20 h-48 w-64 -rotate-6 rounded-[60%_40%_30%_70%/60%_30%_70%_40%] bg-yellow-400/5 blur-2xl sm:h-[400px] sm:w-[500px]"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mb-6 inline-flex items-center gap-3">
                <div class="h-px w-8 bg-yellow-400 sm:w-12"></div>
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-yellow-400 sm:tracking-[0.3em]">{{ __('welcome.hero.badge') }}</span>
            </div>

            <h1 class="text-5xl font-black leading-[0.9] tracking-tighter text-white sm:text-7xl lg:text-9xl">
                <span class="block">{{ __('welcome.hero.title_line1') }}</span>
                <span class="block sm:pl-8 text-yellow-400">{{ __('welcome.hero.title_line2') }}</span>
                <span class="block sm:pl-16 text-zinc-500">{{ __('welcome.hero.title_line3') }}</span>
            </h1>

            <p class="mt-6 max-w-xl text-base leading-relaxed text-zinc-400 sm:mt-10 sm:text-lg">
                {{ __('welcome.hero.subtitle') }}
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:mt-12 sm:flex-row sm:gap-4">
                <a href="{{ route('register.plans') }}" class="group inline-flex items-center justify-center gap-3 bg-yellow-400 px-8 py-4 text-base font-black text-zinc-900 transition hover:bg-yellow-300 sm:justify-start sm:px-10 sm:py-5">
                    <span>{{ __('welcome.hero.cta_start') }}</span>
                    <svg class="size-5 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                <a href="#features" class="inline-flex items-center justify-center border border-zinc-700 px-8 py-4 text-base font-medium text-zinc-400 transition hover:border-yellow-400 hover:text-yellow-400 sm:px-10 sm:py-5">
                    {{ __('welcome.hero.cta_features') }}
                </a>
            </div>

            <div class="mt-12 flex flex-wrap gap-6 sm:mt-20 sm:gap-10">
                <div class="border-l-2 border-yellow-400 pl-3 sm:pl-4">
                    <p class="text-2xl font-black text-white sm:text-3xl">100%</p>
                    <p class="text-xs uppercase tracking-widest text-zinc-500">{{ __('welcome.hero.stat_multitenant') }}</p>
                </div>
                <div class="border-l-2 border-zinc-700 pl-3 sm:pl-4">
                    <p class="text-2xl font-black text-white sm:text-3xl">SSL</p>
                    <p class="text-xs uppercase tracking-widest text-zinc-500">{{ __('welcome.hero.stat_secure') }}</p>
                </div>
                <div class="border-l-2 border-zinc-700 pl-3 sm:pl-4">
                    <p class="text-2xl font-black text-white sm:text-3xl">Auto</p>
                    <p class="text-xs uppercase tracking-widest text-zinc-500">{{ __('welcome.hero.stat_compression') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="clip-feat relative -mt-8 bg-stone-100 py-24 sm:-mt-16 sm:py-40">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mb-4 inline-flex items-center gap-3">
                <div class="h-px w-8 bg-zinc-900"></div>
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-zinc-500 sm:tracking-[0.3em]">{{ __('welcome.features.badge') }}</span>
            </div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <h2 class="text-4xl font-black leading-tight tracking-tighter text-zinc-900 sm:text-5xl">
                    {{ __('welcome.features.title_line1') }}<br><span class="text-yellow-400">{{ __('welcome.features.title_line2') }}</span>
                </h2>
                <p class="hidden max-w-xs text-sm leading-relaxed text-zinc-500 md:block">
                    {{ __('welcome.features.subtitle') }}
                </p>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-4 sm:mt-16 sm:grid-cols-3 sm:gap-0">
                <div class="card-slash group border-t-2 border-zinc-900 bg-zinc-900 p-8 text-white transition hover:border-yellow-400 sm:p-10">
                    <div class="mb-5 text-yellow-400 sm:mb-6">
                        <svg class="size-8 sm:size-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black sm:text-2xl">{{ __('welcome.features.clients_title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-400 sm:mt-3">{{ __('welcome.features.clients_text') }}</p>
                    <div class="mt-6 h-px w-12 bg-yellow-400 transition group-hover:w-full sm:mt-8"></div>
                </div>

                <div class="card-slash group border-t-2 border-yellow-400 bg-yellow-400 p-8 transition hover:bg-yellow-300 sm:p-10">
                    <div class="mb-5 text-zinc-900 sm:mb-6">
                        <svg class="size-8 sm:size-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black text-zinc-900 sm:text-2xl">{{ __('welcome.features.coupons_title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-800 sm:mt-3">{{ __('welcome.features.coupons_text') }}</p>
                    <div class="mt-6 h-px w-12 bg-zinc-900 transition group-hover:w-full sm:mt-8"></div>
                </div>

                <div class="card-slash group border-t-2 border-stone-300 bg-white p-8 transition hover:border-yellow-400 sm:p-10">
                    <div class="mb-5 text-zinc-900 sm:mb-6">
                        <svg class="size-8 sm:size-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black text-zinc-900 sm:text-2xl">{{ __('welcome.features.images_title') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-500 sm:mt-3">{{ __('welcome.features.images_text') }}</p>
                    <div class="mt-6 h-px w-12 bg-yellow-400 transition group-hover:w-full sm:mt-8"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="clip-price -mt-8 bg-zinc-900 py-24 sm:-mt-12 sm:py-40">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mb-4 inline-flex items-center gap-3">
                <div class="h-px w-8 bg-yellow-400"></div>
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-yellow-400 sm:tracking-[0.3em]">{{ __('welcome.pricing.badge') }}</span>
            </div>
            <h2 class="text-4xl font-black tracking-tighter text-white sm:text-5xl">
                {{ __('welcome.pricing.title_line1') }} <span class="text-yellow-400">{{ __('welcome.pricing.title_line2') }}</span>
            </h2>

            <div class="mt-10 grid grid-cols-1 gap-px bg-zinc-700 sm:mt-16 md:grid-cols-3">
                <div class="group relative flex flex-col bg-zinc-900 p-6 transition hover:bg-zinc-800 sm:p-10">
                    <div class="absolute right-4 top-4 rotate-12 bg-yellow-400 px-3 py-1 text-xs font-black uppercase tracking-wider text-zinc-900 sm:right-6 sm:top-6">
                        {{ __('welcome.pricing.basic_badge') }}
                    </div>
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-zinc-500">{{ __('welcome.pricing.basic_name') }}</p>
                    <div class="mt-4 flex items-end gap-2">
                        <span class="text-5xl font-black text-white sm:text-6xl">{{ __('welcome.pricing.basic_price') }}</span>
                        <span class="mb-2 text-sm text-zinc-500 line-through">{{ __('welcome.pricing.basic_old_price') }}</span>
                    </div>
                    <div class="mt-6 h-px bg-zinc-700 transition group-hover:bg-yellow-400/50 sm:mt-8"></div>
                    <ul class="mt-6 grow space-y-3 text-sm text-zinc-400 sm:mt-8 sm:space-y-4">
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('welcome.pricing.basic_item_images') }}</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('welcome.pricing.basic_item_services') }}</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('welcome.pricing.item_unlimited_clients') }}</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('welcome.pricing.item_coupons') }}</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('welcome.pricing.item_subdomain') }}</li>
                    </ul>
                    <a href="{{ route('register.plans') }}" class="mt-8 block border border-zinc-700 py-4 text-center text-sm font-bold text-zinc-400 transition hover:border-yellow-400 hover:text-yellow-400">
                        {{ __('welcome.pricing.cta_start') }}
                    </a>
                </div>

                <div class="group relative flex flex-col bg-yellow-400 p-6 sm:p-10">
                    <div class="absolute right-4 top-4 rotate-12 bg-zinc-900 px-3 py-1 text-xs font-black uppercase tracking-wider text-yellow-400 sm:right-6 sm:top-6">
                        {{ __('welcome.pricing.pro_badge') }}
                    </div>
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-zinc-700">{{ __('welcome.pricing.pro_name') }}</p>
                    <div class="mt-4 flex items-end gap-2">
                        <span class="text-5xl font-black text-zinc-900 sm:text-6xl">{{ __('welcome.pricing.pro_price') }}</span>
                        <span class="mb-2 text-zinc-700">{{ __('welcome.pricing.pro_per_month') }}</span>
                        <span class="mb-2 text-sm text-zinc-700/70 line-through">{{ __('welcome.pricing.pro_old_price') }}</span>
                    </div>
                    <div class="mt-6 h-px bg-zinc-900/20 sm:mt-8"></div>
                    <ul class="mt-6 grow space-y-3 text-sm text-zinc-800 sm:mt-8 sm:space-y-4">
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> {{ __('welcome.pricing.pro_item_images') }}</li>
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> {{ __('welcome.pricing.item_unlimited_clients') }}</li>
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> {{ __('welcome.pricing.item_coupons') }}</li>
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> {{ __('welcome.pricing.item_subdomain') }}</li>
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> <span class="font-bold">{{ __('welcome.pricing.item_billing') }}</span></li>
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> <span class="font-bold">{{ __('welcome.pricing.item_appointments') }}</span></li>
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> {{ __('welcome.pricing.pro_item_support') }}</li>
                    </ul>
                    <a href="{{ route('register.plans') }}" class="mt-8 block bg-zinc-900 py-4 text-center text-sm font-black text-yellow-400 transition hover:bg-zinc-800">
                        {{ __('welcome.pricing.cta_start_pro') }}
                    </a>
                </div>

                <div class="group flex flex-col bg-zinc-900 p-6 transition hover:bg-zinc-800 sm:p-10">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-zinc-500">{{ __('welcome.pricing.enterprise_name') }}</p>
                    <div class="mt-4 flex items-end gap-2">
                        <span class="text-5xl font-black text-white sm:text-6xl">{{ __('welcome.pricing.enterprise_price') }}</span>
                        <span class="mb-2 text-zinc-500">{{ __('welcome.pricing.pro_per_month') }}</span>
                        <span class="mb-2 text-sm text-zinc-500/70 line-through">{{ __('welcome.pricing.enterprise_old_price') }}</span>
                    </div>
                    <div class="mt-6 h-px bg-zinc-700 transition group-hover:bg-yellow-400/50 sm:mt-8"></div>
                    <ul class="mt-6 grow space-y-3 text-sm text-zinc-400 sm:mt-8 sm:space-y-4">
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('welcome.pricing.enterprise_item_images') }}</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('welcome.pricing.item_unlimited_clients') }}</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('welcome.pricing.item_coupons') }}</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('welcome.pricing.item_subdomain') }}</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> <span class="font-bold">{{ __('welcome.pricing.item_billing') }}</span></li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> <span class="font-bold">{{ __('welcome.pricing.item_appointments') }}</span></li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> <span class="font-bold">{{ __('welcome.pricing.item_employees') }}</span></li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('welcome.pricing.enterprise_item_support') }}</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('welcome.pricing.enterprise_item_integrations') }}</li>
                    </ul>
                    <a href="{{ route('register.plans') }}" class="mt-8 block border border-zinc-700 py-4 text-center text-sm font-bold text-zinc-400 transition hover:border-yellow-400 hover:text-yellow-400">
                        {{ __('welcome.pricing.cta_start') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="clip-cta -mt-8 bg-stone-100 py-24 sm:-mt-12 sm:py-40">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="flex flex-col gap-8 sm:gap-12 md:flex-row md:items-end md:justify-between">
                <h2 class="text-5xl font-black leading-none tracking-tighter text-zinc-900 sm:text-6xl md:text-8xl">
                    {{ __('welcome.cta.title_line1') }}<br><span class="text-yellow-400">{{ __('welcome.cta.title_line2') }}</span>
                </h2>
                <div class="flex flex-col gap-4 sm:gap-6 md:items-end">
                    <p class="max-w-sm text-zinc-500 md:text-right">
                        {{ __('welcome.cta.subtitle') }}
                    </p>
                    <a href="{{ route('register.plans') }}" class="group inline-flex items-center gap-3 bg-zinc-900 px-8 py-4 text-base font-black text-yellow-400 transition hover:bg-zinc-800 sm:px-10 sm:py-5">
                        {{ __('welcome.cta.button') }}
                        <svg class="size-5 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-zinc-900 py-10 sm:py-12">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 sm:gap-6 sm:px-6 md:flex-row">
            <span class="text-xl font-black tracking-tighter text-yellow-400 sm:text-2xl">Workon</span>
            <p class="text-sm text-zinc-600">© {{ date('Y') }} Workon. {{ __('welcome.footer.rights') }}</p>
            <a href="{{ auth('super_admin')->check() ? route('admin.dashboard') : url('/admin/login') }}" class="text-sm text-zinc-500 transition hover:text-yellow-400">
                {{ auth('super_admin')->check() ? __('welcome.footer.panel') : __('welcome.footer.login') }}
            </a>
        </div>
    </footer>

</body>
</html>
