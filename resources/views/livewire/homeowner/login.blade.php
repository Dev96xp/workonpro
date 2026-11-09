<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.homeowner')] #[Title('Workon')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (auth('homeowner')->check()) {
            $this->redirectRoute('homeowner.dashboard');
        }
    }

    public function login(): void
    {
        $this->validate();

        $throttleKey = Str::transliterate('homeowner|'.Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout(request()));
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ]);
        }

        if (! Auth::guard('homeowner')->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($throttleKey);
        Session::regenerate();

        $this->redirectIntended(default: route('homeowner.dashboard'));
    }
}; ?>

<div class="mx-auto max-w-sm">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-black tracking-tight">{{ __('homeowner.login.heading') }}</h1>
        <p class="mt-2 text-zinc-400">{{ __('homeowner.login.subheading') }}</p>
    </div>

    <form wire:submit="login" class="space-y-5">
        <flux:input wire:model="email" type="email" :label="__('homeowner.fields.email')" autofocus autocomplete="email" />
        <flux:input wire:model="password" type="password" :label="__('homeowner.fields.password')" autocomplete="current-password" viewable />
        <flux:checkbox wire:model="remember" :label="__('homeowner.login.remember')" />

        <button type="submit" class="w-full rounded-full bg-yellow-400 py-4 font-extrabold text-zinc-950 transition hover:bg-yellow-300">
            {{ __('homeowner.login.submit') }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-400">
        {{ __('homeowner.login.no_account') }}
        <a href="{{ route('homeowner.register') }}" class="font-semibold text-yellow-400 hover:underline">{{ __('homeowner.login.create_account') }}</a>
    </p>
</div>
