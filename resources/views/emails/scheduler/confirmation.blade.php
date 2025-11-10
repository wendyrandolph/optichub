@component('mail::message')

Appointment Booked
Hi {{ $appointment->client_name }},

Your appointment with Causey Web Solutions has been successfully booked.

Detail

Value

Date

{{ \Carbon\Carbon::parse($appointment->date)->format('F j, Y') }}

Time

{{ \Carbon\Carbon::parse($appointment->time)->format('g:i A') }}

@component('mail::button', ['url' => $googleLink])
Add to Google Calendar
@endcomponent

If you need to reschedule or cancel, please contact us directly by replying to this email.

Thanks,




{{ config('app.name') }} Team
@endcomponent