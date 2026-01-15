@extends('layouts.app')

@section('title', 'Upload your logo')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('onboarding._steps', ['current' => 3])

        <div class="rounded-xl bg-surface-card/80 border border-border-default/60 p-6 shadow-sm">
            <h1 class="text-2xl font-semibold text-text-base mb-1">Add your logo</h1>
            <p class="text-sm text-text-subtle mb-4">
                We’ll use this on your invoices and client portal. You can change it anytime.
            </p>

            <form method="POST" action="{{ route('onboarding.logo.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                @if ($tenant && $tenant->logo_path)
                    <div class="mb-3">
                        <p class="text-xs text-text-subtle mb-1">Current logo</p>
                        <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="Current logo" class="h-12">
                    </div>
                @endif

                <div class="mt-4">
                    <label for="logo" class="block text-sm font-medium text-[rgb(var(--text))]">
                        Upload logo <span class="text-[rgb(var(--text-subtle))]">(PNG or SVG)</span>
                    </label>

                    <div class="mt-2 flex items-center gap-3">
                        <input id="logo" name="logo" type="file" accept=".png,.svg,image/png,image/svg+xml"
                            class="sr-only">

                        <label for="logo"
                            class="inline-flex items-center rounded-md border border-[rgb(var(--border))] bg-[rgb(var(--surface))] px-3 py-2 text-sm font-medium text-[rgb(var(--text))] shadow-sm hover:bg-[rgb(var(--surface-muted))] focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgba(var(--brand-primary),0.35)] cursor-pointer">
                            Choose file
                        </label>

                        <span id="logoFilename" class="text-sm text-[rgb(var(--text-subtle))]">
                            No file chosen
                        </span>
                    </div>

                    <p class="mt-2 text-xs text-[rgb(var(--text-subtle))]">
                        Recommended: 512px wide • Transparent background
                    </p>

                    @error('logo')
                        <p class="mt-2 text-sm text-[rgb(var(--status-danger))]">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-border-default/60 mt-4">
                    <button type="submit"
                        class="btn btn--primary text-sm bg-[rgb(var(--ui-primary))] hover:bg-[rgb(var(--ui-primary-hover))] text-white border border-transparent">
                        Save & continue
                    </button>
                    <a href="{{ route('onboarding.client') }}" class="text-sm text-text-subtle hover:text-text-base">
                        Skip for now
                    </a>
                </div>
            </form>
        </div>
    </div>
@push('head')
    <script>
        (function() {
            const input = document.getElementById('logo');
            const label = document.getElementById('logoFilename');
            if (!input || !label) return;

            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                label.textContent = file ? file.name : 'No file chosen';
            });
        })();
    </script>
@endpush
@endsection
