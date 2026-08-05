<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ tenant('name') ?? config('app.name') }}</title>
    @php $businessLogo = \App\Models\BusinessImage::gallery()->where('is_logo', true)->first(); @endphp
    @if ($businessLogo)
        <link rel="icon" href="{{ $businessLogo->url() }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
    {{ $slot }}
    @livewireScripts
    @fluxScripts
</body>
</html>
