@extends('layouts.portal')

@section('title', 'Link Expired')

@section('content')
    <div class="max-w-md mx-auto py-10">
        <div class="oh-card border border-border-default/70 shadow-card space-y-4">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Client portal</p>
                <h1 class="text-2xl font-semibold text-text-base">Link expired</h1>
                <p class="text-sm text-text-subtle">Request a new sign-in link to continue.</p>
            </div>

            <form method="POST" action="{{ route('portal.magic.send') }}" class="space-y-4">
                @csrf
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" class="oh-input" required>
                    @error('email')
                        <span class="text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <div class="flex items-center justify-between">
                    <a href="{{ route('portal.login') }}" class="text-sm text-[rgb(var(--brand-primary))] hover:underline">
                        Back to sign in
                    </a>
                    <button type="submit" class="oh-btn oh-btn--primary">Send new link</button>
                </div>
            </form>
        </div>
    </div>
@endsection
