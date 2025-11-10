@extends('layouts.app')

@section('title', 'Compose Email')

@section('content')
    <div class="max-w-2xl mx-auto px-4 py-8">
        <header class="mb-4">
            <h1 class="text-2xl font-semibold text-text-base">Compose Email</h1>
            <p class="text-sm text-text-subtle">Send an email and automatically log it to the timeline.</p>
        </header>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-red-700">
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.emails.store', ['tenant' => $tenant]) }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-text-base mb-1">To *</label>
                <input name="recipient_email" type="email" required value="{{ old('recipient_email') }}"
                    class="w-full h-10 rounded-lg border border-border-default bg-surface-card px-3 text-sm focus:ring-brand-primary focus:border-brand-primary" />
            </div>

            <div>
                <label class="block text-sm font-medium text-text-base mb-1">Subject *</label>
                <input name="subject" type="text" required value="{{ old('subject') }}"
                    class="w-full h-10 rounded-lg border border-border-default bg-surface-card px-3 text-sm focus:ring-brand-primary focus:border-brand-primary" />
            </div>

            <div>
                <label class="block text-sm font-medium text-text-base mb-1">Body</label>
                <textarea name="body" rows="8"
                    class="w-full rounded-lg border border-border-default bg-surface-card px-3 py-2 text-sm focus:ring-brand-primary focus:border-brand-primary">{{ old('body') }}</textarea>
                <p class="mt-2 text-xs text-text-subtle">HTML supported.</p>
            </div>

            <div class="pt-2 flex items-center gap-3">
                <button type="submit"
                    class="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm font-medium text-white bg-gradient-to-b from-brand-primary to-blue-700 hover:brightness-110 transition">
                    Send Email
                </button>
                <a href="{{ route('tenant.emails.index', ['tenant' => $tenant]) }}"
                    class="h-10 px-4 rounded-lg bg-surface-card text-text-base text-sm">Cancel</a>
            </div>
        </form>
    </div>
@endsection
