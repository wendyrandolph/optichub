@extends('layouts.app')

@section('title', 'Brand colors')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('onboarding._steps', ['current' => 2])

        <div class="rounded-xl bg-surface-card/80 border border-border-default/60 p-6 shadow-sm">
            <h1 class="text-2xl font-semibold text-text-base mb-1">Set your brand colors</h1>
            <p class="text-sm text-text-subtle mb-4">
                These colors will be used for buttons, highlights, and client-facing areas like the portal and invoices.
            </p>

            <form method="POST" action="{{ route('onboarding.brand.store') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Primary color</span>
                        <input type="color" name="primary_color" value="{{ old('primary_color', $primary_color) }}"
                            class="h-10 w-16 rounded-md border border-border-default bg-surface-card">
                        <span class="text-[11px] text-text-subtle">
                            Main buttons & key highlights.
                        </span>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Secondary color</span>
                        <input type="color" name="secondary_color" value="{{ old('secondary_color', $secondary_color) }}"
                            class="h-10 w-16 rounded-md border border-border-default bg-surface-card">
                        <span class="text-[11px] text-text-subtle">
                            Pills, badges, and accents.
                        </span>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Accent color</span>
                        <input type="color" name="accent_color" value="{{ old('accent_color', $accent_color) }}"
                            class="h-10 w-16 rounded-md border border-border-default bg-surface-card">
                        <span class="text-[11px] text-text-subtle">
                            Warnings, highlights, and emphasis.
                        </span>
                    </label>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-border-default/60 mt-4">
                    <button type="submit" class="btn btn--primary text-sm bg-[rgb(var(--ui-primary))] hover:bg-[rgb(var(--ui-primary-hover))] text-white border border-transparent">
                        Save & continue
                    </button>
                    <a href="{{ route('onboarding.logo') }}" class="text-sm text-text-subtle hover:text-text-base">
                        Skip for now
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
