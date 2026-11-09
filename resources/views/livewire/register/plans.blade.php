<?php

use App\Models\Plan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] class extends Component {
    public function selectPlan(string $plan): void
    {
        $this->redirect(route('register.create', ['plan' => $plan]));
    }

    public function with(): array
    {
        return [
            'plans' => Plan::query()->whereIn('slug', ['basic', 'pro', 'enterprise'])->get()->keyBy('slug'),
        ];
    }
}; ?>

<div class="min-h-screen bg-zinc-900 text-white">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24">
        <a href="{{ url('/') }}" class="text-2xl font-black tracking-tighter text-white">{{ __('register.plans.meta_title') }}</a>
        <p class="mt-1 text-sm text-zinc-500">{{ __('register.plans.subtitle') }}</p>

        <div class="mt-12 mb-4 inline-flex items-center gap-3 sm:mt-16">
            <div class="h-px w-8 bg-yellow-400"></div>
            <span class="text-xs font-bold uppercase tracking-[0.2em] text-yellow-400 sm:tracking-[0.3em]">{{ __('register.plans.tagline') }}</span>
        </div>
        <h1 class="text-4xl font-black tracking-tighter text-white sm:text-5xl">{{ __('register.plans.heading') }}</h1>

        <div class="mt-10 grid grid-cols-1 gap-px bg-zinc-700 sm:mt-16 md:grid-cols-3">
            {{-- Básico --}}
            <div class="group relative flex flex-col bg-zinc-900 p-6 transition hover:bg-zinc-800 sm:p-10">
                <p class="text-xs font-bold uppercase tracking-[0.3em] text-zinc-500">{{ __('register.plans.basic_name') }}</p>
                @include('livewire.register.partials.plan-price', ['plan' => $plans->get('basic')])
                <div class="mt-6 h-px bg-zinc-700 transition group-hover:bg-yellow-400/50 sm:mt-8"></div>
                <ul class="mt-6 grow space-y-3 text-sm text-zinc-400 sm:mt-8 sm:space-y-4">
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('register.plans.basic_item_images') }}</li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('register.plans.basic_item_services') }}</li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('register.plans.item_unlimited_clients') }}</li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('register.plans.item_coupons') }}</li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('register.plans.item_subdomain') }}</li>
                </ul>
                <button type="button" wire:click="selectPlan('basic')" class="mt-8 block w-full cursor-pointer border border-zinc-700 py-4 text-center text-sm font-bold text-zinc-400 transition hover:border-yellow-400 hover:text-yellow-400">
                    {{ __('register.plans.cta_start') }}
                </button>
            </div>

            {{-- Pro --}}
            <div class="group relative flex flex-col bg-yellow-400 p-6 sm:p-10">
                <div class="absolute right-4 top-4 rotate-12 bg-zinc-900 px-3 py-1 text-xs font-black uppercase tracking-wider text-yellow-400 sm:right-6 sm:top-6">
                    {{ __('register.plans.pro_badge') }}
                </div>
                <p class="text-xs font-bold uppercase tracking-[0.3em] text-zinc-700">{{ __('register.plans.pro_name') }}</p>
                @include('livewire.register.partials.plan-price', ['plan' => $plans->get('pro'), 'featured' => true])
                <div class="mt-6 h-px bg-zinc-900/20 sm:mt-8"></div>
                <ul class="mt-6 grow space-y-3 text-sm text-zinc-800 sm:mt-8 sm:space-y-4">
                    <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> {{ __('register.plans.pro_item_images') }}</li>
                    <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> {{ __('register.plans.item_unlimited_clients') }}</li>
                    <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> {{ __('register.plans.item_coupons') }}</li>
                    <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> {{ __('register.plans.item_subdomain') }}</li>
                    <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> <span class="font-bold">{{ __('register.plans.item_billing') }}</span></li>
                    <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> <span class="font-bold">{{ __('register.plans.item_appointments') }}</span></li>
                    <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> <span class="font-bold">{{ __('register.plans.item_businesscard') }}</span></li>
                    <li class="flex items-center gap-3"><span class="font-black text-zinc-900">—</span> {{ __('register.plans.pro_item_support') }}</li>
                </ul>
                <button type="button" wire:click="selectPlan('pro')" class="mt-8 block w-full cursor-pointer bg-zinc-900 py-4 text-center text-sm font-black text-yellow-400 transition hover:bg-zinc-800">
                    {{ __('register.plans.cta_start_pro') }}
                </button>
            </div>

            {{-- Enterprise --}}
            <div class="group flex flex-col bg-zinc-900 p-6 transition hover:bg-zinc-800 sm:p-10">
                <p class="text-xs font-bold uppercase tracking-[0.3em] text-zinc-500">{{ __('register.plans.enterprise_name') }}</p>
                @include('livewire.register.partials.plan-price', ['plan' => $plans->get('enterprise')])
                <div class="mt-6 h-px bg-zinc-700 transition group-hover:bg-yellow-400/50 sm:mt-8"></div>
                <ul class="mt-6 grow space-y-3 text-sm text-zinc-400 sm:mt-8 sm:space-y-4">
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('register.plans.enterprise_item_images') }}</li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('register.plans.item_unlimited_clients') }}</li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('register.plans.item_coupons') }}</li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('register.plans.item_subdomain') }}</li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> <span class="font-bold">{{ __('register.plans.item_billing') }}</span></li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> <span class="font-bold">{{ __('register.plans.item_appointments') }}</span></li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> <span class="font-bold">{{ __('register.plans.item_employees') }}</span></li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> <span class="font-bold">{{ __('register.plans.item_businesscard') }}</span></li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('register.plans.enterprise_item_support') }}</li>
                    <li class="flex items-center gap-3"><span class="font-bold text-yellow-400">—</span> {{ __('register.plans.enterprise_item_integrations') }}</li>
                </ul>
                <button type="button" wire:click="selectPlan('enterprise')" class="mt-8 block w-full cursor-pointer border border-zinc-700 py-4 text-center text-sm font-bold text-zinc-400 transition hover:border-yellow-400 hover:text-yellow-400">
                    {{ __('register.plans.cta_start') }}
                </button>
            </div>
        </div>

        <p class="mt-10 text-center text-sm text-zinc-500">
            {{ __('register.plans.already_account') }}
            <a href="{{ url('/login') }}" wire:navigate class="font-bold text-yellow-400 transition hover:text-yellow-300">{{ __('register.plans.login') }}</a>
        </p>
    </div>
</div>
