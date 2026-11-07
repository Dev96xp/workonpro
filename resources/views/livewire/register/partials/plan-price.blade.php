@php /** @var \App\Models\Plan|null $plan */ $featured ??= false; @endphp
@if ($plan)
    <div class="mt-4 flex items-end gap-2">
        <span @class(['text-5xl font-black sm:text-6xl', 'text-zinc-900' => $featured, 'text-white' => ! $featured])>{{ $plan->displayPrice() }}</span>
        @unless ($plan->isFree())
            <span @class(['mb-2', 'text-zinc-700' => $featured, 'text-zinc-500' => ! $featured])>{{ __('register.plans.pro_per_month') }}</span>
        @endunless
        @if ($plan->displayOldPrice())
            <span @class(['mb-2 text-sm line-through', 'text-zinc-700/70' => $featured, 'text-zinc-500' => ! $featured])>{{ $plan->displayOldPrice() }}</span>
        @endif
    </div>
@endif
