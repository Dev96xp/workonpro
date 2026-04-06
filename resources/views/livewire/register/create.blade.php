<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $plan = '';

    #[Validate('required|string|max:100')]
    public string $business_name = '';

    #[Validate('required|string|min:3|max:30|alpha_dash|unique:domains,domain')]
    public string $subdomain = '';

    #[Validate('required|email|unique:tenants,email')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public array $plans = [
        'basic'      => ['label' => 'Básico',     'price' => '$29/mes'],
        'pro'        => ['label' => 'Pro',         'price' => '$59/mes'],
        'enterprise' => ['label' => 'Enterprise',  'price' => '$99/mes'],
    ];

    public function mount(): void
    {
        $this->plan = request('plan', 'pro');

        if (! array_key_exists($this->plan, $this->plans)) {
            $this->plan = 'pro';
        }
    }

    public function updatedSubdomain(): void
    {
        $this->subdomain = strtolower(preg_replace('/[^a-z0-9-]/', '', strtolower($this->subdomain)));
    }

    public function checkout(): void
    {
        $this->validate();

        $priceId = config('services.stripe.prices.' . $this->plan);

        // Guardamos password en session (no en BD)
        session(['pending_password_' . $this->subdomain => $this->password]);

        // Creamos el tenant en estado "pending" para que Cashier pueda usarlo
        $tenant = \App\Models\Tenant::updateOrCreate(
            ['id' => $this->subdomain],
            [
                'name'   => $this->business_name,
                'email'  => $this->email,
                'status' => 'pending',
                'plan'   => $this->plan,
            ]
        );

        $tenant->domains()->firstOrCreate(['domain' => $this->subdomain]);

        $checkoutUrl = $tenant
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('register.success') . '?session_id={CHECKOUT_SESSION_ID}&tenant=' . $this->subdomain,
                'cancel_url'  => route('register.plans'),
            ]);

        $this->redirect($checkoutUrl->url);
    }
}; ?>

<div class="mx-auto max-w-lg">
    <div class="mb-6 text-center">
        <flux:heading size="xl">Crear tu negocio</flux:heading>
        <flux:text class="mt-1 text-zinc-500">
            Plan seleccionado:
            <strong>{{ $plans[$plan]['label'] }}</strong> — {{ $plans[$plan]['price'] }}
            <flux:link href="{{ route('register.plans') }}" wire:navigate class="ml-1 text-sm">Cambiar</flux:link>
        </flux:text>
    </div>

    <form wire:submit="checkout" class="space-y-5">
        <flux:field>
            <flux:label>Nombre del negocio</flux:label>
            <flux:input wire:model="business_name" placeholder="Mi Constructora S.A." autofocus />
            <flux:error name="business_name" />
        </flux:field>

        <flux:field>
            <flux:label>Subdominio</flux:label>
            <div class="flex items-center gap-0">
                <flux:input wire:model.live="subdomain" placeholder="miconstructora" class="rounded-r-none" />
                <span class="inline-flex h-10 items-center rounded-r-lg border border-l-0 border-zinc-300 bg-zinc-100 px-3 text-sm text-zinc-500 dark:border-zinc-600 dark:bg-zinc-700">
                    .workonpro.com
                </span>
            </div>
            <flux:error name="subdomain" />
        </flux:field>

        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input wire:model="email" type="email" placeholder="admin@minegocio.com" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>Contraseña</flux:label>
            <flux:input wire:model="password" type="password" placeholder="Mínimo 8 caracteres" />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label>Confirmar contraseña</flux:label>
            <flux:input wire:model="password_confirmation" type="password" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Continuar al pago →</span>
            <span wire:loading>Procesando...</span>
        </flux:button>
    </form>

    <p class="mt-4 text-center text-xs text-zinc-400">
        Al continuar, serás redirigido a Stripe para completar el pago de forma segura.
    </p>
</div>
