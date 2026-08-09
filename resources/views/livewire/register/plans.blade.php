<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] class extends Component {
    public function selectPlan(string $plan): void
    {
        $this->redirect(route('register.create', ['plan' => $plan]));
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <header class="py-8 text-center">
        <flux:heading size="xl" class="text-3xl font-bold">Workonpro</flux:heading>
        <flux:text class="mt-2 text-zinc-500">La plataforma para negocios de construcción</flux:text>
    </header>

    <div class="mx-auto max-w-5xl px-6 pb-16">
        <div class="mb-10 text-center">
            <flux:heading size="xl">Elige tu plan</flux:heading>
            <flux:text class="mt-2 text-zinc-500">Comienza hoy. Cancela cuando quieras.</flux:text>
        </div>

        <div class="grid gap-8 md:grid-cols-3">
            {{-- Básico --}}
            <div class="relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <flux:badge color="green" size="sm">¡Solo por este mes!</flux:badge>
                </div>
                <flux:heading size="lg">Básico</flux:heading>
                <div class="mt-4 flex items-end gap-2">
                    <span class="text-4xl font-bold text-zinc-900 dark:text-white">Gratis</span>
                    <span class="mb-1 text-sm text-zinc-400 line-through dark:text-zinc-500">$29.00/mes</span>
                </div>
                <flux:separator class="my-6" />
                <ul class="flex-1 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> 20 imágenes</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> 3 servicios</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Clientes ilimitados</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Cupones de descuento</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Subdominio propio</li>
                </ul>
                <flux:button wire:click="selectPlan('basic')" class="mt-8 w-full">Comenzar</flux:button>
            </div>

            {{-- Pro --}}
            <div class="relative flex flex-col rounded-2xl border-2 border-blue-500 bg-white p-8 shadow-lg dark:bg-zinc-800">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <flux:badge color="blue" size="sm">Más popular</flux:badge>
                </div>
                <flux:heading size="lg">Pro</flux:heading>
                <div class="mt-4 flex items-end gap-2">
                    <span class="text-4xl font-bold text-zinc-900 dark:text-white">$39</span>
                    <span class="mb-1 text-zinc-500">/mes</span>
                    <span class="mb-1 text-sm text-zinc-400 line-through dark:text-zinc-500">$79.00</span>
                </div>
                <flux:separator class="my-6" />
                <ul class="flex-1 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> 100 imágenes</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Clientes ilimitados</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Cupones de descuento</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Subdominio propio</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> <span class="font-bold">Sistema de Facturación</span></li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Soporte prioritario</li>
                </ul>
                <flux:button wire:click="selectPlan('pro')" variant="primary" class="mt-8 w-full">Comenzar</flux:button>
            </div>

            {{-- Enterprise --}}
            <div class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">Enterprise</flux:heading>
                <div class="mt-4 flex items-end gap-2">
                    <span class="text-4xl font-bold text-zinc-900 dark:text-white">$79</span>
                    <span class="mb-1 text-zinc-500">/mes</span>
                    <span class="mb-1 text-sm text-zinc-400 line-through dark:text-zinc-500">$129.00</span>
                </div>
                <flux:separator class="my-6" />
                <ul class="flex-1 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Imágenes ilimitadas</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Clientes ilimitados</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Cupones de descuento</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Subdominio propio</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> <span class="font-bold">Sistema de Facturación</span></li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Soporte 24/7</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> Integraciones avanzadas</li>
                </ul>
                <flux:button wire:click="selectPlan('enterprise')" class="mt-8 w-full">Comenzar</flux:button>
            </div>
        </div>

        <p class="mt-8 text-center text-sm text-zinc-500">
            ¿Ya tienes una cuenta?
            <flux:link href="{{ url('/login') }}" wire:navigate>Inicia sesión</flux:link>
        </p>
    </div>
</div>
