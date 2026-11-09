<x-mail::message>
{{ __('projects.mail.hello', ['name' => $contractorName]) }}

{{ __('projects.mail.accepted_intro', ['homeowner' => $homeownerName, 'project' => $projectTitle]) }}

<x-mail::panel>
**{{ $homeownerName }}**<br>
{{ $homeownerEmail }}
@if ($homeownerPhone)
<br>{{ $homeownerPhone }}
@endif
</x-mail::panel>

{{ __('projects.mail.accepted_next') }}

@if ($projectsUrl)
<x-mail::button :url="$projectsUrl">
{{ __('projects.mail.accepted_button') }}
</x-mail::button>
@endif

{{ __('projects.mail.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
