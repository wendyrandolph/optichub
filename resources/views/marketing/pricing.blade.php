@extends('layouts.marketing')
@section('title', 'Predictable pricing — Renlo')

@push('head')
    <link rel="canonical" href="{{ url('/pricing') }}">
    <meta name="description" content="Renlo pricing — One plan, full workspace.">
@endpush

@section('content')
    <section class="py-16 bg-slate-50" id="pricing-hero">
        <div class="container max-w-6xl">
            <div class="grid gap-10 lg:grid-cols-2">
                <div class="space-y-4">
                    <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Predictable pricing</p>
                    <h1 class="text-4xl font-semibold text-text-base leading-tight">One plan. Full-featured workspace.</h1>
                    <p class="text-base text-text-subtle leading-relaxed">
                        Renlo keeps client work, projects, billing, client portals, and messaging connected inside one workspace. Everything stays tied together so you can see progress, approvals, and payments without hopping between tabs.
                    </p>
                    <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">
                        14-day trial · No credit card required · Cancel anytime
                    </p>
                </div>
                <div class="oh-card p-6 space-y-6">
                    <div class="flex items-center justify-between">
                        <span class="oh-pill oh-pill--success text-[11px]">Single workspace plan</span>
                        <span class="text-xs uppercase tracking-[0.3em] text-text-subtle">All features included</span>
                    </div>
                    <div>
                        <div class="flex items-end gap-2">
                            <span class="text-4xl font-semibold text-text-base">$49</span>
                            <span class="text-sm text-text-subtle">/ month</span>
                        </div>
                        <p class="text-sm text-text-subtle">Unlimited clients, projects, invoices, and portals per workspace.</p>
                    </div>
                    <ul class="space-y-2 text-sm text-text-subtle list-disc list-inside">
                        <li>Projects, tasks, and billing stay tied to the same job.</li>
                        <li>Client portal + messaging keep approvals transparent.</li>
                        <li>Stripe payments, reminders, and receipts without add-ons.</li>
                    </ul>
                    <div class="flex flex-col gap-3 items-stretch sm:flex-row sm:justify-between sm:items-center">
                        <a class="oh-btn oh-btn--primary w-full sm:w-auto" href="{{ url('/trial/start') }}">Start your 14-day free trial</a>
                        <a class="oh-btn oh-btn--ghost w-full sm:w-auto" href="{{ route('marketing.home') }}#demo">See a live workspace</a>
                    </div>
                    <p class="text-xs uppercase tracking-[0.3em] text-text-subtle text-center">
                        Secure Stripe checkout · Export anytime
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-white" id="whats-included">
        <div class="container max-w-6xl space-y-8">
            <div class="space-y-3 text-center max-w-2xl mx-auto">
                <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Everything is included</p>
                <h2 class="text-3xl font-semibold text-text-base">One plan. Every Renlo workflow.</h2>
                <p class="text-base text-text-subtle">
                    No feature gates, no add-ons—just the full Renlo toolkit for managing clients, projects, billing, messaging, and scheduling.
                </p>
            </div>
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['title' => 'Clients & Contacts', 'body' => 'Client records hold notes, files, tags, and linked projects in one place.'],
                    ['title' => 'Projects & Tasks', 'body' => 'Stage-based work, templates, and filters keep progress visible.'],
                    ['title' => 'Invoices & Payments', 'body' => 'Estimates convert to invoices with Stripe checkout and payment tracking.'],
                    ['title' => 'Client Portal', 'body' => 'Share updates, files, and approvals inside a branded portal.'],
                    ['title' => 'Templates', 'body' => 'Save reusable workflows with relative dates and reminders.'],
                    ['title' => 'Scheduling', 'body' => 'Milestones, meetings, and reminders live next to projects.' ],
                ] as $item)
                    <article class="oh-card p-4 space-y-2">
                        <div class="oh-pill oh-pill--muted text-[11px]">{{ $item['title'] }}</div>
                        <p class="text-sm text-text-subtle">{{ $item['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 bg-slate-50">
        <div class="container max-w-5xl space-y-4">
            <article class="oh-card oh-card--muted p-6 space-y-3">
                <p class="text-sm text-text-base">“We stopped toggling between spreadsheets and email—Renlo shows projects, tasks, invoices, and client messages on the same screen.”</p>
                <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">— Jamie, Studio Owner</p>
            </article>
            <div class="flex flex-wrap gap-2 text-xs text-text-subtle justify-center">
                <span class="oh-pill oh-pill--muted">Encrypted storage</span>
                <span class="oh-pill oh-pill--muted">Stripe payments</span>
                <span class="oh-pill oh-pill--muted">Role-based access</span>
                <span class="oh-pill oh-pill--muted">Export anytime</span>
            </div>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="container max-w-5xl">
            <article class="oh-card p-6 space-y-4">
                <h2 class="text-2xl font-semibold text-text-base">Pricing FAQ</h2>
                <div class="space-y-3">
                    <details class="faq"><summary>Do I need a credit card for the trial?</summary><p>No. Try the full workspace before upgrading.</p></details>
                    <details class="faq"><summary>Can I cancel anytime?</summary><p>Yes — month-to-month billing with no obligations.</p></details>
                    <details class="faq"><summary>Is my data secure?</summary><p>We use encrypted storage, role-based permissions, and Stripe.</p></details>
                    <details class="faq"><summary>Do you offer team plans?</summary><p>Team workspaces are coming soon; join the waitlist.</p></details>
                </div>
            </article>
        </div>
    </section>

    <section class="py-16 bg-slate-50">
        <div class="container max-w-6xl text-center space-y-4">
            <h2 class="text-3xl font-semibold text-text-base">Start predictable work today.</h2>
            <p class="text-base text-text-subtle">Set up clients, templates, and invoices inside Renlo and see what a connected workflow feels like.</p>
            <div class="flex flex-col gap-4 items-center sm:flex-row sm:justify-center">
                <a class="oh-btn oh-btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
                <a class="oh-btn oh-btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
            </div>
        </div>
    </section>
@endsection
