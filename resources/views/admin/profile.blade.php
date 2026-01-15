@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="mb-6">
            <p class="text-[11px] uppercase tracking-wider text-text-subtle">Account</p>
            <h1 class="text-2xl font-semibold text-text-base">My Profile</h1>
            <p class="text-sm text-text-subtle mt-1">Update your personal details and login email.</p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.profile.update') }}"
            class="rounded-2xl border border-border-default bg-surface-card shadow-card overflow-hidden">
            @csrf

            <div class="px-6 py-5 border-b border-border-default/70">
                <h2 class="text-base font-semibold text-text-base">Personal Info</h2>
                <p class="text-sm text-text-subtle">This is shown in the header and used for notifications.</p>
            </div>

            <div class="px-6 py-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">First name</span>
                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3">
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Last name</span>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                            class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3">
                    </label>
                </div>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Username</span>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}"
                        class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3">
                    <span class="text-[11px] text-text-subtle">Shown in system activity and for login.</span>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Email</span>
                    <input type="email" name="email" required value="{{ old('email', $user->email) }}"
                        class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3">
                </label>

            </div>

            <div class="px-6 py-5 border-t border-border-default/70">
                <h2 class="text-base font-semibold text-text-base">Password</h2>
                <p class="text-sm text-text-subtle">Leave blank to keep your current password.</p>
            </div>

            <div class="px-6 py-6 space-y-4">
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">New password</span>
                    <input type="password" name="password"
                        class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Confirm new password</span>
                    <input type="password" name="password_confirmation"
                        class="h-10 rounded-lg bg-surface-card text-text-base border border-border-default px-3">
                </label>
            </div>

            <div class="px-6 py-4 border-t border-border-default/70 bg-surface-muted flex justify-end gap-3">
                <a href="{{ url()->previous() }}" class="text-sm font-medium text-text-subtle hover:text-text-base">Cancel</a>
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary/90">
                    Save changes
                </button>
            </div>
        </form>
    </div>
@endsection
