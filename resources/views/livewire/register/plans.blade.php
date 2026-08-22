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
        <flux:heading size="xl" class="text-3xl font-bold">{{ __('register.plans.meta_title') }}</flux:heading>
        <flux:text class="mt-2 text-zinc-500">{{ __('register.plans.subtitle') }}</flux:text>
    </header>

    <div class="mx-auto max-w-5xl px-6 pb-16">
        <div class="mb-10 text-center">
            <flux:heading size="xl">{{ __('register.plans.heading') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">{{ __('register.plans.tagline') }}</flux:text>
        </div>

        <div class="grid gap-8 md:grid-cols-3">
            {{-- Básico --}}
            <div class="relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <flux:badge color="green" size="sm">{{ __('register.plans.basic_badge') }}</flux:badge>
                </div>
                <flux:heading size="lg">{{ __('register.plans.basic_name') }}</flux:heading>
                <div class="mt-4 flex items-end gap-2">
                    <span class="text-4xl font-bold text-zinc-900 dark:text-white">{{ __('register.plans.basic_price') }}</span>
                    <span class="mb-1 text-sm text-zinc-400 line-through dark:text-zinc-500">{{ __('register.plans.basic_old_price') }}</span>
                </div>
                <flux:separator class="my-6" />
                <ul class="flex-1 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.basic_item_images') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.basic_item_services') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.item_unlimited_clients') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.item_coupons') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.item_subdomain') }}</li>
                </ul>
                <flux:button wire:click="selectPlan('basic')" class="mt-8 w-full">{{ __('register.plans.cta_start') }}</flux:button>
            </div>

            {{-- Pro --}}
            <div class="relative flex flex-col rounded-2xl border-2 border-blue-500 bg-white p-8 shadow-lg dark:bg-zinc-800">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <flux:badge color="blue" size="sm">{{ __('register.plans.pro_badge') }}</flux:badge>
                </div>
                <flux:heading size="lg">{{ __('register.plans.pro_name') }}</flux:heading>
                <div class="mt-4 flex items-end gap-2">
                    <span class="text-4xl font-bold text-zinc-900 dark:text-white">{{ __('register.plans.pro_price') }}</span>
                    <span class="mb-1 text-zinc-500">{{ __('register.plans.pro_per_month') }}</span>
                    <span class="mb-1 text-sm text-zinc-400 line-through dark:text-zinc-500">{{ __('register.plans.pro_old_price') }}</span>
                </div>
                <flux:separator class="my-6" />
                <ul class="flex-1 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.pro_item_images') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.item_unlimited_clients') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.item_coupons') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.item_subdomain') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> <span class="font-bold">{{ __('register.plans.item_billing') }}</span></li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> <span class="font-bold">{{ __('register.plans.item_appointments') }}</span></li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.pro_item_support') }}</li>
                </ul>
                <flux:button wire:click="selectPlan('pro')" variant="primary" class="mt-8 w-full">{{ __('register.plans.cta_start') }}</flux:button>
            </div>

            {{-- Enterprise --}}
            <div class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('register.plans.enterprise_name') }}</flux:heading>
                <div class="mt-4 flex items-end gap-2">
                    <span class="text-4xl font-bold text-zinc-900 dark:text-white">{{ __('register.plans.enterprise_price') }}</span>
                    <span class="mb-1 text-zinc-500">{{ __('register.plans.pro_per_month') }}</span>
                    <span class="mb-1 text-sm text-zinc-400 line-through dark:text-zinc-500">{{ __('register.plans.enterprise_old_price') }}</span>
                </div>
                <flux:separator class="my-6" />
                <ul class="flex-1 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.enterprise_item_images') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.item_unlimited_clients') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.item_coupons') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.item_subdomain') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> <span class="font-bold">{{ __('register.plans.item_billing') }}</span></li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> <span class="font-bold">{{ __('register.plans.item_appointments') }}</span></li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> <span class="font-bold">{{ __('register.plans.item_employees') }}</span></li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.enterprise_item_support') }}</li>
                    <li class="flex items-center gap-2"><flux:icon.check class="size-4 text-green-500" /> {{ __('register.plans.enterprise_item_integrations') }}</li>
                </ul>
                <flux:button wire:click="selectPlan('enterprise')" class="mt-8 w-full">{{ __('register.plans.cta_start') }}</flux:button>
            </div>
        </div>

        <p class="mt-8 text-center text-sm text-zinc-500">
            {{ __('register.plans.already_account') }}
            <flux:link href="{{ url('/login') }}" wire:navigate>{{ __('register.plans.login') }}</flux:link>
        </p>
    </div>
</div>
