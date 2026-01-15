@php($productName = $productName ?? config('app.name', 'Renlo'))

<p>Hi {{ $user->first_name ?? 'there' }},</p>

<p>Use the secure link below to sign in to your {{ $productName }} client portal. This link expires in 7 days.</p>
<p>Please keep this link private and do not share it.</p>

<p>
    <a href="{{ $link }}">{{ $link }}</a>
</p>

<p>If you didn’t request this, you can safely ignore this email.</p>

<p>— {{ $productName }} Team</p>
