<x-mail::message>
Hola {{ $tenantName }},

{!! nl2br(e($messageBody)) !!}

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
