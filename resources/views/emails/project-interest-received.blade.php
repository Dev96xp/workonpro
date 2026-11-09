<x-mail::message>
{{ __('projects.mail.hello', ['name' => $homeownerName]) }}

{{ __('projects.mail.received_intro', ['contractor' => $contractorName, 'project' => $projectTitle]) }}

<x-mail::panel>
{!! nl2br(e($interestMessage)) !!}
</x-mail::panel>

{{ __('projects.mail.received_next') }}

<x-mail::button :url="$projectUrl">
{{ __('projects.mail.received_button') }}
</x-mail::button>

{{ __('projects.mail.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
