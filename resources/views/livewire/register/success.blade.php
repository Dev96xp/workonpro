<?php

use Laravel\Cashier\Cashier;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] class extends Component {
    public string $businessName = '';
    public string $subdomain = '';
    public string $loginUrl = '';

    public function mount(): void
    {
        $sessionId = request('session_id');
        $tenantId = request('tenant');

        if (! $sessionId || ! $tenantId) {
            $this->redirect(route('register.plans'));

            return;
        }

        $stripeSession = Cashier::stripe()->checkout->sessions->retrieve($sessionId);

        if ($stripeSession->payment_status !== 'paid') {
            $this->redirect(route('register.plans'));

            return;
        }

        $tenant = \App\Models\Tenant::find($tenantId);

        if (! $tenant) {
            $this->redirect(route('register.plans'));

            return;
        }

        // Activar tenant
        $tenant->update(['status' => 'active']);

        // Crear usuario admin dentro del tenant
        $password = session('pending_password_' . $tenantId);
        session()->forget('pending_password_' . $tenantId);

        tenancy()->initialize($tenant);

        // Migrar si la BD del tenant no tiene tablas aún
        if (! \Illuminate\Support\Facades\Schema::hasTable('users')) {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        }

        \App\Models\User::firstOrCreate(
            ['email' => $tenant->email],
            [
                'name'     => $tenant->name,
                'email'    => $tenant->email,
                'password' => $password ?? 'changeme123',
            ]
        );

        tenancy()->end();

        $centralDomain = config('tenancy.central_domains')[0];
        $this->businessName = $tenant->name;
        $this->subdomain = $tenantId;
        $scheme = request()->getScheme();
        $this->loginUrl = $scheme . '://' . $tenantId . '.' . $centralDomain;
    }
}; ?>

<div class="flex min-h-screen items-center justify-center bg-zinc-50 dark:bg-zinc-900">
    <div class="mx-auto max-w-md px-6 text-center">
        <div class="mb-6 flex justify-center">
            <div class="flex size-20 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                <flux:icon.check class="size-10 text-green-600 dark:text-green-400" />
            </div>
        </div>

        <flux:heading size="xl">¡Bienvenido a Workonpro!</flux:heading>
        <flux:text class="mt-3 text-zinc-500">
            Tu negocio <strong>{{ $businessName }}</strong> está listo.
        </flux:text>

        <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-5 text-left dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm text-zinc-500">Tu panel de administración:</flux:text>
            <p class="mt-1 font-mono text-blue-600 dark:text-blue-400">{{ $loginUrl }}</p>
        </div>

        <a href="{{ $loginUrl }}" class="mt-6 block w-full rounded-lg bg-blue-600 px-4 py-3 text-center font-medium text-white hover:bg-blue-700">
            Ir a mi panel →
        </a>
    </div>
</div>
