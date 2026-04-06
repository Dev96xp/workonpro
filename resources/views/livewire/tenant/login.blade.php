<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tenant')] class extends Component {
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirect(url('/dashboard'));
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::lower($this->email) . '|' . request()->ip();
    }
}; ?>

<div class="flex min-h-screen items-center justify-center">
    <div class="w-full max-w-sm px-6">
        <div class="mb-8 text-center">
            <flux:heading size="xl" class="text-2xl font-bold">{{ tenant('name') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Inicia sesión en tu panel</flux:text>
        </div>

        <form wire:submit="login" class="space-y-5">
            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input wire:model="email" type="email" autofocus placeholder="admin@tunegocio.com" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Contraseña</flux:label>
                <flux:input wire:model="password" type="password" />
                <flux:error name="password" />
            </flux:field>

            <div class="flex items-center justify-between">
                <flux:checkbox wire:model="remember" label="Recordarme" />
            </div>

            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>Iniciar sesión</span>
                <span wire:loading>Entrando...</span>
            </flux:button>
        </form>
    </div>
</div>
