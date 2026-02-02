@extends('layouts.marketing')

@php
    $productName = 'Renlo';
@endphp

@section('title', "About — {$productName}")

@push('head')
    <link rel="canonical" href="https://yourdomain.com/about">
    <meta name="description" content="Why Renlo exists, how it’s built, and what guides the work.">
    <meta property="og:title" content="About – Renlo">
    <meta property="og:description" content="Why Renlo exists, how it’s built, and what guides the work.">
    <meta property="og:image" content="https://yourdomain.com/og/about.jpg">
@endpush

@section('content')
    <section id="about-hero" class="section">
        <div class="container">
            <div class="grid gap-8 lg:grid-cols-[1.2fr,0.8fr] lg:items-start">
                {{-- Left: hero copy --}}
                <div class="space-y-4">
                    <p class="eyebrow">About {{ $productName }}</p>
                    <h1 class="h2">Built for people who run client work every day.</h1>
                    <p class="copy max-w-2xl">
                        {{ $productName }} keeps service work structured—whether you’re managing projects, schedules, and
                        billing
                        while still doing the work yourself. It’s designed to reduce friction, keep clients informed, and
                        make
                        the next step obvious.
                    </p>

                    <div class="flex flex-wrap gap-2 pt-2">
                        <span class="oh-pill oh-pill--muted text-[11px]">Projects + tasks</span>
                        <span class="oh-pill oh-pill--muted text-[11px]">Client portal</span>
                        <span class="oh-pill oh-pill--muted text-[11px]">Billing</span>
                        <span class="oh-pill oh-pill--muted text-[11px]">Scheduling</span>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <a class="oh-btn oh-btn--primary" href="{{ route('marketing.features') }}">Explore features</a>
                        <a class="oh-btn oh-btn--ghost" href="{{ route('contact.form') }}">Contact</a>
                    </div>
                </div>

                {{-- Right: principles card --}}
                <aside class="oh-card p-6 space-y-4">
                    <div class="space-y-1">
                        <div class="text-xs uppercase tracking-[0.28em] text-text-subtle">What {{ $productName }} optimizes
                            for</div>
                        <div class="text-lg font-semibold text-text-base">A workspace that stays readable.</div>
                    </div>

                    <ul class="space-y-3 text-sm text-text-subtle">
                        <li class="flex gap-3">
                            <span class="mt-2 h-2 w-2 rounded-full bg-[rgb(var(--ui-primary))]"></span>
                            <span><strong class="text-text-base">Clarity</strong> — projects, updates, and billing stay
                                connected.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-2 h-2 w-2 rounded-full bg-[rgb(var(--ui-primary))]"></span>
                            <span><strong class="text-text-base">Momentum</strong> — next steps are visible, due dates don’t
                                disappear.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-2 h-2 w-2 rounded-full bg-[rgb(var(--ui-primary))]"></span>
                            <span><strong class="text-text-base">Trust</strong> — straightforward pricing and responsible
                                handling of data.</span>
                        </li>
                    </ul>
                </aside>
            </div>
        </div>
    </section>

    {{-- Mission + Story --}}
    <section class="section">
        <div class="container">
            <div class="grid gap-6 lg:grid-cols-2">
                <article class="oh-card p-6 space-y-3">
                    <h2 class="h3">Our mission</h2>
                    <p class="copy">
                        {{ $productName }} is built to bring structure to business management for teams who deliver client
                        work:
                        projects, jobs, schedules, and billing—often at the same time.
                    </p>
                    <p class="copy">
                        The goal is simple: fewer moving parts, less hunting for context, and a more consistent experience
                        for your clients.
                    </p>
                </article>

                <article class="oh-card p-6 space-y-3">
                    <h2 class="h3">The story</h2>
                    <p class="copy">
                        {{ $productName }} started as a search for a straightforward way to run client work in one place.
                        Existing tools felt scattered, noisy, and shaped for workflows that didn’t match real service work.
                    </p>
                    <p class="copy">
                        So we focused on essentials: clear navigation, linked records, and tools that stay in sync—built to
                        support
                        the long haul, not the next trend.
                    </p>
                </article>
            </div>
        </div>
    </section>

    {{-- Founder note --}}
    <section class="section" id="founder-note">
        <div class="container">
            <div class="oh-card p-6 lg:p-8">
                <div class="grid gap-8 lg:grid-cols-[1.2fr,0.8fr] lg:items-center">
                    <div class="space-y-4">
                        <div class="space-y-1">
                            <div class="text-xs uppercase tracking-[0.28em] text-text-subtle">A note from the founder</div>
                            <h2 class="h3">Built with responsibility in mind.</h2>
                        </div>

                        <p class="copy">
                            Building {{ $productName }} has been challenging and rewarding. It reflects what I believe a
                            trustworthy tool
                            should do: honor your time, your clients, and the work you’re responsible for.
                        </p>
                        <p class="copy">
                            My hope is that {{ $productName }} gives you room to focus on doing great work and caring for
                            the people you serve.
                            Every detail has been built carefully and prayerfully for teams who depend on it.
                        </p>

                        <blockquote
                            class="rounded-xl border border-border-default bg-[rgba(var(--ui-surface-card),0.6)] p-4 text-sm text-text-subtle">
                            <div class="font-medium text-text-base">“For God is not the author of confusion, but of peace…”
                            </div>
                            <div class="mt-1">— 1 Corinthians 14:33 (KJV)</div>
                        </blockquote>

                        <div class="text-sm text-text-subtle">
                            <div class="font-semibold text-text-base">Wendy Causey</div>
                            <div>Founder, {{ $productName }}</div>
                        </div>
                    </div>

                    <div class="flex justify-center lg:justify-end">
                        <figure class="w-full max-w-sm">
                            <div class="oh-card p-3">
                                <img class="w-full rounded-xl object-cover"
                                    src="{{ asset('images/founder-placeholder.jpg') }}"
                                    alt="Founder of {{ $productName }}" loading="lazy">
                            </div>
                            <figcaption class="mt-3 text-xs text-text-subtle">
                                Founder of {{ $productName }}
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Values --}}
    <section class="section" id="values">
        <div class="container">
            <header class="max-w-2xl space-y-2">
                <p class="eyebrow">What guides the work</p>
                <h2 class="h2">Principles that keep the product focused.</h2>
                <p class="copy">
                    We build for teams doing real client work—so the tools stay practical, predictable, and easy to operate.
                </p>
            </header>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article class="oh-card p-6 space-y-2">
                    <div class="text-xs uppercase tracking-[0.24em] text-text-subtle">Purpose</div>
                    <h3 class="text-lg font-semibold text-text-base">Serve real work</h3>
                    <p class="text-sm text-text-subtle">Build tools for real clients, real jobs, and the teams delivering
                        them.</p>
                </article>

                <article class="oh-card p-6 space-y-2">
                    <div class="text-xs uppercase tracking-[0.24em] text-text-subtle">Integrity</div>
                    <h3 class="text-lg font-semibold text-text-base">No surprises</h3>
                    <p class="text-sm text-text-subtle">Clear pricing, honest roadmaps, and responsible handling of customer
                        data.</p>
                </article>

                <article class="oh-card p-6 space-y-2">
                    <div class="text-xs uppercase tracking-[0.24em] text-text-subtle">Simplicity</div>
                    <h3 class="text-lg font-semibold text-text-base">Only what’s needed</h3>
                    <p class="text-sm text-text-subtle">Keep the workflow readable so teams move faster without fighting the
                        tool.</p>
                </article>

                <article class="oh-card p-6 space-y-2">
                    <div class="text-xs uppercase tracking-[0.24em] text-text-subtle">Faith</div>
                    <h3 class="text-lg font-semibold text-text-base">Build for the long view</h3>
                    <p class="text-sm text-text-subtle">Serve with excellence, extend grace, and stay committed over time.
                    </p>
                </article>

                <article class="oh-card p-6 space-y-2">
                    <div class="text-xs uppercase tracking-[0.24em] text-text-subtle">Gratitude</div>
                    <h3 class="text-lg font-semibold text-text-base">Earn trust daily</h3>
                    <p class="text-sm text-text-subtle">Stay humble and appreciate the customers who run their work on
                        {{ $productName }}.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- How it’s built --}}
    <section class="section" id="how-its-built">
        <div class="container">
            <div class="oh-card p-6 lg:p-8">
                <div class="grid gap-8 lg:grid-cols-[1.1fr,0.9fr] lg:items-start">
                    <div class="space-y-3">
                        <h2 class="h3">How {{ $productName }} is built</h2>
                        <p class="copy">
                            We keep development focused on real workflows and steady improvements—so the product stays
                            dependable.
                        </p>

                        <ul class="mt-4 space-y-2 text-sm text-text-subtle">
                            <li class="flex gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span>Fast, thoughtful updates (not noisy releases)</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span>Customer-led roadmap grounded in real feedback</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span>Privacy-first foundation: encryption, role-based access, Stripe for payments</span>
                            </li>
                        </ul>
                    </div>

                    <aside class="space-y-3">
                        <div class="text-xs uppercase tracking-[0.24em] text-text-subtle">Trust cues</div>
                        <div class="flex flex-wrap gap-2">
                            <span class="oh-pill oh-pill--muted text-[11px]">Stripe payments</span>
                            <span class="oh-pill oh-pill--muted text-[11px]">Role-based access</span>
                            <span class="oh-pill oh-pill--muted text-[11px]">Export anytime</span>
                            <span class="oh-pill oh-pill--muted text-[11px]">Audit-friendly records</span>
                        </div>

                        <div
                            class="rounded-xl border border-border-default bg-[rgba(var(--ui-surface-card),0.6)] p-4 text-sm text-text-subtle">
                            If you’re evaluating {{ $productName }} for your business, reach out with your workflow—we’ll
                            help you
                            confirm fit quickly.
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a class="oh-btn oh-btn--primary" href="{{ route('trial.show') }}">Start free trial</a>
                            <a class="oh-btn oh-btn--ghost" href="{{ route('contact.form') }}">Talk to us</a>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="section cta" id="about-cta">
        <div class="container">
            <div class="oh-card p-6 lg:p-8">
                <div class="grid gap-6 lg:grid-cols-[1.2fr,0.8fr] lg:items-center">
                    <div class="space-y-2">
                        <h2 class="h2">Explore the tools at your pace.</h2>
                        <p class="copy">
                            See how {{ $productName }} keeps projects, updates, and billing connected—then start your
                            trial when you’re ready.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 lg:justify-end">
                        <a class="oh-btn oh-btn--primary" href="{{ route('marketing.features') }}">Explore features</a>
                        <a class="oh-btn oh-btn--ghost" href="{{ route('contact.form') }}">Say hello</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
