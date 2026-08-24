<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $businessName }}</title>
    @vite('resources/css/welcome.css')
</head>
<body class="flex min-h-screen items-center justify-center overflow-x-hidden bg-zinc-900 px-4 py-10 text-zinc-900 antialiased">

    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -right-24 top-0 h-72 w-72 rotate-12 rounded-[40%_60%_70%_30%/40%_50%_60%_50%] bg-yellow-400/10 blur-3xl"></div>
        <div class="absolute -left-24 bottom-0 h-72 w-72 -rotate-6 rounded-[60%_40%_30%_70%/60%_30%_70%_40%] bg-yellow-400/5 blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-sm">
        <div class="mb-6 text-center">
            <a href="{{ url('/') }}" class="text-4xl font-black tracking-tighter text-yellow-400 transition hover:text-yellow-300">{{ $businessName }}</a>
        </div>

        <div class="card-slash bg-white p-8 shadow-2xl" style="clip-path: polygon(0 0, 100% 0, 100% 92%, 96% 100%, 0 100%);">
            <div class="flex flex-col items-center text-center">
                @if ($logoImage)
                    <img src="{{ $logoImage->url() }}" alt="{{ $businessName }}" class="size-20 rounded-full object-cover" />
                @else
                    <div class="flex size-20 items-center justify-center rounded-full bg-zinc-900 text-2xl font-black text-yellow-400">
                        {{ $initials }}
                    </div>
                @endif

                <h1 class="mt-5 text-3xl font-black tracking-tighter text-zinc-900">
                    <a href="{{ url('/') }}" class="transition hover:text-yellow-500">{{ $businessName }}</a>
                </h1>

                @if ($businessSlogan)
                    <div class="mt-2 inline-flex items-center gap-2">
                        <div class="h-px w-6 bg-yellow-400"></div>
                        <span class="text-xs font-bold uppercase tracking-[0.25em] text-zinc-500">{{ $businessSlogan }}</span>
                        <div class="h-px w-6 bg-yellow-400"></div>
                    </div>
                @endif

                @if ($businessAddress || $businessCity)
                    <p class="mt-1 text-sm font-medium text-zinc-400">{{ collect([$businessAddress, $businessCity])->filter()->implode(', ') }}</p>
                @endif
            </div>

            <div class="my-6 h-px bg-zinc-100"></div>

            @if ($businessPhone)
                <a href="tel:{{ $businessPhone }}" class="flex items-center gap-3 py-2 text-zinc-700 transition hover:text-zinc-900">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 00-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97a1.125 1.125 0 00.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                    </span>
                    <span class="text-sm font-semibold">{{ $businessPhone }}</span>
                </a>
            @endif

            <a href="{{ url('/') }}" class="flex items-center gap-3 py-2 text-zinc-700 transition hover:text-zinc-900">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </span>
                <span class="text-sm font-semibold">{{ $tenantDomain }}</span>
            </a>

            @if ($businessEmail)
                <a href="mailto:{{ $businessEmail }}" class="flex items-center gap-3 py-2 text-zinc-700 transition hover:text-zinc-900">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    <span class="text-sm font-semibold">{{ $businessEmail }}</span>
                </a>
            @endif

            @if ($businessPhone || $businessWa)
                <div class="mt-6 grid {{ $businessPhone && $businessWa ? 'grid-cols-2' : 'grid-cols-1' }} gap-3">
                    @if ($businessPhone)
                        <a href="tel:{{ $businessPhone }}" class="flex items-center justify-center gap-2 border-2 border-zinc-900 bg-zinc-900 px-4 py-3 text-sm font-black text-yellow-400 transition hover:bg-zinc-800">
                            {{ __('tenant.businesscard.call') }}
                        </a>
                    @endif
                    @if ($businessWa)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $businessWa) }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 border-2 border-zinc-900 bg-white px-4 py-3 text-sm font-black text-zinc-900 transition hover:bg-zinc-100">
                            {{ __('tenant.businesscard.whatsapp') }}
                        </a>
                    @endif
                </div>
            @endif

            <a href="{{ url('/card/vcard') }}" class="mt-3 flex w-full items-center justify-center gap-2 bg-yellow-400 px-4 py-3 text-sm font-black text-zinc-900 transition hover:bg-yellow-300">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                {{ __('tenant.businesscard.save_contact') }}
            </a>
        </div>

        <p class="mt-6 text-center text-xs text-zinc-500">
            {{ __('tenant.businesscard.powered_by') }} <a href="{{ route('home') }}" class="hover:text-yellow-400">Workon</a>
        </p>
    </div>

</body>
</html>
