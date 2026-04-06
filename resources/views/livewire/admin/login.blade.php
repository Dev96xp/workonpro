<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component
{
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Workonpro Admin" description="Acceso exclusivo para administradores" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    @if ($errors->any())
        <div class="text-sm text-red-600 dark:text-red-400 text-center">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('admin.login.store') }}" method="POST" class="flex flex-col gap-6">
        @csrf
        <flux:input label="{{ __('Email') }}" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="admin@workonpro.com" />

        <flux:input label="{{ __('Contraseña') }}" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />

        <flux:checkbox name="remember" label="{{ __('Recordarme') }}" />

        <flux:button variant="primary" type="submit" class="w-full">
            {{ __('Ingresar') }}
        </flux:button>
    </form>
</div>
