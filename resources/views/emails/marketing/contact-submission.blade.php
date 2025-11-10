@component('mail::message')
# New Contact Form Submission

A new message has been submitted via the marketing contact form.

@component('mail::panel')
**Name:** {{ $name }}  
**Email:** {{ $email }}  
**Topic:** {{ $topic }}
@endcomponent

---

## Message Details

{{ $message }}

---

*This message was recorded in the database.*

@endcomponent