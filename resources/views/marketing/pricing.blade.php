@extends('layouts.marketing')
@section('title', 'Simple pricing — Renlo')

@push('head')
    <link rel="canonical" href="{{ url('/pricing') }}">
    <meta name="description" content="One clear plan. Full access. 14-day free trial — no credit card required.">
    <meta property="og:title" content="Simple pricing — Renlo">
    <meta property="og:description" content="One clear plan. Full access. 14-day free trial.">
@endpush

@section('content')
    {{-- HERO + PRICING CARD WRAPPER --}}
    <div class="pricing-page">
        <section class="section section--aperture pricing-hero">
            <div class="container">
                <div class="pricing-hero__frame">
                    <div class="pricing-hero__inner">
                        <header class="pricing-hero__copy">
                            <p class="eyebrow">Simple pricing. No contracts.</p>
                            <h1 class="h2">One clear plan for solo operators and small studios.</h1>

                            <p class="copy"> Renlo keeps client work, tasks, scheduling, and billing in one place—whether
                                you run a studio, a shop, or a service route.

                                Full access to every core feature.
                                <br><br>14-day free trial — no credit card required to explore.
                            </p>
                        </header>

                        {{-- MAIN PRICING CARD (same styling as home) --}}
                        <div class="pricing-hero__card">
                            <header class="pricing-hero__header">
                                <span class="pricing-hero__badge">Solo workspace</span>

                                <div class="pricing-hero__amount">
                                    <div class="pricing-hero__price-row">
                                        <span class="pricing-hero__currency">$</span>
                                        <span class="pricing-hero__price">49</span>
                                        <span class="pricing-hero__per">/month</span>
                                    </div>
                                    <p class="pricing-hero__subcopy">
                                        Full access to Renlo for your workspace. Cancel anytime.
                                    </p>
                                </div>
                            </header>

                            <div class="pricing-hero__body">
                                <div class="pricing-hero__col">
                                    <ul class="pricing-hero__list">
                                        <li>All core features unlocked — projects, clients, portal, billing.</li>
                                        <li>Month-to-month billing via Stripe. No contracts.</li>
                                        <li>Built for service businesses—designers, studios, and home services.</li>
                                    </ul>
                                </div>

                                <div class="pricing-hero__col">
                                    <ul class="pricing-hero__list">
                                        <li>14-day free trial — no credit card required to explore.</li>
                                        <li>Unlimited clients, projects, and invoices in your workspace.</li>
                                        <li>Client portal included — no extra add-ons.</li>
                                    </ul>
                                </div>
                            </div>

                            <footer class="pricing-hero__footer">
                                <div class="pricing-hero__cta-row">
                                    <a class="btn btn--primary btn--lg" href="{{ url('/trial/start') }}">
                                        Start your 14-day free trial
                                    </a>
                                    <a class="btn btn--ghost btn--lg" href="{{ route('marketing.home') }}#demo">
                                        Book a demo
                                    </a>
                                </div>
                                <p class="pricing-hero__meta">
                                    <i class="fa-solid fa-lock text-[11px] mr-1" aria-hidden="true"></i>
                                    Secure Stripe checkout. You keep your data if you ever leave.
                                </p>
                            </footer>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- WHAT’S INCLUDED (ICON GRID) --}}
        <section class="section section--soft" id="whats-included">
            <div class="container">
                <header class="pricing-included__header">
                    <p class="eyebrow">Everything is included</p>
                    <h2 class="h3">One plan. All the tools you’ve seen.</h2>
                    <p class="copy">
                        No feature gates, no per-feature add-ons. Every workspace gets the full Renlo toolkit.
                    </p>
                </header>

                <div class="pricing-included__grid">
                    <article class="card pricing-included__card">
                        <div class="pricing-included__icon pricing-included__icon--blue">
                            <i class="fa-solid fa-users" aria-hidden="true"></i>
                        </div>
                        <h3 class="h4">Clients & Contacts</h3>
                        <p class="copy">All client details, files, and history in one place.</p>
                    </article>

                    <article class="card pricing-included__card">
                        <div class="pricing-included__icon pricing-included__icon--purple">
                            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                        </div>
                        <h3 class="h4">Projects & Tasks</h3>
                        <p class="copy">Stages, due dates, and templates that keep work moving.</p>
                    </article>

                    <article class="card pricing-included__card">
                        <div class="pricing-included__icon pricing-included__icon--blue">
                            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                        </div>
                        <h3 class="h4">Calendar</h3>
                        <p class="copy">See milestones and meetings in one clean view.</p>
                    </article>

                    <article class="card pricing-included__card">
                        <div class="pricing-included__icon pricing-included__icon--purple">
                            <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                        </div>
                        <h3 class="h4">Invoices & Payments</h3>
                        <p class="copy">Estimates → invoices, paid status, Stripe checkout.</p>
                    </article>

                    <article class="card pricing-included__card">
                        <div class="pricing-included__icon pricing-included__icon--blue">
                            <i class="fa-solid fa-handshake" aria-hidden="true"></i>
                        </div>
                        <h3 class="h4">Client Portal</h3>
                        <p class="copy">Share updates, files, and approvals—without email chaos.</p>
                    </article>

                    <article class="card pricing-included__card">
                        <div class="pricing-included__icon pricing-included__icon--purple">
                            <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                        </div>
                        <h3 class="h4">Templates</h3>
                        <p class="copy">Start repeatable projects in minutes, not hours.</p>
                    </article>
                </div>
            </div>
        </section>

        {{-- SOCIAL PROOF SECTION --}}
        <section class="section section--white">
            <div class="container">
                <article class="card card--glass pricing-quote">
                    <p class="quote">
                        “We went from spreadsheet chaos and email back-and-forth to a clean pipeline
                        with clear next steps for every client. Renlo paid for itself in the first week.”
                    </p>
                    <span class="quote__meta">— Jamie, Studio Owner</span>
                </article>

                <div class="pill-list pill-list--center mt-4">
                    <span class="badge"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Encrypted at
                        rest</span>
                    <span class="badge"><i class="fa-brands fa-stripe" aria-hidden="true"></i> Stripe payments</span>
                    <span class="badge"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Role-based access</span>
                    <span class="badge"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Export
                        anytime</span>
                </div>
            </div>
        </section>

        {{-- FAQ IN A CARD --}}
        <section class="section section--soft">
            <div class="container">
                <article class="card pricing-faq">
                    <header class="pricing-faq__header">
                        <h2 class="h3">Pricing FAQ</h2>
                        <p class="copy">Short answers to common questions before you dive in.</p>
                    </header>

                    <div class="pricing-faq__grid">
                        <details class="faq">
                            <summary>Do I need a credit card for the trial?</summary>
                            <p>No. Try all core features first and upgrade when you’re ready.</p>
                        </details>

                        <details class="faq">
                            <summary>Can I cancel anytime?</summary>
                            <p>Yes — it’s month-to-month billing. Cancelling stops future charges.</p>
                        </details>

                        <details class="faq">
                            <summary>Is my data secure?</summary>
                            <p>Yes. We use encrypted storage, role-based permissions, and Stripe for payments.</p>
                        </details>

                        <details class="faq">
                            <summary>Do you offer team plans?</summary>
                            <p>Team workspaces are coming soon. You can join the waitlist during signup.</p>
                        </details>
                    </div>
                </article>
            </div>
        </section>

        {{-- FINAL CTA ON DARK GRADIENT --}}
        <section class="section pricing-cta">
            <div class="container pricing-cta__inner">
                <h2 class="h2">Start free. Get organized.</h2>
                <p class="copy">
                    Set up clients, pick a template, and send your first invoice — all in your 14-day free trial.
                </p>
                <div class="btn-row">
                    <a class="btn btn--primary btn--lg" href="{{ url('/trial/start') }}">Start Free Trial</a>
                    <a class="btn btn--ghost btn--lg btn--ghost-on-dark" href="{{ route('marketing.home') }}#demo">
                        Book a Demo
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection
