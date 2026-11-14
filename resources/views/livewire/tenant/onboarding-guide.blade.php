<?php

use App\Models\BusinessImage;
use App\Models\BusinessProfile;
use App\Models\Coupon;
use App\Models\Service;
use Livewire\Volt\Component;

new class extends Component {
    public bool $showGuide = false;

    public function mount(): void
    {
        if (! tenant()->showsOnboardingGuide() || session()->has('onboarding_guide_seen')) {
            return;
        }

        session()->put('onboarding_guide_seen', true);

        $this->showGuide = $this->completedSteps() < count($this->steps());
    }

    public function openGuide(): void
    {
        $this->showGuide = true;
    }

    public function dismissForever(): void
    {
        tenant()->hideOnboardingGuide();

        $this->showGuide = false;
    }

    /**
     * @return list<array{key: string, url: string, icon: string, done: bool, important: bool}>
     */
    public function steps(): array
    {
        $profile = BusinessProfile::first();

        return [
            [
                'key' => 'profile',
                'url' => url('/settings'),
                'icon' => 'building-storefront',
                'done' => filled($profile?->description) && (filled($profile?->phone) || filled($profile?->whatsapp)),
                'important' => false,
            ],
            [
                'key' => 'images',
                'url' => url('/images'),
                'icon' => 'photo',
                'done' => BusinessImage::query()->gallery()->exists(),
                'important' => false,
            ],
            [
                'key' => 'featured',
                'url' => url('/images'),
                'icon' => 'star',
                'done' => BusinessImage::query()->where('is_featured', true)->exists(),
                'important' => false,
            ],
            [
                'key' => 'service',
                'url' => url('/services'),
                'icon' => 'wrench-screwdriver',
                'done' => Service::query()->where('is_active', true)->exists(),
                'important' => true,
            ],
            [
                'key' => 'coupon',
                'url' => url('/coupons'),
                'icon' => 'tag',
                'done' => Coupon::query()->where('is_active', true)->exists(),
                'important' => false,
            ],
        ];
    }

    public function completedSteps(): int
    {
        return collect($this->steps())->where('done', true)->count();
    }

    /**
     * @return array{steps: list<array{key: string, url: string, icon: string, done: bool, important: bool}>, done: int, total: int, active: bool}
     */
    public function with(): array
    {
        $steps = $this->steps();

        return [
            'steps' => $steps,
            'done' => collect($steps)->where('done', true)->count(),
            'total' => count($steps),
            'active' => tenant()->showsOnboardingGuide(),
        ];
    }
}; ?>

<div>
    @if ($active && $done < $total)
        <div data-onboarding-indicator class="flex flex-col gap-4 rounded-2xl border border-yellow-400/40 bg-yellow-400/10 p-5 sm:flex-row sm:items-center">
            <div class="min-w-0 flex-1">
                <p class="font-bold text-zinc-900 dark:text-white">{{ __('onboarding.indicator', ['done' => $done, 'total' => $total]) }}</p>
                <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">{{ __('onboarding.indicator_hint') }}</p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                    <div class="h-full rounded-full bg-yellow-400 transition-all" style="width: {{ round($done / $total * 100) }}%"></div>
                </div>
            </div>
            <flux:button wire:click="openGuide" variant="primary" icon-trailing="arrow-right">{{ __('onboarding.open') }}</flux:button>
        </div>
    @endif

    <flux:modal wire:model="showGuide" class="w-full max-w-lg" data-onboarding-guide>
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">🚀 {{ __('onboarding.title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('onboarding.subtitle') }}</flux:text>
            </div>

            <div>
                <div class="mb-1 flex justify-between text-xs font-semibold text-zinc-500">
                    <span>{{ __('onboarding.progress', ['done' => $done, 'total' => $total]) }}</span>
                    <span>{{ round($done / $total * 100) }}%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                    <div class="h-full rounded-full bg-yellow-400 transition-all" style="width: {{ round($done / $total * 100) }}%"></div>
                </div>
            </div>

            @if ($done === $total)
                <p class="rounded-xl bg-green-50 px-4 py-3 text-sm font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">🎉 {{ __('onboarding.all_done') }}</p>
            @endif

            <ol class="space-y-3">
                @foreach ($steps as $index => $step)
                    <li wire:key="onboarding-step-{{ $step['key'] }}" data-onboarding-step="{{ $step['key'] }}" data-done="{{ $step['done'] ? 'true' : 'false' }}" @class([
                        'flex gap-3 rounded-xl border p-3',
                        'border-green-500/30 bg-green-50/60 dark:bg-green-900/10' => $step['done'],
                        'border-yellow-400 bg-yellow-50/60 dark:bg-yellow-400/5' => ! $step['done'] && $step['important'],
                        'border-zinc-200 dark:border-zinc-700' => ! $step['done'] && ! $step['important'],
                    ])>
                        <div @class([
                            'flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                            'bg-green-500 text-white' => $step['done'],
                            'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300' => ! $step['done'],
                        ])>
                            @if ($step['done'])
                                <flux:icon.check variant="mini" />
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p @class(['font-semibold', 'text-zinc-400 line-through' => $step['done']])>{{ __("onboarding.steps.{$step['key']}.title") }}</p>
                                @if ($step['important'] && ! $step['done'])
                                    <flux:badge size="sm" color="yellow">⭐ {{ __('onboarding.important') }}</flux:badge>
                                @endif
                            </div>
                            @unless ($step['done'])
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __("onboarding.steps.{$step['key']}.body") }}</p>
                            @endunless
                        </div>
                        <div class="shrink-0 self-center">
                            @if ($step['done'])
                                <span class="text-xs font-semibold text-green-600 dark:text-green-400">{{ __('onboarding.done') }}</span>
                            @else
                                <flux:button :href="$step['url']" size="sm" variant="primary" :icon="$step['icon']">{{ __('onboarding.go') }}</flux:button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>

            <div data-onboarding-tip class="flex gap-3 rounded-xl bg-zinc-100 p-3 text-sm dark:bg-zinc-700/50">
                <span class="text-lg leading-none">💡</span>
                <p class="text-zinc-600 dark:text-zinc-300"><span class="font-semibold text-zinc-900 dark:text-white">{{ __('onboarding.tip_title') }}:</span> {{ __('onboarding.tip_home') }}</p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button wire:click="dismissForever" variant="ghost" size="sm" data-onboarding-dismiss>{{ __('onboarding.dont_show_again') }}</flux:button>
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('onboarding.not_now') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
