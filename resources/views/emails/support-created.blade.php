@component('mail::message')
# New support ticket

**Workspace:** {{ $ticket->tenant?->name ?? 'Unknown tenant' }}  
**Category:** {{ ucfirst($ticket->category ?? 'request') }}  
**Subject:** {{ $ticket->subject }}

@component('mail::panel')
{{ $ticket->body }}
@endcomponent

@if(!empty($pageUrl))
Page URL: {{ $pageUrl }}
@endif

@endcomponent
