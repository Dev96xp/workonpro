<?php

use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tenant')] class extends Component {
    // Basic info
    #[Validate('required|string|max:100')]
    public string $business_name = '';

    #[Validate('nullable|string|max:150')]
    public string $business_title = '';

    public bool $show_logo_on_hero = false;

    #[Validate('nullable|string|max:150')]
    public string $slogan = '';

    #[Validate('nullable|email|max:100')]
    public string $business_email = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    #[Validate('nullable|string|max:30')]
    public string $phone_2 = '';

    #[Validate('nullable|string|max:30')]
    public string $whatsapp = '';

    #[Validate('nullable|string|max:255')]
    public string $address = '';

    #[Validate('nullable|string|max:100')]
    public string $city = '';

    #[Validate('nullable|string|max:100')]
    public string $country = '';

    #[Validate('nullable|url|max:255')]
    public string $website = '';

    #[Validate('nullable|string|max:150')]
    public string $instagram = '';

    #[Validate('nullable|string|max:150')]
    public string $facebook = '';

    #[Validate('nullable|string|max:150')]
    public string $youtube = '';

    #[Validate('nullable|string|max:150')]
    public string $x = '';

    #[Validate('nullable|string|max:150')]
    public string $tiktok = '';

    #[Validate('nullable|string|max:150')]
    public string $discord = '';

    #[Validate('nullable|string|max:100')]
    public string $business_hours = '';

    // Licencia y seguro
    public bool $has_license = false;

    #[Validate('nullable|string|max:100')]
    public string $license_number = '';

    public bool $has_insurance = false;

    #[Validate('nullable|string|max:100')]
    public string $insurance_number = '';

    // Extended info
    #[Validate('nullable|string|max:2000')]
    public string $description = '';

    #[Validate('nullable|string|max:2000')]
    public string $policy = '';

    #[Validate('nullable|string|max:2000')]
    public string $objectives = '';

    // Password fields
    #[Validate('required|string|current_password')]
    public string $current_password = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $new_password = '';

    public string $new_password_confirmation = '';

    public string $profileSaved = '';
    public string $passwordSaved = '';

    public function mount(): void
    {
        $profile = BusinessProfile::first();

        if ($profile) {
            $this->business_name  = $profile->business_name;
            $this->business_title    = $profile->business_title ?? '';
            $this->show_logo_on_hero = $profile->show_logo_on_hero;
            $this->slogan            = $profile->slogan ?? '';
            $this->business_email = $profile->email ?? '';
            $this->phone          = $profile->phone ?? '';
            $this->phone_2        = $profile->phone_2 ?? '';
            $this->whatsapp       = $profile->whatsapp ?? '';
            $this->address        = $profile->address ?? '';
            $this->city           = $profile->city ?? '';
            $this->country        = $profile->country ?? '';
            $this->website        = $profile->website ?? '';
            $this->instagram      = $profile->instagram ?? '';
            $this->facebook       = $profile->facebook ?? '';
            $this->youtube        = $profile->youtube ?? '';
            $this->x              = $profile->x ?? '';
            $this->tiktok         = $profile->tiktok ?? '';
            $this->discord        = $profile->discord ?? '';
            $this->business_hours = $profile->business_hours ?? '';
            $this->has_license      = $profile->has_license;
            $this->license_number   = $profile->license_number ?? '';
            $this->has_insurance    = $profile->has_insurance;
            $this->insurance_number = $profile->insurance_number ?? '';
            $this->description    = $profile->description ?? '';
            $this->policy         = $profile->policy ?? '';
            $this->objectives     = $profile->objectives ?? '';
        } else {
            $this->business_name = tenant('name') ?? '';
        }
    }

    public function saveProfile(): void
    {
        $this->validateOnly('business_name,business_title,slogan,business_email,phone,phone_2,whatsapp,address,city,country,website,instagram,facebook,youtube,x,tiktok,discord,business_hours,license_number,insurance_number,description,policy,objectives');

        BusinessProfile::updateOrCreate(
            ['id' => 1],
            [
                'business_name'      => $this->business_name,
                'business_title'     => $this->business_title ?: null,
                'show_logo_on_hero'  => $this->show_logo_on_hero,
                'slogan'             => $this->slogan ?: null,
                'email'          => $this->business_email ?: null,
                'phone'          => $this->phone ?: null,
                'phone_2'        => $this->phone_2 ?: null,
                'whatsapp'       => $this->whatsapp ?: null,
                'address'        => $this->address ?: null,
                'city'           => $this->city ?: null,
                'country'        => $this->country ?: null,
                'website'        => $this->website ?: null,
                'instagram'      => $this->instagram ?: null,
                'facebook'       => $this->facebook ?: null,
                'youtube'        => $this->youtube ?: null,
                'x'              => $this->x ?: null,
                'tiktok'         => $this->tiktok ?: null,
                'discord'        => $this->discord ?: null,
                'business_hours' => $this->business_hours ?: null,
                'has_license'      => $this->has_license,
                'license_number'   => $this->license_number ?: null,
                'has_insurance'    => $this->has_insurance,
                'insurance_number' => $this->insurance_number ?: null,
                'description'    => $this->description ?: null,
                'policy'         => $this->policy ?: null,
                'objectives'     => $this->objectives ?: null,
            ]
        );

        $this->profileSaved = __('tenant.settings.profile_saved');
    }

    public function savePassword(): void
    {
        $this->validateOnly('current_password,new_password');

        auth()->user()->update([
            'password' => $this->new_password,
        ]);

        $this->current_password            = '';
        $this->new_password                = '';
        $this->new_password_confirmation   = '';
        $this->passwordSaved               = __('tenant.settings.password_saved');
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/'));
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="p-6">
                <flux:heading size="xl">{{ __('tenant.nav.settings') }}</flux:heading>
                <flux:text class="text-zinc-500">{{ __('tenant.settings.subheading') }}</flux:text>

                <div class="mt-8 max-w-2xl space-y-8">

                    {{-- Información básica --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:heading size="lg">{{ __('tenant.settings.profile_heading') }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">{{ __('tenant.settings.profile_sub') }}</flux:text>

                        @if ($profileSaved)
                            <div class="mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                {{ $profileSaved }}
                            </div>
                        @endif

                        <form wire:submit="saveProfile" class="mt-5 space-y-6">

                            {{-- Identidad --}}
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 mb-3">{{ __('tenant.settings.section_identity') }}</p>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field class="sm:col-span-2">
                                        <flux:label>{{ __('tenant.settings.business_name_label') }} <span class="text-red-500">*</span></flux:label>
                                        <flux:input wire:model="business_name" />
                                        <flux:error name="business_name" />
                                    </flux:field>

                                    <flux:field class="sm:col-span-2">
                                        <flux:label>{{ __('tenant.settings.business_title_label') }}</flux:label>
                                        <flux:input wire:model="business_title" placeholder="{{ __('tenant.settings.business_title_placeholder') }}" />
                                        <flux:description>{{ __('tenant.settings.business_title_hint') }}</flux:description>
                                        <flux:error name="business_title" />
                                    </flux:field>

                                    <flux:field class="sm:col-span-2">
                                        <flux:switch wire:model="show_logo_on_hero" label="{{ __('tenant.settings.show_logo_switch') }}" />
                                        <flux:description>{{ __('tenant.settings.show_logo_hint') }}</flux:description>
                                    </flux:field>

                                    <flux:field class="sm:col-span-2">
                                        <flux:label>{{ __('tenant.settings.slogan_label') }}</flux:label>
                                        <flux:input wire:model="slogan" placeholder="{{ __('tenant.settings.slogan_placeholder') }}" />
                                        <flux:error name="slogan" />
                                    </flux:field>
                                </div>
                            </div>

                            {{-- Contacto --}}
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 mb-3">{{ __('tenant.settings.section_contact') }}</p>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.email_label') }}</flux:label>
                                        <flux:input wire:model="business_email" type="email" />
                                        <flux:error name="business_email" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.phone_label') }}</flux:label>
                                        <flux:input wire:model="phone" placeholder="+1 234 567 8900" />
                                        <flux:error name="phone" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.phone2_label') }}</flux:label>
                                        <flux:input wire:model="phone_2" placeholder="+1 234 567 8901" />
                                        <flux:error name="phone_2" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.whatsapp_label') }}</flux:label>
                                        <flux:input wire:model="whatsapp" placeholder="+1 234 567 8900" />
                                        <flux:error name="whatsapp" />
                                    </flux:field>
                                </div>
                            </div>

                            {{-- Licencia y seguro --}}
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 mb-3">{{ __('tenant.settings.section_license') }}</p>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field>
                                        <flux:switch wire:model.live="has_license" label="{{ __('tenant.settings.has_license_switch') }}" />
                                    </flux:field>

                                    @if ($has_license)
                                        <flux:field>
                                            <flux:label>{{ __('tenant.settings.license_number_label') }}</flux:label>
                                            <flux:input wire:model="license_number" placeholder="{{ __('tenant.common.optional') }}" />
                                            <flux:error name="license_number" />
                                        </flux:field>
                                    @endif

                                    <flux:field>
                                        <flux:switch wire:model.live="has_insurance" label="{{ __('tenant.settings.has_insurance_switch') }}" />
                                    </flux:field>

                                    @if ($has_insurance)
                                        <flux:field>
                                            <flux:label>{{ __('tenant.settings.insurance_number_label') }}</flux:label>
                                            <flux:input wire:model="insurance_number" placeholder="{{ __('tenant.common.optional') }}" />
                                            <flux:error name="insurance_number" />
                                        </flux:field>
                                    @endif
                                </div>
                            </div>

                            {{-- Ubicación --}}
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 mb-3">{{ __('tenant.settings.section_location') }}</p>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field class="sm:col-span-2">
                                        <flux:label>{{ __('tenant.common.address') }}</flux:label>
                                        <flux:input wire:model="address" />
                                        <flux:error name="address" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.city_label') }}</flux:label>
                                        <flux:input wire:model="city" />
                                        <flux:error name="city" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.country_label') }}</flux:label>
                                        <flux:input wire:model="country" />
                                        <flux:error name="country" />
                                    </flux:field>

                                    <flux:field class="sm:col-span-2">
                                        <flux:label>{{ __('tenant.settings.hours_label') }}</flux:label>
                                        <flux:input wire:model="business_hours" placeholder="{{ __('tenant.settings.hours_placeholder') }}" />
                                        <flux:error name="business_hours" />
                                    </flux:field>
                                </div>
                            </div>

                            {{-- Web y redes --}}
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 mb-3">{{ __('tenant.settings.section_web') }}</p>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field class="sm:col-span-2">
                                        <flux:label>{{ __('tenant.settings.website_label') }}</flux:label>
                                        <flux:input wire:model="website" placeholder="https://minegocio.com" />
                                        <flux:error name="website" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.instagram_label') }}</flux:label>
                                        <flux:input wire:model="instagram" placeholder="@minegocio" />
                                        <flux:error name="instagram" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.facebook_label') }}</flux:label>
                                        <flux:input wire:model="facebook" placeholder="facebook.com/minegocio" />
                                        <flux:error name="facebook" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.youtube_label') }}</flux:label>
                                        <flux:input wire:model="youtube" placeholder="youtube.com/@minegocio" />
                                        <flux:error name="youtube" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.x_label') }}</flux:label>
                                        <flux:input wire:model="x" placeholder="@minegocio" />
                                        <flux:error name="x" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.tiktok_label') }}</flux:label>
                                        <flux:input wire:model="tiktok" placeholder="@minegocio" />
                                        <flux:error name="tiktok" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.discord_label') }}</flux:label>
                                        <flux:input wire:model="discord" placeholder="discord.gg/minegocio" />
                                        <flux:error name="discord" />
                                    </flux:field>
                                </div>
                            </div>

                            {{-- Descripción, políticas y objetivos --}}
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 mb-3">{{ __('tenant.settings.section_business_info') }}</p>
                                <div class="grid gap-4">
                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.description_label') }}</flux:label>
                                        <flux:textarea wire:model="description" rows="4" placeholder="{{ __('tenant.settings.description_placeholder') }}" />
                                        <flux:error name="description" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.policy_label') }}</flux:label>
                                        <flux:textarea wire:model="policy" rows="4" placeholder="{{ __('tenant.settings.policy_placeholder') }}" />
                                        <flux:error name="policy" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('tenant.settings.objectives_label') }}</flux:label>
                                        <flux:textarea wire:model="objectives" rows="4" placeholder="{{ __('tenant.settings.objectives_placeholder') }}" />
                                        <flux:error name="objectives" />
                                    </flux:field>
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove>{{ __('tenant.settings.save_profile') }}</span>
                                    <span wire:loading>{{ __('tenant.common.saving') }}</span>
                                </flux:button>
                            </div>
                        </form>
                    </div>

                    {{-- Cambiar contraseña --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:heading size="lg">{{ __('tenant.settings.change_password_heading') }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">{{ __('tenant.settings.change_password_sub') }}</flux:text>

                        @if ($passwordSaved)
                            <div class="mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                {{ $passwordSaved }}
                            </div>
                        @endif

                        <form wire:submit="savePassword" class="mt-5 space-y-4">
                            <flux:field>
                                <flux:label>{{ __('tenant.settings.current_password_label') }}</flux:label>
                                <flux:input wire:model="current_password" type="password" />
                                <flux:error name="current_password" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('tenant.settings.new_password_label') }}</flux:label>
                                <flux:input wire:model="new_password" type="password" />
                                <flux:error name="new_password" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('tenant.settings.confirm_password_label') }}</flux:label>
                                <flux:input wire:model="new_password_confirmation" type="password" />
                            </flux:field>

                            <div class="flex justify-end pt-2">
                                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove>{{ __('tenant.settings.update_password') }}</span>
                                    <span wire:loading>{{ __('tenant.settings.updating') }}</span>
                                </flux:button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </flux:main>
    </div>
</div>
