<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    //
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Configuraciones</flux:heading>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <a href="{{ route('admin.settings.notifications') }}" wire:navigate class="block rounded-xl border border-zinc-200 bg-white p-6 transition hover:border-yellow-400 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <flux:icon name="envelope" class="size-8 text-zinc-400" />
                <div>
                    <flux:heading size="lg">Notificaciones a negocios</flux:heading>
                    <flux:subheading>Enviar un mensaje por email a uno, varios, o todos los negocios</flux:subheading>
                </div>
            </div>
        </a>
    </div>
</div>
