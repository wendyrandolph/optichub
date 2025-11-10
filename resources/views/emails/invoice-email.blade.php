@component('mail::message')

Your Invoice #{{ $invoice['invoice_number'] }}
Hello {{ $clientName }},

Here is the summary for your latest invoice from Causey Web Solutions.

Field

Value

Invoice #

{{ $invoice['invoice_number'] }}

Due Date

{{ $invoice['due_date'] }}

Status

{{ $invoice['status'] }}

Total Due

**${{ number_format($total, 2) }}**

@component('mail::button', ['url' => $invoice['stripe_link'], 'color' => 'success'])
💳 Pay Now
@endcomponent

If you have any questions regarding this invoice or need assistance, please reply directly to this email.

Thanks,




{{ config('app.name') }} Team
@endcomponent