<flux:header class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    @php $logo = \App\Models\BusinessImage::gallery()->where('is_logo', true)->first(); @endphp

    <flux:navbar>
        <a href="{{ url('/') }}" wire:navigate class="flex items-center gap-2 px-2.5 h-8 rounded-lg text-zinc-500 hover:text-zinc-800 dark:text-white/80 dark:hover:text-white">
            @if ($logo)
                <img src="{{ $logo->url() }}" alt="{{ tenant('name') }}" class="size-7 rounded-lg object-cover" />
            @endif
            <flux:heading class="truncate font-bold">{{ tenant('name') }}</flux:heading>
        </a>
    </flux:navbar>

    <flux:spacer />

    <flux:navbar>
        <flux:dropdown>
            <flux:profile name="{{ auth()->user()->name }}" />
            <flux:menu>
                <form method="POST" action="{{ url('/logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('tenant.nav.logout') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:navbar>
</flux:header>
