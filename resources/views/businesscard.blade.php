<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ricardo Ramirez — Workon</title>
    @vite('resources/css/welcome.css')
</head>
<body class="flex min-h-screen items-center justify-center overflow-x-hidden bg-zinc-900 px-4 py-10 text-zinc-900 antialiased">

    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -right-24 top-0 h-72 w-72 rotate-12 rounded-[40%_60%_70%_30%/40%_50%_60%_50%] bg-yellow-400/10 blur-3xl"></div>
        <div class="absolute -left-24 bottom-0 h-72 w-72 -rotate-6 rounded-[60%_40%_30%_70%/60%_30%_70%_40%] bg-yellow-400/5 blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-sm">
        <div class="mb-6 text-center">
            <span class="text-4xl font-black tracking-tighter text-yellow-400">Workon</span>
        </div>

        <div class="card-slash bg-white p-8 shadow-2xl" style="clip-path: polygon(0 0, 100% 0, 100% 92%, 96% 100%, 0 100%);">
            <div class="flex flex-col items-center text-center">
                <div class="flex size-20 items-center justify-center rounded-full bg-zinc-900 text-2xl font-black text-yellow-400">
                    RR
                </div>

                <h1 class="mt-5 text-3xl font-black tracking-tighter text-zinc-900">
                    <a href="{{ route('home') }}" class="transition hover:text-yellow-500">Ricardo Ramirez</a>
                </h1>

                <div class="mt-2 inline-flex items-center gap-2">
                    <div class="h-px w-6 bg-yellow-400"></div>
                    <span class="text-xs font-bold uppercase tracking-[0.25em] text-zinc-500">Fundador</span>
                    <div class="h-px w-6 bg-yellow-400"></div>
                </div>

                <p class="mt-1 text-sm font-medium text-zinc-400">Workon</p>
            </div>

            <div class="my-6 h-px bg-zinc-100"></div>

            <a href="tel:+17704122535" class="flex items-center gap-3 py-2 text-zinc-700 transition hover:text-zinc-900">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 00-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97a1.125 1.125 0 00.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                    </svg>
                </span>
                <span class="text-sm font-semibold">(770) 412-2535</span>
            </a>

            <div class="mt-6 grid grid-cols-2 gap-3">
                <a href="tel:+17704122535" class="flex items-center justify-center gap-2 border-2 border-zinc-900 bg-zinc-900 px-4 py-3 text-sm font-black text-yellow-400 transition hover:bg-zinc-800">
                    Llamar
                </a>
                <a href="https://wa.me/17704122535" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 border-2 border-zinc-900 bg-white px-4 py-3 text-sm font-black text-zinc-900 transition hover:bg-zinc-100">
                    WhatsApp
                </a>
            </div>

            <a href="{{ route('businesscard.vcard') }}" class="mt-3 flex w-full items-center justify-center gap-2 bg-yellow-400 px-4 py-3 text-sm font-black text-zinc-900 transition hover:bg-yellow-300">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Guardar contacto
            </a>
        </div>

        <p class="mt-6 text-center text-xs text-zinc-500">
            <a href="{{ route('home') }}" class="hover:text-yellow-400">workonpro.com</a>
        </p>
    </div>

</body>
</html>
