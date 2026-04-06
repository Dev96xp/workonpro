<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workon — La plataforma para tu negocio</title>
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
                <a href="#features" class="hidden text-sm font-medium text-zinc-400 transition hover:text-yellow-400 md:block">Características</a>
                <a href="#pricing"  class="hidden text-sm font-medium text-zinc-400 transition hover:text-yellow-400 md:block">Precios</a>
                <a href="{{ url('/admin/login') }}" class="hidden text-sm font-medium text-zinc-400 transition hover:text-yellow-400 sm:block">Iniciar sesión</a>
                <a href="{{ route('register.plans') }}" class="border-b-2 border-yellow-400 bg-yellow-400 px-4 py-2 text-sm font-black text-zinc-900 transition hover:bg-transparent hover:text-yellow-400 sm:px-6">
                    Empezar →
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
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-yellow-400 sm:tracking-[0.3em]">Plataforma SaaS</span>
            </div>

            <h1 class="text-5xl font-black leading-[0.9] tracking-tighter text-white sm:text-7xl lg:text-9xl">
                <span class="block">Gestiona</span>
                <span class="block sm:pl-8 text-yellow-400">tu negocio</span>
                <span class="block sm:pl-16 text-zinc-500">con Workon</span>
            </h1>

            <p class="mt-6 max-w-xl text-base leading-relaxed text-zinc-400 sm:mt-10 sm:text-lg">
                Clientes, cupones e imágenes en un solo panel. Tu espacio, tu marca, tu subdominio.
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:mt-12 sm:flex-row sm:gap-4">
                <a href="{{ route('register.plans') }}" class="group inline-flex items-center justify-center gap-3 bg-yellow-400 px-8 py-4 text-base font-black text-zinc-900 transition hover:bg-yellow-300 sm:justify-start sm:px-10 sm:py-5">
                    <span>Comenzar ahora</span>
                    <svg class="size-5 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                <a href="#features" class="inline-flex items-center justify-center border border-zinc-700 px-8 py-4 text-base font-medium text-zinc-400 transition hover:border-yellow-400 hover:text-yellow-400 sm:px-10 sm:py-5">
                    Ver características
                </a>
            </div>

            <div class="mt-12 flex flex-wrap gap-6 sm:mt-20 sm:gap-10">
                <div class="border-l-2 border-yellow-400 pl-3 sm:pl-4">
                    <p class="text-2xl font-black text-white sm:text-3xl">100%</p>
                    <p class="text-xs uppercase tracking-widest text-zinc-500">Multi-tenant</p>
                </div>
                <div class="border-l-2 border-zinc-700 pl-3 sm:pl-4">
                    <p class="text-2xl font-black text-white sm:text-3xl">SSL</p>
                    <p class="text-xs uppercase tracking-widest text-zinc-500">Seguro</p>
                </div>
                <div class="border-l-2 border-zinc-700 pl-3 sm:pl-4">
                    <p class="text-2xl font-black text-white sm:text-3xl">Auto</p>
                    <p class="text-xs uppercase tracking-widest text-zinc-500">Compresión</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="clip-feat relative -mt-8 bg-stone-100 py-24 sm:-mt-16 sm:py-40">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mb-4 inline-flex items-center gap-3">
                <div class="h-px w-8 bg-zinc-900"></div>
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-zinc-500 sm:tracking-[0.3em]">Funcionalidades</span>
            </div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <h2 class="text-4xl font-black leading-tight tracking-tighter text-zinc-900 sm:text-5xl">
                    Todo en<br><span class="text-yellow-400">un solo panel</span>
                </h2>
                <p class="hidden max-w-xs text-sm leading-relaxed text-zinc-500 md:block">
                    Cada negocio tiene su espacio aislado con su subdominio único.
                </p>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-4 sm:mt-16 sm:grid-cols-3 sm:gap-0">
                <div class="card-slash group border-t-2 border-zinc-900 bg-zinc-900 p-8 text-white transition hover:border-yellow-400 sm:p-10">
                    <div class="mb-5 text-yellow-400 sm:mb-6">
                        <svg class="size-8 sm:size-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black sm:text-2xl">Clientes</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-400 sm:mt-3">Registra y administra tu cartera completa con historial e información de contacto.</p>
                    <div class="mt-6 h-px w-12 bg-yellow-400 transition group-hover:w-full sm:mt-8"></div>
                </div>

                <div class="card-slash group border-t-2 border-yellow-400 bg-yellow-400 p-8 transition hover:bg-yellow-300 sm:p-10">
                    <div class="mb-5 text-zinc-900 sm:mb-6">
                        <svg class="size-8 sm:size-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black text-zinc-900 sm:text-2xl">Cupones</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-800 sm:mt-3">Crea descuentos con fechas, límites de uso y porcentajes personalizados.</p>
                    <div class="mt-6 h-px w-12 bg-zinc-900 transition group-hover:w-full sm:mt-8"></div>
                </div>

                <div class="card-slash group border-t-2 border-stone-300 bg-white p-8 transition hover:border-yellow-400 sm:p-10">
                    <div class="mb-5 text-zinc-900 sm:mb-6">
                        <svg class="size-8 sm:size-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black text-zinc-900 sm:text-2xl">Imágenes</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-500 sm:mt-3">Sube las fotos de tu negocio. Compresión automática y galería lista para mostrar.</p>
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
                <span class="text-xs font-bold uppercase tracking-[0.2em] text-yellow-400 sm:tracking-[0.3em]">Precios</span>
            </div>
            <h2 class="text-4xl font-black tracking-tighter text-white sm:text-5xl">
                Planes <span class="text-yellow-400">simples</span>
            </h2>

            <div class="mt-10 grid grid-cols-1 gap-px bg-zinc-700 sm:mt-16 md:grid-cols-3">
                <div class="group flex flex-col bg-zinc-900 p-6 transition hover:bg-zinc-800 sm:p-10">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-zinc-500">Básico</p>
                    <div class="mt-4 flex items-end gap-1">
                        <span class="text-5xl font-black text-white sm:text-6xl">$29</span>
                        <span class="mb-2 text-zinc-500">/mes</span>
                    </div>
                    <div class="mt-6 h-px bg-zinc-700 transition group-hover:bg-yellow-400/50 sm:mt-8"></div>
                    <ul class="mt-6 grow space-y-3 text-sm text-zinc-400 sm:mt-8 sm:space-y-4">
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> 40 imágenes</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> Clientes ilimitados</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> Cupones de descuento</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> Subdominio propio</li>
                    </ul>
                    <a href="{{ route('register.plans') }}" class="mt-8 block border border-zinc-700 py-4 text-center text-sm font-bold text-zinc-400 transition hover:border-yellow-400 hover:text-yellow-400">
                        Empezar
                    </a>
                </div>

                <div class="group relative flex flex-col bg-yellow-400 p-6 sm:p-10">
                    <div class="absolute right-4 top-4 rotate-12 bg-zinc-900 px-3 py-1 text-xs font-black uppercase tracking-wider text-yellow-400 sm:right-6 sm:top-6">
                        Popular
                    </div>
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-zinc-700">Pro</p>
                    <div class="mt-4 flex items-end gap-1">
                        <span class="text-5xl font-black text-zinc-900 sm:text-6xl">$79</span>
                        <span class="mb-2 text-zinc-700">/mes</span>
                    </div>
                    <div class="mt-6 h-px bg-zinc-900/20 sm:mt-8"></div>
                    <ul class="mt-6 grow space-y-3 text-sm text-zinc-800 sm:mt-8 sm:space-y-4">
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> 100 imágenes</li>
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> Clientes ilimitados</li>
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> Cupones de descuento</li>
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> Subdominio propio</li>
                        <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> Soporte prioritario</li>
                    </ul>
                    <a href="{{ route('register.plans') }}" class="mt-8 block bg-zinc-900 py-4 text-center text-sm font-black text-yellow-400 transition hover:bg-zinc-800">
                        Empezar con Pro
                    </a>
                </div>

                <div class="group flex flex-col bg-zinc-900 p-6 transition hover:bg-zinc-800 sm:p-10">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-zinc-500">Enterprise</p>
                    <div class="mt-4 flex items-end gap-1">
                        <span class="text-5xl font-black text-white sm:text-6xl">$149</span>
                        <span class="mb-2 text-zinc-500">/mes</span>
                    </div>
                    <div class="mt-6 h-px bg-zinc-700 transition group-hover:bg-yellow-400/50 sm:mt-8"></div>
                    <ul class="mt-6 grow space-y-3 text-sm text-zinc-400 sm:mt-8 sm:space-y-4">
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> Imágenes ilimitadas</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> Clientes ilimitados</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> Cupones de descuento</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> Subdominio propio</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> Soporte 24/7</li>
                        <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> Integraciones avanzadas</li>
                    </ul>
                    <a href="{{ route('register.plans') }}" class="mt-8 block border border-zinc-700 py-4 text-center text-sm font-bold text-zinc-400 transition hover:border-yellow-400 hover:text-yellow-400">
                        Empezar
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
                    Listo para<br><span class="text-yellow-400">crecer?</span>
                </h2>
                <div class="flex flex-col gap-4 sm:gap-6 md:items-end">
                    <p class="max-w-sm text-zinc-500 md:text-right">
                        Configura tu panel en minutos y empieza a gestionar tu negocio desde el día uno.
                    </p>
                    <a href="{{ route('register.plans') }}" class="group inline-flex items-center gap-3 bg-zinc-900 px-8 py-4 text-base font-black text-yellow-400 transition hover:bg-zinc-800 sm:px-10 sm:py-5">
                        Crear mi cuenta
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
            <p class="text-sm text-zinc-600">© {{ date('Y') }} Workon. Todos los derechos reservados.</p>
            <a href="{{ url('/admin/login') }}" class="text-sm text-zinc-500 transition hover:text-yellow-400">Iniciar sesión</a>
        </div>
    </footer>

</body>
</html>