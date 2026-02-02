@component('mail::message')
@if(!empty($logoUrl))
<div style="text-align:left;margin-bottom:16px;">
    <img src="{{ $logoUrl }}" alt="{{ $brandName ?? 'Renlo' }} logo" style="max-height:48px;max-width:200px;">
</div>
@endif

# Invoice ready

Hi {{ $client?->firstName ?? $client?->name ?? 'there' }},

Your invoice from {{ $brandName ?? 'Renlo' }} is ready.

@component('mail::panel')
**Invoice:** #{{ $invoice->invoice_number ?? $invoice->id }}  
**Amount due:** {{ $invoice->balance_due ?? $invoice->total_amount ?? $invoice->total ?? '—' }}  
@if(!empty($invoice->due_date))
**Due date:** {{ \Carbon\Carbon::parse($invoice->due_date)->format('M j, Y') }}
@endif
@endcomponent

@component('mail::button', ['url' => route('tenant.invoices.show', ['tenant' => $invoice->tenant_id, 'invoice' => $invoice->id])])
View invoice
@endcomponent

Thanks,  
{{ $brandName ?? 'Renlo' }}
@endcomponent
