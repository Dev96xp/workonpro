<flux:sidebar sticky stashable class="border-r border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

    <flux:navlist>
        <flux:navlist.item href="{{ url('/dashboard') }}" icon="home" wire:navigate
            :current="request()->routeIs('tenant.dashboard')">
            {{ __('tenant.nav.dashboard') }}
        </flux:navlist.item>
        <flux:navlist.item href="{{ url('/clients') }}" icon="users" wire:navigate
            :current="request()->routeIs('tenant.clients')">
            {{ __('tenant.nav.clients') }}
        </flux:navlist.item>
        <flux:navlist.item href="{{ url('/services') }}" icon="wrench-screwdriver" wire:navigate
            :current="request()->routeIs('tenant.services')">
            {{ __('tenant.nav.services') }}
        </flux:navlist.item>
        <flux:navlist.item href="{{ url('/coupons') }}" icon="tag" wire:navigate
            :current="request()->routeIs('tenant.coupons')">
            {{ __('tenant.nav.coupons') }}
        </flux:navlist.item>
        <flux:navlist.item href="{{ url('/images') }}" icon="photo" wire:navigate
            :current="request()->routeIs('tenant.images')">
            {{ __('tenant.nav.images') }}
        </flux:navlist.item>
        <flux:navlist.item href="{{ url('/settings') }}" icon="cog-6-tooth" wire:navigate
            :current="request()->routeIs('tenant.settings')">
            {{ __('tenant.nav.settings') }}
        </flux:navlist.item>
    </flux:navlist>
</flux:sidebar>
