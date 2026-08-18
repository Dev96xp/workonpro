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
        @if (\App\Models\Tenant::hasFeature(tenant('plan'), 'appointments'))
            <flux:navlist.item href="{{ url('/appointments') }}" icon="calendar-days" wire:navigate
                :current="request()->routeIs('tenant.appointments')">
                {{ __('tenant.nav.appointments') }}
            </flux:navlist.item>
        @endif
        <flux:navlist.item href="{{ url('/images') }}" icon="photo" wire:navigate
            :current="request()->routeIs('tenant.images')">
            {{ __('tenant.nav.images') }}
        </flux:navlist.item>
        @if (\App\Models\Tenant::hasFeature(tenant('plan'), 'invoices'))
            <flux:navlist.group
                heading="{{ __('tenant.nav.invoices') }}"
                expandable
                :expanded="request()->routeIs('tenant.invoices', 'tenant.products', 'tenant.product-categories', 'tenant.taxes')"
            >
                <flux:navlist.item href="{{ url('/invoices') }}" icon="banknotes" wire:navigate
                    :current="request()->routeIs('tenant.invoices')">
                    {{ __('tenant.nav.invoices_list') }}
                </flux:navlist.item>
                <flux:navlist.item href="{{ url('/products') }}" icon="cube" wire:navigate
                    :current="request()->routeIs('tenant.products')">
                    {{ __('tenant.nav.products') }}
                </flux:navlist.item>
                <flux:navlist.item href="{{ url('/product-categories') }}" icon="tag" wire:navigate
                    :current="request()->routeIs('tenant.product-categories')">
                    {{ __('tenant.nav.product_categories') }}
                </flux:navlist.item>
                <flux:navlist.item href="{{ url('/taxes') }}" icon="receipt-percent" wire:navigate
                    :current="request()->routeIs('tenant.taxes')">
                    {{ __('tenant.nav.taxes') }}
                </flux:navlist.item>
            </flux:navlist.group>
        @endif
        @if (\App\Models\Tenant::hasFeature(tenant('plan'), 'employees'))
            <flux:navlist.group
                heading="{{ __('tenant.nav.employees') }}"
                expandable
                :expanded="request()->routeIs('tenant.employees', 'tenant.locations', 'tenant.attendance', 'tenant.attendance.payroll')"
            >
                <flux:navlist.item href="{{ url('/employees') }}" icon="identification" wire:navigate
                    :current="request()->routeIs('tenant.employees')">
                    {{ __('tenant.nav.employees') }}
                </flux:navlist.item>
                <flux:navlist.item href="{{ url('/locations') }}" icon="map-pin" wire:navigate
                    :current="request()->routeIs('tenant.locations')">
                    {{ __('tenant.nav.locations') }}
                </flux:navlist.item>
                <flux:navlist.item href="{{ url('/attendance') }}" icon="clock" wire:navigate
                    :current="request()->routeIs('tenant.attendance')">
                    {{ __('tenant.nav.attendance') }}
                </flux:navlist.item>
                <flux:navlist.item href="{{ url('/attendance/payroll') }}" icon="banknotes" wire:navigate
                    :current="request()->routeIs('tenant.attendance.payroll')">
                    {{ __('tenant.nav.payroll') }}
                </flux:navlist.item>
            </flux:navlist.group>
        @endif
        <flux:navlist.item href="{{ url('/settings') }}" icon="cog-6-tooth" wire:navigate
            :current="request()->routeIs('tenant.settings')">
            {{ __('tenant.nav.settings') }}
        </flux:navlist.item>
    </flux:navlist>
</flux:sidebar>
