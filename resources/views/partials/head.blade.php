<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $businessLogo = tenant('id')
        ? \App\Models\BusinessImage::gallery()->where('is_logo', true)->first()
        : null;
@endphp

<title>{{ $title ?? (tenant('id') ? tenant('name') : config('app.name')) }}</title>

@if ($businessLogo)
    <link rel="icon" href="{{ $businessLogo->url() }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
@endif

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
