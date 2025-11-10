@component('mail::message')

Welcome to Causey Web Solutions
You're now set up with access to your client portal 🎉

We've created a temporary login for you below. Please use this link and these credentials to log in and set your permanent password.

@component('mail::panel')
Login URL: portal.causeywebsolutions.com/login

Username: {{ $username }}

Temporary Password: {{ $tempPassword }}
@endcomponent

@component('mail::button', ['url' => 'https://portal.causeywebsolutions.com/login'])
Access My Portal
@endcomponent

If you have any trouble logging in or have questions, just reply to this email or contact us at support@causeywebsolutions.com.

Thanks,




{{ config('app.name') }} Team
@endcomponent