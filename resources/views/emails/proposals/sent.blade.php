@component('mail::message')
@if(!empty($logoUrl))
<div style="text-align:left;margin-bottom:16px;">
    <img src="{{ $logoUrl }}" alt="{{ $brandName ?? 'Renlo' }} logo" style="max-height:48px;max-width:200px;">
</div>
@endif

# New proposal ready

Hi {{ $clientFirstName ?? 'there' }},

{{ $brandName ?? 'Renlo' }} has prepared a proposal for you. You can review it and approve online.

@component('mail::button', ['url' => $proposalUrl, 'color' => 'primary'])
View proposal
@endcomponent

If the button doesn’t work, copy and paste this link into your browser:
{{ $proposalUrl }}

Thanks,  
{{ $brandName ?? 'Renlo' }}
@endcomponent
