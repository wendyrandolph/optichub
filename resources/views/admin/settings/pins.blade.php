@extends('layouts.app')

@section('title', 'Pinned shortcuts')

@section('content')
    <div class="oh-page space-y-6 max-w-3xl mx-auto">
        <header class="space-y-1">
            <p class="text-[11px] uppercase tracking-[0.08em] text-text-subtle">Settings</p>
            <h1 class="text-2xl font-semibold text-text-base">Pinned shortcuts</h1>
            <p class="text-sm text-text-subtle">Pick up to four shortcuts to show at the top of your sidebar.</p>
        </header>

        @if (session('flash_success'))
            <div class="oh-card border border-green-200 bg-green-50 text-green-800 text-sm px-3 py-2">
                {{ session('flash_success') }}
            </div>
        @endif

        <section class="oh-card space-y-4">
            <form method="POST" action="{{ route('tenant.settings.pins.update', ['tenant' => $tenantId]) }}"
                class="space-y-4">
                @csrf
                <div class="grid gap-3">
                    @for ($i = 0; $i < 4; $i++)
                        @php
                            $val = $current[$i] ?? '';
                        @endphp
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Pin {{ $i + 1 }}</span>
                            <select name="pins[]" class="oh-select h-10">
                                <option value="">(None)</option>
                                @foreach ($options as $route => $label)
                                    <option value="{{ $route }}" @selected($val === $route)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endfor
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <a href="{{ route('tenant.settings.index', ['tenant' => $tenantId]) }}" class="oh-btn">Cancel</a>
                    <button type="submit" class="oh-btn oh-btn--primary">Save</button>
                </div>
            </form>
        </section>
    </div>
@endsection
