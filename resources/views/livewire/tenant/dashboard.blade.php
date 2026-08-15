<?php

use App\Models\BusinessImage;
use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tenant')] class extends Component {
    public function with(): array
    {
        $profile = BusinessProfile::first();
        $hasInvoicing = Tenant::hasFeature(tenant('plan'), 'invoices');
        $invoices = $hasInvoicing ? Invoice::with('items', 'payments')->get() : collect();

        return [
            'businessName'   => $profile?->business_name ?? tenant('name'),
            'clientCount'    => Client::count(),
            'activeCoupons'  => Coupon::where('is_active', true)->count(),
            'imageCount'     => BusinessImage::count(),
            'plan'           => tenant('plan') ?? 'basic',
            'images'         => BusinessImage::latest()->take(12)->get(),
            'hasInvoicing'   => $hasInvoicing,
            'totalInvoiced'  => $invoices->sum(fn (Invoice $invoice) => $invoice->totalAmount()),
            'totalPending'   => $invoices->sum(fn (Invoice $invoice) => $invoice->totalAmount() - $invoice->paidAmount()),
        ];
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/'));
    }
}; ?>

<div class="min-h-screen bg-stone-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="space-y-8 p-6">

                {{-- Hero header --}}
                <div class="relative overflow-hidden rounded-2xl bg-zinc-900 p-6 shadow-xl sm:p-8 dark:bg-zinc-800">
                    {{-- Decorative yellow accent --}}
                    <div class="absolute -right-10 -top-10 size-48 rounded-full bg-yellow-400/20 blur-3xl"></div>
                    <div class="absolute -bottom-6 left-1/3 size-32 rounded-full bg-yellow-400/10 blur-2xl"></div>

                    <div class="relative">
                        <div class="flex items-center gap-2">
                            <span class="h-1 w-8 rounded-full bg-yellow-400"></span>
                            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400">{{ __('tenant.dashboard.panel_label') }}</p>
                        </div>
                        <h1 class="mt-3 text-3xl font-bold text-white sm:text-4xl">{{ $businessName }}</h1>
                        <p class="mt-2 text-zinc-400">{{ __('tenant.dashboard.welcome', ['name' => auth()->user()->name]) }}</p>
                        <div class="mt-5 inline-flex items-center gap-2 rounded-full border border-yellow-400/30 bg-yellow-400/10 px-4 py-1.5 text-sm font-semibold capitalize text-yellow-400">
                            <span class="size-2 rounded-full bg-yellow-400"></span>
                            {{ __('tenant.dashboard.plan') }} {{ $plan }}
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="grid gap-4 sm:grid-cols-3">
                    <a href="{{ url('/clients') }}" wire:navigate
                       class="group rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-yellow-400 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center justify-between">
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('tenant.nav.clients') }}</flux:text>
                            <div class="rounded-lg bg-zinc-900 p-2 text-yellow-400 transition group-hover:bg-yellow-400 group-hover:text-zinc-900 dark:bg-zinc-700">
                                <flux:icon.users class="size-5" />
                            </div>
                        </div>
                        <p class="mt-3 text-3xl font-bold text-zinc-900 dark:text-white">{{ $clientCount }}</p>
                        <p class="mt-1 text-xs text-zinc-400">{{ __('tenant.dashboard.total_registered') }}</p>
                    </a>

                    <a href="{{ url('/coupons') }}" wire:navigate
                       class="group rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-yellow-400 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center justify-between">
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('tenant.dashboard.active_coupons') }}</flux:text>
                            <div class="rounded-lg bg-zinc-900 p-2 text-yellow-400 transition group-hover:bg-yellow-400 group-hover:text-zinc-900 dark:bg-zinc-700">
                                <flux:icon.tag class="size-5" />
                            </div>
                        </div>
                        <p class="mt-3 text-3xl font-bold text-zinc-900 dark:text-white">{{ $activeCoupons }}</p>
                        <p class="mt-1 text-xs text-zinc-400">{{ __('tenant.dashboard.in_circulation') }}</p>
                    </a>

                    <a href="{{ url('/images') }}" wire:navigate
                       class="group rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-yellow-400 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center justify-between">
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('tenant.nav.images') }}</flux:text>
                            <div class="rounded-lg bg-zinc-900 p-2 text-yellow-400 transition group-hover:bg-yellow-400 group-hover:text-zinc-900 dark:bg-zinc-700">
                                <flux:icon.photo class="size-5" />
                            </div>
                        </div>
                        <p class="mt-3 text-3xl font-bold text-zinc-900 dark:text-white">{{ $imageCount }}</p>
                        <p class="mt-1 text-xs text-zinc-400">{{ __('tenant.dashboard.uploaded') }}</p>
                    </a>
                </div>

                {{-- Facturación --}}
                @if ($hasInvoicing)
                    <div class="grid gap-4 sm:grid-cols-2">
                        <a href="{{ url('/invoices') }}" wire:navigate
                           class="group rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-yellow-400 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex items-center justify-between">
                                <flux:text class="text-sm font-medium text-zinc-500">{{ __('tenant.dashboard.total_invoiced') }}</flux:text>
                                <div class="rounded-lg bg-zinc-900 p-2 text-yellow-400 transition group-hover:bg-yellow-400 group-hover:text-zinc-900 dark:bg-zinc-700">
                                    <flux:icon.banknotes class="size-5" />
                                </div>
                            </div>
                            <p class="mt-3 text-3xl font-bold text-zinc-900 dark:text-white">${{ number_format($totalInvoiced, 2) }}</p>
                        </a>

                        <a href="{{ url('/invoices') }}" wire:navigate
                           class="group rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-yellow-400 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex items-center justify-between">
                                <flux:text class="text-sm font-medium text-zinc-500">{{ __('tenant.dashboard.total_pending') }}</flux:text>
                                <div class="rounded-lg bg-zinc-900 p-2 text-yellow-400 transition group-hover:bg-yellow-400 group-hover:text-zinc-900 dark:bg-zinc-700">
                                    <flux:icon.clock class="size-5" />
                                </div>
                            </div>
                            <p class="mt-3 text-3xl font-bold text-zinc-900 dark:text-white">${{ number_format($totalPending, 2) }}</p>
                        </a>
                    </div>
                @endif

                {{-- Image gallery --}}
                @if ($images->isNotEmpty())
                    <div class="rounded-xl border border-stone-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <flux:heading size="lg">{{ __('tenant.dashboard.gallery') }}</flux:heading>
                                <flux:text class="mt-1 text-sm text-zinc-500">{{ __('tenant.dashboard.gallery_sub') }}</flux:text>
                            </div>
                            <a href="{{ url('/images') }}" wire:navigate
                               class="text-sm font-semibold text-yellow-600 hover:text-yellow-700 dark:text-yellow-400">
                                {{ __('tenant.dashboard.view_all') }} →
                            </a>
                        </div>

                        <div class="mt-5 grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6">
                            @foreach ($images as $image)
                                <div class="group relative aspect-square overflow-hidden rounded-xl">
                                    <img
                                        src="{{ $image->url() }}"
                                        alt="{{ $image->original_name }}"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-110"
                                        loading="lazy"
                                    />
                                    <div class="absolute inset-0 rounded-xl bg-black/0 transition group-hover:bg-black/20"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-stone-300 bg-white p-10 text-center dark:border-zinc-600 dark:bg-zinc-800">
                        <flux:icon.photo class="mx-auto size-12 text-zinc-300" />
                        <flux:heading size="sm" class="mt-3 text-zinc-500">{{ __('tenant.dashboard.no_images_heading') }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-400">{{ __('tenant.dashboard.no_images_text') }}</flux:text>
                        <a href="{{ url('/images') }}" wire:navigate
                           class="mt-4 inline-block rounded-lg bg-yellow-400 px-4 py-2 text-sm font-semibold text-zinc-900 hover:bg-yellow-500">
                            {{ __('tenant.images.upload_heading') }}
                        </a>
                    </div>
                @endif

            </div>
        </flux:main>
    </div>
</div>
