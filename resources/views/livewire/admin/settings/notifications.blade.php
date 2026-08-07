<?php

use App\Mail\TenantNotification;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public string $fromEmail = '';

    public string $fromEmailSaved = '';

    public array $selected = [];

    public bool $sendToAll = false;

    #[Validate('required|string|max:150')]
    public string $subject = '';

    #[Validate('required|string|max:5000')]
    public string $body = '';

    public string $sentMessage = '';

    public function mount(): void
    {
        $this->fromEmail = Setting::get('notifications_from_email', config('mail.from.address'));
    }

    public function saveFromEmail(): void
    {
        $this->validate(['fromEmail' => 'required|email']);

        Setting::set('notifications_from_email', $this->fromEmail);

        $this->fromEmailSaved = '¡Correo remitente guardado!';
    }

    public function send(): void
    {
        $this->validate();

        if (! $this->sendToAll && empty($this->selected)) {
            $this->addError('selected', 'Selecciona al menos un negocio, o marca "Todos".');

            return;
        }

        $tenants = $this->sendToAll
            ? Tenant::where('status', 'active')->get()
            : Tenant::whereIn('id', $this->selected)->where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            if ($tenant->email) {
                Mail::to($tenant->email)->queue(new TenantNotification($this->subject, $this->body, $tenant->name));
            }
        }

        $this->sentMessage = "Mensaje enviado a {$tenants->count()} negocio(s).";
        $this->reset(['selected', 'sendToAll', 'subject', 'body']);
    }

    public function with(): array
    {
        return [
            'tenants' => Tenant::orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex items-center gap-4">
        <flux:button href="{{ route('admin.settings.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
        <flux:heading size="xl">Notificaciones a negocios</flux:heading>
    </div>

    <div class="max-w-2xl space-y-8">
        {{-- Correo remitente --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">Correo remitente</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500">Desde qué dirección se envían estas notificaciones. Puedes cambiarla cuando quieras.</flux:text>

            @if ($fromEmailSaved)
                <div class="mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                    {{ $fromEmailSaved }}
                </div>
            @endif

            <form wire:submit="saveFromEmail" class="mt-4 flex items-end gap-3">
                <flux:field class="flex-1">
                    <flux:label>Correo remitente</flux:label>
                    <flux:input wire:model="fromEmail" type="email" placeholder="notificaciones@workonpro.com" />
                    <flux:error name="fromEmail" />
                </flux:field>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </form>
        </div>

        {{-- Mensaje --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">Enviar mensaje</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500">Notifica a uno, varios, o todos los negocios activos por email.</flux:text>

            @if ($sentMessage)
                <div class="mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                    {{ $sentMessage }}
                </div>
            @endif

            <form wire:submit="send" class="mt-5 space-y-5">
                <flux:field>
                    <flux:label>Destinatarios</flux:label>
                    <flux:checkbox wire:model.live="sendToAll" label="Todos los negocios activos" />
                    <flux:error name="selected" />
                </flux:field>

                @if (! $sendToAll)
                    <div class="max-h-64 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        @forelse ($tenants as $tenant)
                            <label class="flex items-center gap-3 border-b border-zinc-100 px-4 py-2 text-sm last:border-b-0 dark:border-zinc-700">
                                <input type="checkbox" wire:model="selected" value="{{ $tenant->id }}" class="rounded border-zinc-300" />
                                <span>{{ $tenant->name }}</span>
                                <span class="text-xs text-zinc-400">{{ $tenant->email }}</span>
                            </label>
                        @empty
                            <p class="px-4 py-3 text-sm text-zinc-400">No hay negocios registrados.</p>
                        @endforelse
                    </div>
                @endif

                <flux:field>
                    <flux:label>Asunto</flux:label>
                    <flux:input wire:model="subject" placeholder="Nueva función disponible en Workon" />
                    <flux:error name="subject" />
                </flux:field>

                <flux:field>
                    <flux:label>Mensaje</flux:label>
                    <flux:textarea wire:model="body" rows="6" placeholder="Escribe el mensaje que quieres enviar..." />
                    <flux:error name="body" />
                </flux:field>

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary" wire:confirm="¿Enviar este mensaje por email?" wire:loading.attr="disabled">
                        <span wire:loading.remove>Enviar</span>
                        <span wire:loading>Enviando...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
