@extends('layouts.portal')

@section('title', 'My Account')

@section('content')
    @php
        $fullName = trim(($contact->firstName ?? '') . ' ' . ($contact->lastName ?? ''));
    @endphp

    <div class="oh-page space-y-6">
        <div>
            <p class="text-[11px] uppercase tracking-wider text-text-subtle">Portal</p>
            <h1 class="text-2xl font-semibold text-text-base">My account</h1>
            <p class="text-sm text-text-subtle">Update your contact details and password.</p>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="oh-card border border-border-default/70 shadow-card">
                <h2 class="text-sm font-semibold text-text-base mb-2">Profile details</h2>
                <p class="text-xs text-text-subtle mb-4">This is the info your provider sees for your account.</p>

                <form method="POST" action="{{ route('portal.settings.profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">First name</span>
                            <input type="text" name="first_name" value="{{ old('first_name', $contact->firstName ?? '') }}"
                                class="oh-input" required>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Last name</span>
                            <input type="text" name="last_name" value="{{ old('last_name', $contact->lastName ?? '') }}"
                                class="oh-input" required>
                        </label>
                    </div>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Email</span>
                        <input type="email" name="email" value="{{ old('email', $contact->email ?? '') }}" class="oh-input"
                            required>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Phone</span>
                        <input type="text" name="phone" value="{{ old('phone', $contact->phone ?? '') }}" class="oh-input">
                    </label>

                    <div class="flex justify-end">
                        <button type="submit" class="oh-btn oh-btn--primary">Save profile</button>
                    </div>
                </form>
            </div>

            <div class="oh-card border border-border-default/70 shadow-card">
                <h2 class="text-sm font-semibold text-text-base mb-2">Change password</h2>
                <p class="text-xs text-text-subtle mb-4">Choose a strong password you haven't used before.</p>

                <form method="POST" action="{{ route('portal.settings.password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Current password</span>
                        <input type="password" name="current_password" class="oh-input" required>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">New password</span>
                        <input type="password" name="password" class="oh-input" required>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Confirm new password</span>
                        <input type="password" name="password_confirmation" class="oh-input" required>
                    </label>

                    <div class="flex justify-end">
                        <button type="submit" class="oh-btn oh-btn--primary">Update password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
