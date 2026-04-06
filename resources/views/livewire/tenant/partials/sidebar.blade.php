<flux:sidebar class="border-r border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
    <flux:navlist>
        <flux:navlist.item href="{{ url('/dashboard') }}" icon="home" wire:navigate
            :current="request()->routeIs('tenant.dashboard')">
            Dashboard
        </flux:navlist.item>
        <flux:navlist.item href="{{ url('/clients') }}" icon="users" wire:navigate
            :current="request()->routeIs('tenant.clients')">
            Clientes
        </flux:navlist.item>
        <flux:navlist.item href="{{ url('/coupons') }}" icon="tag" wire:navigate
            :current="request()->routeIs('tenant.coupons')">
            Cupones
        </flux:navlist.item>
        <flux:navlist.item href="{{ url('/images') }}" icon="photo" wire:navigate
            :current="request()->routeIs('tenant.images')">
            Imágenes
        </flux:navlist.item>
        <flux:navlist.item href="{{ url('/settings') }}" icon="cog-6-tooth" wire:navigate
            :current="request()->routeIs('tenant.settings')">
            Configuración
        </flux:navlist.item>
    </flux:navlist>
</flux:sidebar>
