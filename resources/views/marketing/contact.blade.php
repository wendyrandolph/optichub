@extends('layouts.marketing')
@section('title', 'Renlo - Contact')

@section('content')
    @php
        $form = $formData ?? [];
        $started = $formStartedAt ?? now()->timestamp;
    @endphp

    <section class="section section--aperture" id="contact-hero">
        <div class="container">
            <p class="eyebrow">Contact</p>
            <h1 class="h2">Let’s talk.</h1>
            <p class="copy max-w-2xl">
                Questions, feedback, or a workflow you want to run through—send a note and we’ll point you in the right
                direction.
            </p>
        </div>
    </section>

    <section class="section" id="contact-form">
        <div class="container">
            <div class="grid gap-8 lg:grid-cols-[0.9fr,1.1fr] lg:items-start">

                {{-- Left column: guidance + alternate paths --}}
                <aside class="space-y-4">
                    <div class="oh-card p-6 space-y-3">
                        <div class="text-xs uppercase tracking-[0.28em] text-text-subtle">Before you send</div>
                        <div class="text-lg font-semibold text-text-base">A couple quick notes</div>
                        <ul class="space-y-2 text-sm text-text-subtle">
                            <li class="flex gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-[rgb(var(--ui-primary))]"></span>
                                <span>Replies typically within <strong class="text-text-base">1–2 business
                                        days</strong>.</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-[rgb(var(--ui-primary))]"></span>
                                <span>If it’s billing, pick the <strong class="text-text-base">Billing</strong> topic so it
                                    routes faster.</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-[rgb(var(--ui-primary))]"></span>
                                <span>We won’t share your email—ever.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="oh-card p-6 space-y-3">
                        <div class="text-xs uppercase tracking-[0.28em] text-text-subtle">Prefer to browse?</div>
                        <div class="text-lg font-semibold text-text-base">Start here</div>
                        <div class="flex flex-wrap gap-3">
                            <a class="oh-btn oh-btn--ghost" href="{{ route('marketing.features') }}">Explore features</a>
                            <a class="oh-btn oh-btn--ghost" href="{{ url('/faq') }}">Read FAQ</a>
                        </div>
                    </div>
                </aside>

                {{-- Right column: form --}}
                <div class="space-y-4">

                    {{-- Success / status --}}
                    @if (session('status'))
                        <div class="oh-card p-4 border border-emerald-200 bg-emerald-50 text-emerald-900">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Validation errors --}}
                    @if ($errors->any())
                        <div class="oh-card p-4 border border-red-200 bg-red-50 text-red-900">
                            <div class="font-semibold mb-2">Please fix the following:</div>
                            <ul class="list-disc pl-5 space-y-1 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.submit') }}" class="oh-card p-6 lg:p-8 space-y-5"
                        novalidate>
                        @csrf
                        <input type="hidden" name="started_at" value="{{ (int) $started }}">

                        {{-- Honeypot (keep hidden) --}}
                        <div class="sr-only" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="space-y-1">
                            <div class="text-xs uppercase tracking-[0.28em] text-text-subtle">Send a message</div>
                            <div class="text-lg font-semibold text-text-base">We read every note.</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label for="name" class="text-sm font-medium text-text-base">Name <span
                                        class="text-text-subtle">*</span></label>
                                <input id="name" name="name" type="text" autocomplete="name" required
                                    class="oh-input w-full h-10" value="{{ old('name', $form['name'] ?? '') }}">
                            </div>

                            <div class="space-y-1">
                                <label for="email" class="text-sm font-medium text-text-base">Email <span
                                        class="text-text-subtle">*</span></label>
                                <input id="email" name="email" type="email" autocomplete="email" required
                                    class="oh-input w-full h-10" value="{{ old('email', $form['email'] ?? '') }}">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label for="topic" class="text-sm font-medium text-text-base">Topic</label>
                            @php $topic = old('topic', $form['topic'] ?? ''); @endphp
                            <select id="topic" name="topic" class="oh-select w-full h-10">
                                <option value="" {{ $topic === '' ? 'selected' : '' }}>General</option>
                                <option value="billing" {{ $topic === 'billing' ? 'selected' : '' }}>Billing</option>
                                <option value="feature" {{ $topic === 'feature' ? 'selected' : '' }}>Feature request
                                </option>
                                <option value="support" {{ $topic === 'support' ? 'selected' : '' }}>Support</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label for="message" class="text-sm font-medium text-text-base">Message <span
                                    class="text-text-subtle">*</span></label>
                            <textarea id="message" name="message" rows="7" required class="oh-textarea w-full">{{ old('message', $form['message'] ?? '') }}</textarea>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <button class="oh-btn oh-btn--primary" type="submit">Send message</button>
                            <a class="oh-btn oh-btn--ghost" href="{{ route('marketing.features') }}">Explore features</a>
                        </div>

                        <p class="text-sm text-text-subtle">
                            We’ll never share your email. Replies typically within 1–2 business days.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
