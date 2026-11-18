<?php

use App\Models\Plan;
use App\Services\TenantPlanChanger;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tenant')] class extends Component {
    /**
     * Benefits shown under each plan, the same ones listed on the signup page.
     *
     * @var array<string, list<string>>
     */
    private const FEATURES = [
        'basic' => ['basic_item_images', 'basic_item_services', 'item_unlimited_clients', 'item_coupons', 'item_subdomain'],
        'pro' => ['pro_item_images', 'item_unlimited_clients', 'item_coupons', 'item_subdomain', 'item_billing', 'item_appointments', 'item_businesscard', 'pro_item_support'],
        'enterprise' => ['enterprise_item_images', 'item_unlimited_clients', 'item_coupons', 'item_subdomain', 'item_billing', 'item_appointments', 'item_employees', 'item_businesscard', 'enterprise_item_support', 'enterprise_item_integrations'],
    ];

    #[Url(as: 'session_id', except: null)]
    public ?string $checkoutSessionId = null;

    public ?string $confirmingPlan = null;

    public bool $showConfirm = false;

    public bool $hasSubscription = false;

    public string $message = '';

    public string $errorMessage = '';

    public function mount(TenantPlanChanger $changer): void
    {
        if ($this->checkoutSessionId) {
            try {
                $slug = $changer->confirmCheckout(tenant(), $this->checkoutSessionId);
                $this->message = $slug ? __('billing.changed', ['plan' => Plan::firstWhere('slug', $slug)?->name]) : '';
            } catch (\Throwable $exception) {
                report($exception);
                $this->errorMessage = __('billing.error');
            }

            $this->checkoutSessionId = null;
        }

        try {
            $this->hasSubscription = $changer->hasSubscription(tenant());
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function confirm(string $slug): void
    {
        $this->confirmingPlan = $this->switchablePlans()->firstWhere('slug', $slug)?->slug;
        $this->showConfirm = $this->confirmingPlan !== null;
    }

    public function changePlan(TenantPlanChanger $changer): void
    {
        $plan = $this->switchablePlans()->firstWhere('slug', $this->confirmingPlan);
        $this->showConfirm = false;
        $this->message = '';
        $this->errorMessage = '';

        if (! $plan) {
            $this->errorMessage = __('billing.unavailable');

            return;
        }

        try {
            if ($this->hasSubscription) {
                $changer->swap(tenant(), $plan);
                $this->message = __('billing.changed', ['plan' => $plan->name]);

                return;
            }

            $this->redirect($changer->checkoutUrl(tenant(), $plan, url('/plan')));
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('billing.error');
        }
    }

    public function manageBilling(TenantPlanChanger $changer): void
    {
        try {
            $url = $changer->billingPortalUrl(tenant(), url('/plan'));
        } catch (\Throwable $exception) {
            report($exception);
            $url = null;
        }

        if ($url) {
            $this->redirect($url);

            return;
        }

        $this->errorMessage = __('billing.error');
    }

    /**
     * Active paid plans the tenant can move to (not the one they already have).
     *
     * @return Collection<int, Plan>
     */
    private function switchablePlans(): Collection
    {
        return $this->plans()
            ->reject(fn (Plan $plan) => $plan->slug === tenant('plan') || $plan->isFree() || ! $plan->checkoutPriceId())
            ->values();
    }

    /**
     * @return Collection<int, Plan>
     */
    private function plans(): Collection
    {
        return Plan::query()->where('is_active', true)->orderBy('sort_order')->orderBy('price')->get();
    }

    /**
     * @return array{plans: Collection<int, Plan>, switchable: list<string>, currentPlan: ?Plan, confirming: ?Plan, features: array<string, list<string>>}
     */
    public function with(): array
    {
        $plans = $this->plans();

        return [
            'plans' => $plans,
            'switchable' => $this->switchablePlans()->pluck('slug')->all(),
            'currentPlan' => Plan::firstWhere('slug', tenant('plan')),
            'confirming' => $plans->firstWhere('slug', $this->confirmingPlan),
            'features' => self::FEATURES,
        ];
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="sm:p-6">
                <flux:heading size="xl">{{ __('billing.heading') }}</flux:heading>
                <flux:text class="text-zinc-500">{{ __('billing.subheading') }}</flux:text>

                @if ($message)
                    <div data-plan-changed class="mt-6 rounded-lg bg-green-50 px-4 py-3 text-sm font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">{{ $message }}</div>
                @endif
                @if ($errorMessage)
                    <div class="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ $errorMessage }}</div>
                @endif

                <div class="mt-6 flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:icon.credit-card class="size-8 text-yellow-500" />
                    <div class="min-w-0 flex-1">
                        <flux:text class="text-sm">{{ __('billing.current_plan') }}</flux:text>
                        <p class="text-xl font-bold text-zinc-900 dark:text-white" data-current-plan>{{ $currentPlan?->name ?? ucfirst((string) tenant('plan')) }}</p>
                    </div>
                    @if ($currentPlan)
                        <p class="text-lg font-semibold text-zinc-700 dark:text-zinc-200">{{ $currentPlan->displayPrice() }}@unless ($currentPlan->isFree())<span class="text-sm font-normal text-zinc-500">{{ __('billing.per_month') }}</span>@endunless</p>
                    @endif
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach ($plans as $plan)
                        @php($isCurrent = $plan->slug === tenant('plan'))
                        <div wire:key="plan-{{ $plan->slug }}" data-plan="{{ $plan->slug }}" @class([
                            'flex flex-col rounded-xl border bg-white p-6 dark:bg-zinc-800',
                            'border-yellow-400 ring-2 ring-yellow-400' => $isCurrent,
                            'border-zinc-200 dark:border-zinc-700' => ! $isCurrent,
                        ])>
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-bold uppercase tracking-widest text-zinc-500">{{ $plan->name }}</p>
                                @if ($plan->slug === 'pro' && ! $isCurrent)
                                    <flux:badge size="sm" color="yellow">{{ __('billing.recommended') }}</flux:badge>
                                @endif
                            </div>
                            <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white">
                                {{ $plan->displayPrice() }}@unless ($plan->isFree())<span class="text-base font-normal text-zinc-500">{{ __('billing.per_month') }}</span>@endunless
                            </p>

                            <ul class="mt-5 grow space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                                @foreach ($features[$plan->slug] ?? [] as $feature)
                                    <li class="flex items-start gap-2"><flux:icon.check variant="micro" class="mt-0.5 shrink-0 text-yellow-500" /> {{ __("register.plans.{$feature}") }}</li>
                                @endforeach
                            </ul>

                            <div class="mt-6">
                                @if ($isCurrent)
                                    <flux:button class="w-full" disabled>{{ __('billing.current') }}</flux:button>
                                @elseif (in_array($plan->slug, $switchable, true))
                                    <flux:button class="w-full" :variant="$plan->slug === 'pro' ? 'primary' : 'filled'" wire:click="confirm('{{ $plan->slug }}')">{{ __('billing.choose', ['plan' => $plan->name]) }}</flux:button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (tenant('stripe_id'))
                    <div class="mt-6 flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-5 sm:flex-row sm:items-center dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ __('billing.manage_billing') }}</p>
                            <flux:text class="text-sm">{{ __('billing.manage_billing_hint') }}</flux:text>
                        </div>
                        <flux:button wire:click="manageBilling" icon-trailing="arrow-top-right-on-square">{{ __('billing.manage_billing') }}</flux:button>
                    </div>
                @endif
            </div>
        </flux:main>
    </div>

    <flux:modal wire:model="showConfirm" class="w-full max-w-md">
        @if ($confirming)
            <div class="space-y-5">
                <flux:heading size="lg">{{ __('billing.confirm_title', ['plan' => $confirming->name]) }}</flux:heading>
                <flux:text>
                    {{ $hasSubscription
                        ? __('billing.confirm_body', ['plan' => $confirming->name, 'price' => $confirming->displayPrice()])
                        : __('billing.confirm_checkout', ['plan' => $confirming->name, 'price' => $confirming->displayPrice()]) }}
                </flux:text>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('billing.cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" wire:click="changePlan" wire:loading.attr="disabled">{{ __('billing.confirm_button') }}</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
