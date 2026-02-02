@extends('layouts.admin')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-text-base">Email settings</h1>
            <p class="text-sm text-text-subtle">Configure SMTP for outbound transactional emails.</p>
        </div>

        @if (session('success'))
            <div class="oh-card p-3 text-sm text-green-700 bg-green-50 border border-green-100">
                {{ session('success') }}
            </div>
        @endif

        <form method="post" action="{{ route('admin.settings.email.update') }}" class="oh-card p-6 space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="oh-label" for="mailer">Mailer</label>
                    <input id="mailer" name="mailer" class="oh-input w-full" value="{{ old('mailer', $settings->mailer ?? 'smtp') }}">
                </div>
                <div>
                    <label class="oh-label" for="host">Host</label>
                    <input id="host" name="host" class="oh-input w-full" value="{{ old('host', $settings->host ?? '') }}">
                </div>
                <div>
                    <label class="oh-label" for="port">Port</label>
                    <input id="port" name="port" type="number" class="oh-input w-full" value="{{ old('port', $settings->port ?? '') }}">
                </div>
                <div>
                    <label class="oh-label" for="encryption">Encryption</label>
                    <input id="encryption" name="encryption" class="oh-input w-full" value="{{ old('encryption', $settings->encryption ?? '') }}">
                </div>
                <div>
                    <label class="oh-label" for="username">Username</label>
                    <input id="username" name="username" class="oh-input w-full" value="{{ old('username', $settings->username ?? '') }}">
                </div>
                <div>
                    <label class="oh-label" for="password">Password</label>
                    <input id="password" name="password" type="password" class="oh-input w-full" autocomplete="new-password">
                    <p class="text-xs text-text-subtle mt-1">Leave blank to keep current password.</p>
                </div>
                <div>
                    <label class="oh-label" for="from_address">From address</label>
                    <input id="from_address" name="from_address" class="oh-input w-full" value="{{ old('from_address', $settings->from_address ?? config('mail.from.address')) }}">
                </div>
                <div>
                    <label class="oh-label" for="from_name">From name</label>
                    <input id="from_name" name="from_name" class="oh-input w-full" value="{{ old('from_name', $settings->from_name ?? config('mail.from.name')) }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="oh-label" for="reply_to">Reply-to (optional)</label>
                    <input id="reply_to" name="reply_to" class="oh-input w-full" value="{{ old('reply_to', $settings->reply_to ?? '') }}">
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button class="oh-btn oh-btn--primary" type="submit">Save settings</button>
            </div>
        </form>

        <form method="post" action="{{ route('admin.settings.email.test') }}" class="oh-card p-6 space-y-4">
            @csrf
            <div>
                <label class="oh-label" for="test_email">Send test email to</label>
                <input id="test_email" name="test_email" type="email" class="oh-input w-full" required>
            </div>
            <div class="flex justify-end">
                <button class="oh-btn oh-btn--ghost" type="submit">Send test email</button>
            </div>
        </form>
    </div>
@endsection
