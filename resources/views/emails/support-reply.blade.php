@component('mail::message')
# Support update

Your support ticket has a new reply.

@component('mail::panel')
{{ $replyBody }}
@endcomponent

Ticket: {{ $ticket->subject ?? ('Ticket #' . $ticket->id) }}
@endcomponent
