@extends('layouts.marketing')
@section('title', 'Renlo - FAQ')

@section('content')
    @php
        $productName = 'Renlo';
        $meta = [
            'title' => 'FAQ — Renlo',
            'description' => 'Answers about the free trial, pricing, client portal, templates, data, and support.',
            'canonical' => 'https://portal.causeywebsolutions.com/faq',
            'image' => 'https://portal.causeywebsolutions.com/og/faq.jpg',
        ];
    @endphp

    <div class="faq-page">
        {{-- HERO --}}
        <section class="section" id="faq-hero">
            <div class="container">
                <div class="max-w-3xl">
                    <p class="eyebrow">Questions, answered</p>
                    <h1 class="h2">Renlo FAQ</h1>
                    <p class="copy">
                        Short answers about the trial, billing, portal, and how {{ $productName }} fits real client work.
                    </p>

                    {{-- Quick jump links --}}
                    <div class="mt-6 flex flex-wrap gap-2">
                        <a href="#faq-general" class="oh-pill oh-pill--muted text-[11px]">Getting started</a>
                        <a href="#faq-features" class="oh-pill oh-pill--muted text-[11px]">Workflow</a>
                        <a href="#faq-billing" class="oh-pill oh-pill--muted text-[11px]">Billing</a>
                        <a href="#faq-security" class="oh-pill oh-pill--muted text-[11px]">Security</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- CONTENT SHELL --}}
        <section class="section pt-0">
            <div class="container">
                <div class="grid gap-6">

                    {{-- GETTING STARTED --}}
                    <section id="faq-general" class="oh-card p-6 md:p-8">
                        <div class="max-w-3xl">
                            <h2 class="h3">Getting started</h2>
                            <p class="text-sm text-text-subtle mt-1">
                                Trial details, setup, cancellation, and what happens next.
                            </p>
                        </div>

                        <div class="mt-5 space-y-2">
                            <details class="faq">
                                <summary class="faq__summary">
                                    Do I need a credit card for the 14-day trial?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>No. You can explore the core workflow first, then decide whether to continue.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    What happens when the trial ends?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Your workspace won’t auto-upgrade without confirmation. When you’re ready, you can
                                        start a paid subscription to keep access.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    How long does setup usually take?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Most people can get a workspace ready quickly: add a client, start a project, and
                                        send a first invoice in one sitting.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Can I import clients or projects?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>You can add clients manually today. Import options may be added based on customer
                                        demand.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Can I cancel anytime?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Yes. It’s month-to-month. Cancelling stops future charges.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    What happens to my data if I cancel?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>You can export key data anytime. If you cancel, you’ll still be able to request
                                        access to your data for a limited time if needed.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Is there a free plan?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Not currently. Pricing stays simple: one plan with full access, plus a trial to
                                        evaluate it properly.</p>
                                </div>
                            </details>
                        </div>
                    </section>

                    {{-- WORKFLOW --}}
                    <section id="faq-features" class="oh-card p-6 md:p-8">
                        <div class="max-w-3xl">
                            <h2 class="h3">Features & workflow</h2>
                            <p class="text-sm text-text-subtle mt-1">
                                How projects, tasks, templates, and the portal work together.
                            </p>
                        </div>

                        <div class="mt-5 space-y-2">
                            <details class="faq">
                                <summary class="faq__summary">
                                    Does {{ $productName }} include a client portal?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Yes. Clients can view shared updates, files, and invoices in one place so
                                        communication stays organized.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Do clients need an account to view the portal?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Clients can be invited to access their portal. The exact login experience depends on
                                        how your workspace is configured.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    How do templates work?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Save repeatable project structures—stages and tasks—so new work starts with the same
                                        baseline every time.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Can I customize stages?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Yes. You can rename stages to match your workflow and keep the pipeline aligned with
                                        how you actually run projects.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Do you support automations?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Some automated reminders are available now. More automation will be added based on
                                        real customer workflows.</p>
                                </div>
                            </details>
                        </div>
                    </section>

                    {{-- BILLING --}}
                    <section id="faq-billing" class="oh-card p-6 md:p-8">
                        <div class="max-w-3xl">
                            <h2 class="h3">Billing & payments</h2>
                            <p class="text-sm text-text-subtle mt-1">
                                How invoices work, how clients pay, and what’s included.
                            </p>
                        </div>

                        <div class="mt-5 space-y-2">
                            <details class="faq">
                                <summary class="faq__summary">
                                    How do clients pay invoices?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Invoices can include a secure Stripe checkout. Payment status updates in the
                                        workspace.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Can I accept deposits or partial payments?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Many service businesses need this. If your current invoice flow supports partial
                                        payments, Renlo will reflect that status—if not, it’s a high-priority enhancement.
                                    </p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Do you charge setup or hidden fees?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>No. One plan, one price—no setup fees or surprise add-ons.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Do you offer team pricing?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Team workspaces are planned. Today, {{ $productName }} is optimized for solo
                                        operators and small studios.</p>
                                </div>
                            </details>
                        </div>
                    </section>

                    {{-- SECURITY --}}
                    <section id="faq-security" class="oh-card p-6 md:p-8">
                        <div class="max-w-3xl">
                            <h2 class="h3">Data & security</h2>
                            <p class="text-sm text-text-subtle mt-1">
                                How data is handled, exports, and general security practices.
                            </p>
                        </div>

                        <div class="mt-5 space-y-2">
                            <details class="faq">
                                <summary class="faq__summary">
                                    Is my data secure?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Renlo uses modern security practices, role-based access, and Stripe for payments. You
                                        can export key data anytime.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Can I export my data if I leave?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Yes. Export is part of keeping Renlo low-risk to adopt. If there’s a specific export
                                        you need, support can help.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Where is my data hosted?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Renlo is hosted on reputable infrastructure with standard safeguards. If you need
                                        details for compliance, contact support.</p>
                                </div>
                            </details>

                            <details class="faq">
                                <summary class="faq__summary">
                                    Do you integrate with other tools?
                                    <span class="faq__chev" aria-hidden="true">›</span>
                                </summary>
                                <div class="faq__body">
                                    <p>Core workflows are built-in. Integrations may be added over time based on customer
                                        demand.</p>
                                </div>
                            </details>
                        </div>
                    </section>

                    {{-- SOFT CTA --}}
                    <section class="oh-card p-6 md:p-8">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                            <div class="max-w-2xl">
                                <h2 class="h2">Still have a question?</h2>
                                <p class="copy mt-2">
                                    Tell us what you’re working on and we’ll point you to the right answer.
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a class="oh-btn" href="/contact">Contact us</a>
                                <a class="oh-btn oh-btn--primary" href="/features">Explore features</a>
                            </div>
                        </div>
                    </section>

                </div>
            </div>
        </section>
    </div>
    @verbatim
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "FAQPage",
          "mainEntity": [
            {
              "@type": "Question",
              "name": "Do I need a credit card for the 14-day trial?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "No. You can explore the core workflow first, then decide whether to continue."
              }
            },
            {
              "@type": "Question",
              "name": "What happens when the trial ends?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Your workspace won’t auto-upgrade without confirmation. When you’re ready, you can start a paid subscription to keep access."
              }
            },
            {
              "@type": "Question",
              "name": "Can I cancel anytime?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Yes. It’s month-to-month. Cancelling stops future charges."
              }
            },
            {
              "@type": "Question",
              "name": "Does Renlo include a client portal?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Yes. Clients can view shared updates, files, and invoices in one place so communication stays organized."
              }
            },
            {
              "@type": "Question",
              "name": "How do clients pay invoices?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Invoices can include a secure Stripe checkout. Payment status updates in the workspace."
              }
            },
            {
              "@type": "Question",
              "name": "Can I export my data if I leave?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Yes. Export is part of keeping Renlo low-risk to adopt. If there’s a specific export you need, support can help."
              }
            }
          ]
        }
        </script>
    @endverbatim
@endsection
