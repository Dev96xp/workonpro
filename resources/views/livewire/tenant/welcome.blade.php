<?php

use App\Models\BusinessImage;
use App\Models\BusinessProfile;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] class extends Component {
    public bool $showCoupons = false;

    public bool $showLogin = false;

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function with(): array
    {
        $profile = BusinessProfile::first();

        return [
            'businessName'    => $profile?->business_name ?? tenant('name'),
            'businessSlogan'  => $profile?->slogan,
            'businessAddress' => $profile?->address,
            'businessPhone'   => $profile?->phone,
            'businessPhone2'  => $profile?->phone_2,
            'businessCity'    => $profile?->city,
            'businessEmail'   => $profile?->email,
            'businessWebsite' => $profile?->website,
            'businessHours'   => $profile?->business_hours,
            'businessWa'      => $profile?->whatsapp,
            'businessIg'          => $profile?->instagram,
            'businessFb'          => $profile?->facebook,
            'businessDescription' => $profile?->description,
            'businessPolicy'      => $profile?->policy,
            'businessObjectives'  => $profile?->objectives,
            'featuredImage'       => BusinessImage::gallery()->where('is_featured', true)->first(),
            'images'              => BusinessImage::gallery()->latest()->take(12)->get(),
            'coupons'         => Coupon::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->get(),
        ];
    }

    public function toggleCoupons(): void
    {
        $this->showCoupons = ! $this->showCoupons;
    }

    public function login(): void
    {
        $this->validate();

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => __('auth.failed'),
                ]);
            }

            RateLimiter::clear($this->throttleKey());
            Session::regenerate();

            $this->redirect(url('/dashboard'), navigate: true);

            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::lower($this->email).'|'.request()->ip();
    }
}; ?>

<div class="min-h-screen bg-zinc-950 text-white">

    {{-- Navbar --}}
    <nav class="fixed top-0 inset-x-0 z-50 flex items-center justify-between px-6 py-4 bg-zinc-950/80 backdrop-blur-md border-b border-zinc-800/50">
        <span class="font-black text-yellow-400 text-xl tracking-tight">{{ $businessName }}</span>
        <button
            wire:click="$set('showLogin', true)"
            class="inline-flex items-center gap-2 text-sm font-semibold text-zinc-300 hover:text-yellow-400 transition-colors duration-200"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Acceso
        </button>
    </nav>

    {{-- Login Modal --}}
    @if ($showLogin)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/70 backdrop-blur-sm"
            wire:click.self="$set('showLogin', false)"
            wire:transition
        >
            <div class="w-full max-w-sm bg-zinc-900 border border-zinc-700 rounded-2xl p-8 shadow-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-white">Acceso al Panel</h2>
                    <button wire:click="$set('showLogin', false)" class="text-zinc-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="login" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-zinc-300">Email</label>
                        <input
                            wire:model="email"
                            type="email"
                            required
                            autofocus
                            placeholder="admin@tunegocio.com"
                            class="bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-2.5 text-white placeholder-zinc-500 focus:outline-none focus:border-yellow-400 focus:ring-1 focus:ring-yellow-400 transition-colors"
                        />
                        @error('email') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-medium text-zinc-300">Contraseña</label>
                        <input
                            wire:model="password"
                            type="password"
                            required
                            class="bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-2.5 text-white placeholder-zinc-500 focus:outline-none focus:border-yellow-400 focus:ring-1 focus:ring-yellow-400 transition-colors"
                        />
                        @error('password') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-zinc-400 cursor-pointer">
                        <input wire:model="remember" type="checkbox" class="rounded border-zinc-600 bg-zinc-800 text-yellow-400 focus:ring-yellow-400" />
                        Recordarme
                    </label>

                    <button
                        type="submit"
                        class="w-full bg-yellow-400 hover:bg-yellow-300 text-zinc-900 font-bold py-2.5 rounded-lg transition-all duration-200 hover:scale-105 mt-2"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-70 cursor-not-allowed"
                    >
                        <span wire:loading.remove>Iniciar sesión</span>
                        <span wire:loading class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Entrando...
                        </span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Hero: full-screen image --}}
    <div class="relative h-screen w-full flex flex-col items-center justify-center text-center overflow-hidden">

        {{-- Background image --}}
        @if ($featuredImage)
            <img
                src="{{ $featuredImage->url() }}"
                alt="{{ $businessName }}"
                class="absolute inset-0 w-full h-full object-cover scale-105"
                style="animation: slowzoom 20s ease-in-out infinite alternate;"
            />
        @elseif ($images->isNotEmpty())
            <img
                src="{{ $images->first()->url() }}"
                alt="{{ $businessName }}"
                class="absolute inset-0 w-full h-full object-cover scale-105"
                style="animation: slowzoom 20s ease-in-out infinite alternate;"
            />
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-900 to-zinc-800"></div>
        @endif

        {{-- Gradient overlay --}}
        <div class="absolute inset-0 bg-gradient-to-b from-zinc-950/50 via-zinc-950/40 to-zinc-950"></div>

        {{-- Content --}}
        <div class="relative z-10 px-6 flex flex-col items-center gap-4">
            <h1 class="text-5xl md:text-8xl font-black tracking-tight text-white drop-shadow-2xl">
                {{ $businessName }}
            </h1>

            @if ($businessSlogan)
                <p class="text-yellow-300 text-xl md:text-2xl font-medium italic drop-shadow-lg">{{ $businessSlogan }}</p>
            @endif

            <div class="flex flex-wrap items-center justify-center gap-4 mt-2">
                @if ($businessAddress || $businessCity)
                    <span class="flex items-center gap-1.5 text-zinc-200 text-base">
                        <svg class="w-4 h-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ collect([$businessAddress, $businessCity])->filter()->implode(', ') }}
                    </span>
                @endif
                @if ($businessPhone)
                    <span class="flex items-center gap-1.5 text-zinc-200 text-base">
                        <svg class="w-4 h-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $businessPhone }}
                    </span>
                @endif
            </div>

            @if ($coupons->isNotEmpty())
                <button
                    wire:click="toggleCoupons"
                    class="mt-4 inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-300 text-zinc-900 font-bold px-8 py-3 rounded-full text-lg transition-all duration-200 shadow-lg shadow-yellow-400/30 hover:shadow-yellow-400/50 hover:scale-105"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    {{ $showCoupons ? 'Ocultar Cupones' : 'Ver Cupones' }}
                </button>
            @endif
        </div>

        {{-- Scroll indicator --}}
        @if ($images->count() > 1)
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 animate-bounce">
                <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        @endif
    </div>

    <style>
        @keyframes slowzoom {
            from { transform: scale(1.05); }
            to   { transform: scale(1.12); }
        }
    </style>

    {{-- Coupons --}}
    @if ($showCoupons && $coupons->isNotEmpty())
        <div class="max-w-5xl mx-auto px-6 pb-12" wire:transition>
            <h2 class="text-2xl font-bold text-yellow-400 mb-6 text-center">Cupones Disponibles</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($coupons as $coupon)
                    <div class="bg-zinc-900 border border-yellow-400/20 rounded-2xl p-5 flex flex-col gap-3 hover:border-yellow-400/60 transition-all duration-200">
                        <div class="flex items-center justify-between">
                            <span class="bg-yellow-400 text-zinc-900 font-black text-sm px-3 py-1 rounded-full tracking-widest">
                                {{ $coupon->code }}
                            </span>
                            <span class="text-yellow-400 font-black text-xl">
                                @if ($coupon->type === 'percentage')
                                    {{ number_format($coupon->value, 0) }}% OFF
                                @else
                                    ${{ number_format($coupon->value, 2) }} OFF
                                @endif
                            </span>
                        </div>
                        @if ($coupon->description)
                            <p class="text-zinc-300 text-sm">{{ $coupon->description }}</p>
                        @endif
                        @if ($coupon->expires_at)
                            <p class="text-zinc-500 text-xs">Válido hasta: {{ $coupon->expires_at->format('d/m/Y') }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Gallery --}}
    @if ($images->isNotEmpty())
        <div class="max-w-7xl mx-auto px-6 pb-20">
            <h2 class="text-2xl font-bold text-zinc-300 mb-8 text-center tracking-wider uppercase">Galería</h2>
            <div class="columns-2 sm:columns-3 lg:columns-4 gap-3 space-y-3">
                @foreach ($images as $image)
                    <div class="group relative break-inside-avoid overflow-hidden rounded-xl cursor-pointer">
                        <img
                            src="{{ $image->url() }}"
                            alt="{{ $image->original_name }}"
                            class="w-full object-cover transition-transform duration-500 group-hover:scale-110"
                            loading="lazy"
                        />
                        @if ($image->is_featured)
                            <div class="absolute left-2 top-2 rounded-full bg-yellow-400 p-1 shadow">
                                <svg class="size-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                </svg>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-end p-3">
                            <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                            </svg>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-24 text-zinc-600">
            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-lg">Pronto habrá fotos aquí</p>
        </div>
    @endif

    {{-- About / Description / Policy / Objectives --}}
    @if ($businessDescription || $businessPolicy || $businessObjectives || $businessPhone || $businessAddress)
        <div class="max-w-5xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                @if ($businessDescription)
                    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 flex flex-col gap-3">
                        <div class="flex items-center gap-2 text-yellow-400 font-bold text-sm uppercase tracking-widest">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Quiénes somos
                        </div>
                        <p class="text-zinc-300 text-sm leading-relaxed">{{ $businessDescription }}</p>
                    </div>
                @endif

                @if ($businessObjectives)
                    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 flex flex-col gap-3">
                        <div class="flex items-center gap-2 text-yellow-400 font-bold text-sm uppercase tracking-widest">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                            Objetivos
                        </div>
                        <p class="text-zinc-300 text-sm leading-relaxed">{{ $businessObjectives }}</p>
                    </div>
                @endif

                @if ($businessPolicy)
                    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 flex flex-col gap-3">
                        <div class="flex items-center gap-2 text-yellow-400 font-bold text-sm uppercase tracking-widest">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Política
                        </div>
                        <p class="text-zinc-300 text-sm leading-relaxed">{{ $businessPolicy }}</p>
                    </div>
                @endif

                @if ($businessPhone || $businessPhone2 || $businessAddress || $businessCity)
                    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 flex flex-col gap-3">
                        <div class="flex items-center gap-2 text-yellow-400 font-bold text-sm uppercase tracking-widest">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            Contacto
                        </div>
                        <div class="flex flex-col gap-2">
                            @if ($businessPhone)
                                <p class="text-zinc-300 text-sm flex items-center gap-2">
                                    <span class="text-yellow-400">Tel:</span> {{ $businessPhone }}
                                </p>
                            @endif
                            @if ($businessPhone2)
                                <p class="text-zinc-300 text-sm flex items-center gap-2">
                                    <span class="text-yellow-400">Tel 2:</span> {{ $businessPhone2 }}
                                </p>
                            @endif
                            @if ($businessAddress || $businessCity)
                                <p class="text-zinc-300 text-sm flex items-start gap-2">
                                    <span class="text-yellow-400 shrink-0">Dir:</span>
                                    {{ collect([$businessAddress, $businessCity])->filter()->implode(', ') }}
                                </p>
                            @endif
                            @if ($businessHours)
                                <p class="text-zinc-300 text-sm flex items-center gap-2">
                                    <span class="text-yellow-400">Horario:</span> {{ $businessHours }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @endif

    {{-- Company Info --}}
    <div class="border-t border-zinc-800 bg-zinc-900">
        <div class="max-w-5xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h3 class="text-yellow-400 font-black text-2xl mb-4">{{ $businessName }}</h3>
                <div class="flex flex-col gap-3">
                    @if ($businessAddress || $businessCity)
                        <div class="flex items-start gap-3 text-zinc-300">
                            <svg class="w-5 h-5 text-yellow-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>{{ collect([$businessAddress, $businessCity])->filter()->implode(', ') }}</span>
                        </div>
                    @endif
                    @if ($businessPhone)
                        <div class="flex items-center gap-3 text-zinc-300">
                            <svg class="w-5 h-5 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span>{{ $businessPhone }}</span>
                        </div>
                    @endif
                    @if ($businessPhone2)
                        <div class="flex items-center gap-3 text-zinc-300">
                            <svg class="w-5 h-5 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span>{{ $businessPhone2 }}</span>
                        </div>
                    @endif
                    @if ($businessWa)
                        <div class="flex items-center gap-3 text-zinc-300">
                            <svg class="w-5 h-5 text-yellow-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $businessWa) }}" target="_blank" class="hover:text-yellow-400 transition-colors">{{ $businessWa }}</a>
                        </div>
                    @endif
                    @if ($businessHours)
                        <div class="flex items-center gap-3 text-zinc-300">
                            <svg class="w-5 h-5 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ $businessHours }}</span>
                        </div>
                    @endif
                    @if ($businessEmail)
                        <div class="flex items-center gap-3 text-zinc-300">
                            <svg class="w-5 h-5 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ $businessEmail }}</span>
                        </div>
                    @endif
                    @if ($businessWebsite)
                        <div class="flex items-center gap-3 text-zinc-300">
                            <svg class="w-5 h-5 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            <a href="{{ $businessWebsite }}" target="_blank" class="text-yellow-400 hover:underline">{{ $businessWebsite }}</a>
                        </div>
                    @endif
                    @if ($businessIg || $businessFb)
                        <div class="flex items-center gap-3 mt-1">
                            @if ($businessIg)
                                <a href="https://instagram.com/{{ ltrim($businessIg, '@') }}" target="_blank" class="text-zinc-400 hover:text-yellow-400 transition-colors text-sm flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                    {{ $businessIg }}
                                </a>
                            @endif
                            @if ($businessFb)
                                <a href="{{ Str::startsWith($businessFb, 'http') ? $businessFb : 'https://'.$businessFb }}" target="_blank" class="text-zinc-400 hover:text-yellow-400 transition-colors text-sm flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    Facebook
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex flex-col justify-center">
                @if ($coupons->isNotEmpty())
                    <p class="text-zinc-400 text-sm mb-3">Tenemos <span class="text-yellow-400 font-bold">{{ $coupons->count() }} cupón(es)</span> disponible(s) para ti.</p>
                    <button
                        wire:click="toggleCoupons"
                        class="inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-300 text-zinc-900 font-bold px-6 py-2.5 rounded-full transition-all duration-200 hover:scale-105 self-start"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Ver Cupones
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="bg-zinc-950 py-4 text-center text-zinc-600 text-sm">
        {{ $businessName }} &mdash; Powered by <span class="text-yellow-400 font-semibold">Workon</span>
    </div>
</div>
