@component('mail::message')

New Proposal: {{ $proposal->title }}
Hello {{ $clientFirstName }},

A new proposal has been created for you. You can view the full details and respond to it below.

@component('mail::button', ['url' => $proposalUrl])
View Proposal
@endcomponent

Proposal Details
Field

Value

Title

{{ $proposal->title }}

Project

{{ $proposal->project->name ?? 'N/A' }}

Status

{{ Str::title($proposal->status) }}

If you have any questions, please reply to this email.

Thanks,




{{ config('app.name') }} Team
@endcomponent