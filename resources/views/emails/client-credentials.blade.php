@component('mail::message')

Welcome to {{ config('app.name') }}
You're now set up with access to your client portal 🎉

We've created a temporary login for you below. Please use this link and these credentials to log in and set your permanent password.

@component('mail::panel')
Login URL: {{ url('/portal/login') }}

Email: {{ $email }}

Temporary Password: {{ $tempPassword }}
@endcomponent

@component('mail::button', ['url' => url('/portal/login')])
Access My Portal
@endcomponent

If you have any trouble logging in or have questions, just reply to this email.

Thanks,




{{ config('app.name') }} Team
@endcomponent
