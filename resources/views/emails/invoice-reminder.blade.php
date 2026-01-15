@component('mail::message')

@php
    $invoiceNumber = $invoice->invoice_number ?? $invoice->id;
@endphp

@if ($context === 'new')
Your new invoice #{{ $invoiceNumber }} is ready.
@else
Friendly reminder: invoice #{{ $invoiceNumber }} is due soon.
@endif

Hello {{ $client->firstName ?? 'there' }},

Amount due: **{{ money($invoice->balance_due ?? $invoice->total ?? 0) }}**  
Due date: **{{ optional($invoice->due_date)->format('M j, Y') ?? '—' }}**

@if (!empty($invoice->stripe_link))
@component('mail::button', ['url' => $invoice->stripe_link, 'color' => 'success'])
Pay Invoice
@endcomponent
@endif

If you have any questions, reply to this email and we’ll help right away.

Thanks,  
{{ config('app.name') }} Team
@endcomponent
