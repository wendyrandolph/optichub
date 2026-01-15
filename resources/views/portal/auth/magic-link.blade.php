@extends('layouts.portal')

@section('title', 'Client Login')

@section('content')
    <div class="max-w-md mx-auto py-10">
        <div class="oh-card border border-border-default/70 shadow-card">
            <div class="space-y-2">
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Client portal</p>
                <h1 class="text-2xl font-semibold text-text-base">Sign in</h1>
                <p class="text-sm text-text-subtle">We’ll email you a secure sign-in link.</p>
            </div>

            @if (session('status'))
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('portal.magic.send') }}" class="mt-6 space-y-4">
                @csrf
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" class="oh-input" required>
                    @error('email')
                        <span class="text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <div class="flex justify-end">
                    <button type="submit" class="oh-btn oh-btn--primary">Send magic link</button>
                </div>
            </form>
        </div>
    </div>
@endsection
