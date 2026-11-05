<flux:field>
    <flux:label>{{ __('account_type.question') }}</flux:label>
    <div class="mt-2 grid grid-cols-2 gap-3">
        @foreach (\App\AccountType::cases() as $type)
            <label wire:key="account-type-{{ $type->value }}" class="relative flex cursor-pointer flex-col gap-1 rounded-xl border-2 p-4 transition {{ $account_type === $type->value ? 'border-yellow-400 bg-yellow-400/10' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                <input type="radio" wire:model.live="account_type" value="{{ $type->value }}" class="sr-only">
                <span class="text-2xl">{{ $type === \App\AccountType::Business ? '🏢' : '👤' }}</span>
                <span class="font-semibold text-zinc-900 dark:text-white">{{ $type->label() }}</span>
                <span class="text-xs text-zinc-500">{{ __('account_type.'.$type->value.'_hint') }}</span>
            </label>
        @endforeach
    </div>
    <flux:error name="account_type" />
</flux:field>
