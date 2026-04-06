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

    #[Validate('nullable|string|max:100')]
    public string $business_hours = '';

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
            $this->slogan         = $profile->slogan ?? '';
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
            $this->business_hours = $profile->business_hours ?? '';
            $this->description    = $profile->description ?? '';
            $this->policy         = $profile->policy ?? '';
            $this->objectives     = $profile->objectives ?? '';
        } else {
            $this->business_name = tenant('name') ?? '';
        }
    }

    public function saveProfile(): void
    {
        $this->validateOnly('business_name,slogan,business_email,phone,phone_2,whatsapp,address,city,country,website,instagram,facebook,business_hours,description,policy,objectives');

        BusinessProfile::updateOrCreate(
            ['id' => 1],
            [
                'business_name'  => $this->business_name,
                'slogan'         => $this->slogan ?: null,
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
                'business_hours' => $this->business_hours ?: null,
                'description'    => $this->description ?: null,
                'policy'         => $this->policy ?: null,
                'objectives'     => $this->objectives ?: null,
            ]
        );

        $this->profileSaved = '¡Perfil guardado exitosamente!';
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
        $this->passwordSaved               = '¡Contraseña actualizada exitosamente!';
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
                <flux:heading size="xl">Configuración</flux:heading>
                <flux:text class="text-zinc-500">Administra el perfil y configuración de tu negocio</flux:text>

                <div class="mt-8 max-w-2xl space-y-8">

                    {{-- Información básica --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:heading size="lg">Perfil del negocio</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">Información pública de tu negocio</flux:text>

                        @if ($profileSaved)
                            <div class="mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                {{ $profileSaved }}
                            </div>
                        @endif

                        <form wire:submit="saveProfile" class="mt-5 space-y-6">

                            {{-- Identidad --}}
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 mb-3">Identidad</p>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field class="sm:col-span-2">
                                        <flux:label>Nombre del negocio <span class="text-red-500">*</span></flux:label>
                                        <flux:input wire:model="business_name" />
                                        <flux:error name="business_name" />
                                    </flux:field>

                                    <flux:field class="sm:col-span-2">
                                        <flux:label>Slogan</flux:label>
                                        <flux:input wire:model="slogan" placeholder="Tu frase de marketing..." />
                                        <flux:error name="slogan" />
                                    </flux:field>
                                </div>
                            </div>

                            {{-- Contacto --}}
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 mb-3">Contacto</p>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field>
                                        <flux:label>Email de contacto</flux:label>
                                        <flux:input wire:model="business_email" type="email" />
                                        <flux:error name="business_email" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Teléfono principal</flux:label>
                                        <flux:input wire:model="phone" placeholder="+1 234 567 8900" />
                                        <flux:error name="phone" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Teléfono secundario</flux:label>
                                        <flux:input wire:model="phone_2" placeholder="+1 234 567 8901" />
                                        <flux:error name="phone_2" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>WhatsApp</flux:label>
                                        <flux:input wire:model="whatsapp" placeholder="+1 234 567 8900" />
                                        <flux:error name="whatsapp" />
                                    </flux:field>
                                </div>
                            </div>

                            {{-- Ubicación --}}
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 mb-3">Ubicación</p>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field class="sm:col-span-2">
                                        <flux:label>Dirección</flux:label>
                                        <flux:input wire:model="address" />
                                        <flux:error name="address" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Ciudad</flux:label>
                                        <flux:input wire:model="city" />
                                        <flux:error name="city" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>País</flux:label>
                                        <flux:input wire:model="country" />
                                        <flux:error name="country" />
                                    </flux:field>

                                    <flux:field class="sm:col-span-2">
                                        <flux:label>Horario de atención</flux:label>
                                        <flux:input wire:model="business_hours" placeholder="Lun-Vie 9am-6pm, Sáb 10am-2pm" />
                                        <flux:error name="business_hours" />
                                    </flux:field>
                                </div>
                            </div>

                            {{-- Web y redes --}}
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 mb-3">Web y Redes Sociales</p>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field class="sm:col-span-2">
                                        <flux:label>Sitio web</flux:label>
                                        <flux:input wire:model="website" placeholder="https://minegocio.com" />
                                        <flux:error name="website" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Instagram</flux:label>
                                        <flux:input wire:model="instagram" placeholder="@minegocio" />
                                        <flux:error name="instagram" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Facebook</flux:label>
                                        <flux:input wire:model="facebook" placeholder="facebook.com/minegocio" />
                                        <flux:error name="facebook" />
                                    </flux:field>
                                </div>
                            </div>

                            {{-- Descripción, políticas y objetivos --}}
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 mb-3">Información Empresarial</p>
                                <div class="grid gap-4">
                                    <flux:field>
                                        <flux:label>Descripción del negocio</flux:label>
                                        <flux:textarea wire:model="description" rows="4" placeholder="¿A qué se dedica tu negocio? Cuéntanos sobre tus servicios y productos..." />
                                        <flux:error name="description" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Política de la empresa</flux:label>
                                        <flux:textarea wire:model="policy" rows="4" placeholder="Describe las políticas de tu empresa (devoluciones, privacidad, etc.)..." />
                                        <flux:error name="policy" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Objetivos</flux:label>
                                        <flux:textarea wire:model="objectives" rows="4" placeholder="¿Cuáles son los objetivos de tu empresa?..." />
                                        <flux:error name="objectives" />
                                    </flux:field>
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove>Guardar perfil</span>
                                    <span wire:loading>Guardando...</span>
                                </flux:button>
                            </div>
                        </form>
                    </div>

                    {{-- Cambiar contraseña --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:heading size="lg">Cambiar contraseña</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">Asegúrate de usar una contraseña segura</flux:text>

                        @if ($passwordSaved)
                            <div class="mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                {{ $passwordSaved }}
                            </div>
                        @endif

                        <form wire:submit="savePassword" class="mt-5 space-y-4">
                            <flux:field>
                                <flux:label>Contraseña actual</flux:label>
                                <flux:input wire:model="current_password" type="password" />
                                <flux:error name="current_password" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Nueva contraseña</flux:label>
                                <flux:input wire:model="new_password" type="password" />
                                <flux:error name="new_password" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Confirmar nueva contraseña</flux:label>
                                <flux:input wire:model="new_password_confirmation" type="password" />
                            </flux:field>

                            <div class="flex justify-end pt-2">
                                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove>Actualizar contraseña</span>
                                    <span wire:loading>Actualizando...</span>
                                </flux:button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </flux:main>
    </div>
</div>
