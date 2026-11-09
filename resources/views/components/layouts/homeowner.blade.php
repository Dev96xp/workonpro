<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-zinc-950 text-white antialiased">
    <header class="border-b border-white/10">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-black tracking-tight">
                <span class="flex size-8 items-center justify-center rounded-lg bg-yellow-400 text-zinc-950">W</span>
                Workon
            </a>

            <div class="flex items-center gap-3 text-sm">
                <a href="{{ url('/lang/'.(app()->getLocale() === 'es' ? 'en' : 'es')) }}" class="font-semibold text-zinc-500 hover:text-yellow-400">
                    {{ app()->getLocale() === 'es' ? 'EN' : 'ES' }}
                </a>

                @auth('homeowner')
                    <a href="{{ route('homeowner.dashboard') }}" class="hidden font-semibold text-zinc-300 hover:text-yellow-400 sm:inline">{{ __('homeowner.nav.my_projects') }}</a>
                    <span class="flex size-8 items-center justify-center rounded-full bg-yellow-400 text-xs font-black text-zinc-950" title="{{ auth('homeowner')->user()->name }}">
                        {{ auth('homeowner')->user()->initials() }}
                    </span>
                    <form method="POST" action="{{ route('homeowner.logout') }}">
                        @csrf
                        <button type="submit" class="font-semibold text-zinc-400 hover:text-yellow-400">{{ __('homeowner.nav.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('homeowner.login') }}" class="font-semibold text-zinc-300 hover:text-yellow-400">{{ __('homeowner.nav.login') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        {{ $slot }}
    </main>

    @fluxScripts
</body>
</html>
