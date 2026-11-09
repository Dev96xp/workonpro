<?php

use App\Models\Homeowner;
use App\Services\IpGeolocationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.homeowner')] #[Title('Workon')] class extends Component {
    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|email|max:255|unique:homeowners,email')]
    public string $email = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    #[Validate('required|string|max:100')]
    public string $city = '';

    #[Validate('required|string|max:100')]
    public string $state = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    #[Validate('accepted', message: 'register.create.accept_terms_required')]
    public bool $accepted_terms = false;

    public function mount(IpGeolocationService $geolocation): void
    {
        if (auth('homeowner')->check()) {
            $this->redirectRoute('homeowner.dashboard');

            return;
        }

        $location = $geolocation->location(IpGeolocationService::clientIp());
        $this->city = $location['city'] ?? '';
        $this->state = $location['state'] ?? '';
    }

    public function register(): void
    {
        $validated = $this->validate();

        $homeowner = Homeowner::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'password' => $validated['password'],
        ]);

        Auth::guard('homeowner')->login($homeowner);
        Session::regenerate();

        $this->redirectRoute('homeowner.projects.create');
    }
}; ?>

<div class="mx-auto max-w-lg">
    <div class="mb-8 text-center">
        <span class="text-xs font-bold uppercase tracking-[0.3em] text-yellow-400">{{ __('homeowner.register.tagline') }}</span>
        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">{{ __('homeowner.register.heading') }}</h1>
        <p class="mt-2 text-zinc-400">{{ __('homeowner.register.subheading') }}</p>
    </div>

    <form wire:submit="register" class="space-y-5">
        <flux:input wire:model="name" :label="__('homeowner.fields.name')" autofocus autocomplete="name" />
        <flux:input wire:model="email" type="email" :label="__('homeowner.fields.email')" autocomplete="email" />
        <flux:input wire:model="phone" type="tel" :label="__('homeowner.fields.phone')" :description="__('homeowner.fields.phone_hint')" autocomplete="tel" />

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="city" :label="__('homeowner.fields.city')" placeholder="Omaha" />
            <flux:input wire:model="state" :label="__('homeowner.fields.state')" placeholder="Nebraska" />
        </div>

        <flux:input wire:model="password" type="password" :label="__('homeowner.fields.password')" autocomplete="new-password" viewable />
        <flux:input wire:model="password_confirmation" type="password" :label="__('homeowner.fields.password_confirmation')" autocomplete="new-password" viewable />

        <flux:field variant="inline">
            <flux:checkbox wire:model="accepted_terms" />
            <flux:label>
                {!! __('register.create.accept_terms', [
                    'terms_link' => '<a href="'.route('terms').'" target="_blank" class="text-yellow-400 underline hover:no-underline">'.__('register.create.terms_link_text').'</a>',
                    'privacy_link' => '<a href="'.route('privacy').'" target="_blank" class="text-yellow-400 underline hover:no-underline">'.__('register.create.privacy_link_text').'</a>',
                ]) !!}
            </flux:label>
            <flux:error name="accepted_terms" />
        </flux:field>

        <button type="submit" class="w-full rounded-full bg-yellow-400 py-4 font-extrabold text-zinc-950 transition hover:bg-yellow-300">
            <span wire:loading.remove wire:target="register">{{ __('homeowner.register.submit') }}</span>
            <span wire:loading wire:target="register">{{ __('homeowner.register.submitting') }}</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-400">
        {{ __('homeowner.register.have_account') }}
        <a href="{{ route('homeowner.login') }}" class="font-semibold text-yellow-400 hover:underline">{{ __('homeowner.nav.login') }}</a>
    </p>
</div>
