<flux:header class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
    <flux:navbar>
        <flux:navbar.item href="{{ url('/dashboard') }}" wire:navigate>
            <flux:heading class="font-bold">{{ tenant('name') }}</flux:heading>
        </flux:navbar.item>
    </flux:navbar>

    <flux:spacer />

    <flux:navbar>
        <flux:dropdown>
            <flux:profile name="{{ auth()->user()->name }}" />
            <flux:menu>
                <flux:menu.item wire:click="logout" icon="arrow-right-start-on-rectangle">
                    Cerrar sesión
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:navbar>
</flux:header>
