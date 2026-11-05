<?php

use App\Services\IpGeolocationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $plan = '';

    #[Validate('required|in:business,personal')]
    public string $account_type = 'business';

    #[Validate('required|string|max:100')]
    public string $business_name = '';

    #[Validate('required|string|min:3|max:30|alpha_dash|unique:domains,domain')]
    public string $subdomain = '';

    #[Validate('required|string|max:100')]
    public string $city = '';

    #[Validate('required|string|max:100')]
    public string $state = '';

    #[Validate('required|string|max:100')]
    public string $country = '';

    #[Validate('nullable|string|max:255')]
    public string $street_address = '';

    #[Validate('required|email|unique:tenants,email')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    #[Validate('accepted', message: 'register.create.accept_terms_required')]
    public bool $accepted_terms = false;

    public array $plans = [];

    public function mount(): void
    {
        $this->plans = [
            'basic'      => ['label' => __('register.create.plan_label_basic'),      'price' => __('register.create.plan_price_basic')],
            'pro'        => ['label' => __('register.create.plan_label_pro'),        'price' => __('register.create.plan_price_pro')],
            'enterprise' => ['label' => __('register.create.plan_label_enterprise'), 'price' => __('register.create.plan_price_enterprise')],
        ];

        $this->plan = request('plan', 'pro');

        if (! array_key_exists($this->plan, $this->plans)) {
            $this->plan = 'pro';
        }

        $detected = app(IpGeolocationService::class)->location(IpGeolocationService::clientIp());
        $this->city = $detected['city'] ?? '';
        $this->state = $detected['state'] ?? '';
        $this->country = $this->countryName($detected['country'] ?? null);
    }

    public function updatedSubdomain(): void
    {
        $this->subdomain = strtolower(preg_replace('/[^a-z0-9-]/', '', strtolower($this->subdomain)));
    }

    public function checkout(): void
    {
        $this->validate();

        $signupCity = app(IpGeolocationService::class)->city(IpGeolocationService::clientIp());

        if ($this->plan === 'basic') {
            $this->registerFreeTenant($signupCity);

            return;
        }

        $priceId = config('services.stripe.prices.' . $this->plan);

        // Guardamos password en session (no en BD)
        session(['pending_password_' . $this->subdomain => $this->password]);

        // Creamos el tenant en estado "pending" para que Cashier pueda usarlo.
        // Sin disparar eventos: la base de datos del tenant NO debe aprovisionarse
        // hasta que el pago se confirme en register.success.
        $tenant = \App\Models\Tenant::withoutEvents(fn () => \App\Models\Tenant::updateOrCreate(
            ['id' => $this->subdomain],
            [
                'name'        => $this->business_name,
                'email'       => $this->email,
                'status'      => 'pending',
                'plan'        => $this->plan,
                'account_type' => $this->account_type,
                'signup_city' => $signupCity,
                ...$this->locationAttributes(),
            ]
        ));

        $tenant->domains()->firstOrCreate(['domain' => $this->subdomain]);

        $checkoutUrl = $tenant
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('register.success') . '?session_id={CHECKOUT_SESSION_ID}&tenant=' . $this->subdomain,
                'cancel_url'  => route('register.plans'),
            ]);

        $this->redirect($checkoutUrl->url);
    }

    /**
     * @return array{street_address: ?string, city: string, state: string, country: string}
     */
    protected function locationAttributes(): array
    {
        return [
            'street_address' => $this->street_address ?: null,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
        ];
    }

    /**
     * ipinfo returns ISO codes; show the common ones by name so people only confirm them.
     */
    protected function countryName(?string $code): string
    {
        return match ($code) {
            null => '',
            'US' => 'United States',
            'MX' => 'México',
            'CA' => 'Canada',
            'GT' => 'Guatemala',
            'SV' => 'El Salvador',
            'HN' => 'Honduras',
            'CO' => 'Colombia',
            'ES' => 'España',
            default => $code,
        };
    }

    protected function registerFreeTenant(?string $signupCity): void
    {
        // Plan Básico: sin costo, no pasa por Stripe. Se activa de inmediato.
        $tenant = \App\Models\Tenant::updateOrCreate(
            ['id' => $this->subdomain],
            [
                'name'        => $this->business_name,
                'email'       => $this->email,
                'status'      => 'active',
                'plan'        => 'basic',
                'account_type' => $this->account_type,
                'signup_city' => $signupCity,
                ...$this->locationAttributes(),
            ]
        );

        $tenant->domains()->firstOrCreate(['domain' => $this->subdomain]);

        tenancy()->initialize($tenant);

        \App\Models\User::firstOrCreate(
            ['email' => $tenant->email],
            [
                'name'     => $tenant->name,
                'email'    => $tenant->email,
                'password' => $this->password,
            ]
        );

        tenancy()->end();

        $this->redirect(route('register.success', ['tenant' => $this->subdomain]));
    }
}; ?>

<div class="mx-auto max-w-lg">
    <div class="mb-6 text-center">
        <flux:heading size="xl">{{ __('register.create.heading') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500">
            {{ __('register.create.selected_plan') }}
            <strong>{{ $plans[$plan]['label'] }}</strong> — {{ $plans[$plan]['price'] }}
            <flux:link href="{{ route('register.plans') }}" wire:navigate class="ml-1 text-sm">{{ __('register.create.change') }}</flux:link>
        </flux:text>
    </div>

    <form wire:submit="checkout" class="space-y-5">
        @include('partials.account-type-picker')

        <flux:field>
            <flux:label>{{ $account_type === 'personal' ? __('register.create.personal_name_label') : __('register.create.business_name_label') }}</flux:label>
            <flux:input wire:model="business_name" placeholder="{{ $account_type === 'personal' ? __('register.create.personal_name_placeholder') : __('register.create.business_name_placeholder') }}" autofocus />
            <flux:error name="business_name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('register.create.subdomain_label') }}</flux:label>
            <div class="flex items-center gap-0">
                <flux:input wire:model.live="subdomain" placeholder="{{ __('register.create.subdomain_placeholder') }}" class="rounded-r-none" />
                <span class="inline-flex h-10 items-center rounded-r-lg border border-l-0 border-zinc-300 bg-zinc-100 px-3 text-sm text-zinc-500 dark:border-zinc-600 dark:bg-zinc-700">
                    .workonpro.com
                </span>
            </div>
            <flux:error name="subdomain" />
        </flux:field>

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:field>
                <flux:label>{{ __('register.create.city_label') }}</flux:label>
                <flux:input wire:model="city" placeholder="Omaha" />
                <flux:error name="city" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('register.create.state_label') }}</flux:label>
                <flux:input wire:model="state" placeholder="Nebraska" />
                <flux:error name="state" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('register.create.country_label') }}</flux:label>
                <flux:input wire:model="country" placeholder="United States" />
                <flux:error name="country" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>{{ __('register.create.street_label') }}</flux:label>
            <flux:input wire:model="street_address" placeholder="{{ __('register.create.street_placeholder') }}" />
            <flux:description>{{ $account_type === 'personal' ? __('register.create.street_hint_personal') : __('register.create.street_hint') }}</flux:description>
            <flux:error name="street_address" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('register.create.email_label') }}</flux:label>
            <flux:input wire:model="email" type="email" placeholder="{{ __('register.create.email_placeholder') }}" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('register.create.password_label') }}</flux:label>
            <flux:input wire:model="password" type="password" placeholder="{{ __('register.create.password_placeholder') }}" />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('register.create.password_confirmation_label') }}</flux:label>
            <flux:input wire:model="password_confirmation" type="password" />
        </flux:field>

        <flux:field>
            <div class="flex items-start gap-3">
                <flux:checkbox wire:model="accepted_terms" />
                <span class="text-sm text-zinc-600 dark:text-zinc-400">
                    {!! __('register.create.accept_terms', [
                        'terms_link' => '<a href="' . route('terms') . '" target="_blank" class="text-yellow-500 underline hover:no-underline">' . __('register.create.terms_link_text') . '</a>',
                        'privacy_link' => '<a href="' . route('privacy') . '" target="_blank" class="text-yellow-500 underline hover:no-underline">' . __('register.create.privacy_link_text') . '</a>',
                    ]) !!}
                </span>
            </div>
            <flux:error name="accepted_terms" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>{{ $plan === 'basic' ? __('register.create.submit_free') : __('register.create.submit_paid') }}</span>
            <span wire:loading>{{ __('register.create.processing') }}</span>
        </flux:button>
    </form>

    <p class="mt-4 text-center text-xs text-zinc-400">
        @if ($plan === 'basic')
            {{ __('register.create.note_free') }}
        @else
            {{ __('register.create.note_paid') }}
        @endif
    </p>
</div>
