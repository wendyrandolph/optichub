@extends('layouts.marketing')

@section('title', 'Renlo for Creatives')

@section('content')
    <section class="section py-16 bg-[rgb(var(--ui-bg))]">
        <div class="container max-w-5xl space-y-6">
            <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Built for creative teams</p>
            <h1 class="text-4xl font-semibold text-text-base">Keep every review, approval, and payment on the same schedule.</h1>
            <p class="text-base text-text-subtle">
                Projects, proposals, tasks, and client portals stay in sync so every creative milestone is attached to a verified
                invoice.
            </p>
            <div class="flex flex-wrap gap-3">
                <a class="oh-btn oh-btn--primary" href="{{ route('trial.show', ['type' => 'creative']) }}">Start free 14-day trial</a>
                <a class="oh-btn oh-btn--ghost" href="{{ route('marketing.features') }}#demo">See a live workspace</a>
            </div>
            <p class="text-xs font-semibold tracking-[0.3em] text-text-subtle">14-day trial · No credit card required · Cancel anytime</p>
        </div>
    </section>

    <section class="section py-12">
        <div class="container max-w-6xl grid gap-6 lg:grid-cols-4">
            @foreach (['Structured briefs', 'Approvals in one place', 'Pipelines you trust', 'Files ready for clients'] as $item)
                <div class="oh-card p-4 text-sm font-semibold text-text-subtle">✔︎ {{ $item }}</div>
            @endforeach
        </div>
    </section>

    <section class="section py-16 bg-slate-50">
        <div class="container max-w-6xl grid gap-8 md:grid-cols-2">
            @foreach ([
                ['title' => 'Clients & Contacts', 'copy' => 'Reusable client records with email, creative assets, and linked invoices.'],
                ['title' => 'Projects & Tasks', 'copy' => 'Stages, task owners, reviews, and reminders that match your process.'],
                ['title' => 'Invoices & Payments', 'copy' => 'Branded drafts, approvals, and Stripe links tied to each project.'],
                ['title' => 'Client Portal', 'copy' => 'Share files, status, and deadlines with a professional portal update.'],
            ] as $card)
                <div class="oh-card p-5 space-y-2">
                    <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Feature</p>
                    <h3 class="text-lg font-semibold text-text-base">{{ $card['title'] }}</h3>
                    <p class="text-sm text-text-subtle">{{ $card['copy'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="section py-16">
        <div class="container max-w-5xl space-y-8">
            <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">How it runs</p>
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['step' => 'Intake', 'detail' => 'Add briefs, budgets, and retainers.'],
                    ['step' => 'Work', 'detail' => 'Track stages, approvals, and files for each client.'],
                    ['step' => 'Invoice', 'detail' => 'Convert approvals into invoices and send payment links.'],
                ] as $stage)
                    <div class="oh-card p-5 space-y-2">
                        <p class="text-sm font-semibold text-text-subtle">{{ $stage['step'] }}</p>
                        <p class="text-base text-text-base">{{ $stage['detail'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section py-16 bg-slate-50">
        <div class="container max-w-5xl grid gap-8 lg:grid-cols-2">
            <div class="oh-card p-6">
                <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Screenshots</p>
                <div class="mt-4 grid gap-4">
                    <div class="rounded-2xl bg-[rgb(var(--ui-surface))] h-52"></div>
                    <div class="rounded-2xl bg-[rgb(var(--ui-surface))] h-32"></div>
                </div>
            </div>
            <div class="oh-card p-6">
                <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">How creatives work</p>
                <p class="mt-3 text-base text-text-subtle">Bring files, feedback, and invoices into one shared workspace for every client.</p>
            </div>
        </div>
    </section>

    <section class="section py-16">
        <div class="container max-w-5xl space-y-4">
            <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Quick Q&A</p>
            <details class="oh-card p-4">
                <summary class="text-sm font-semibold">Do creatives need a separate tool?</summary>
                <p class="mt-2 text-sm text-text-subtle">No. Renlo keeps approvals, feedback, tasks, and billing in one workspace.</p>
            </details>
            <details class="oh-card p-4">
                <summary class="text-sm font-semibold">Can I keep drafts for clients?</summary>
                <p class="mt-2 text-sm text-text-subtle">Yes. Save proposals as templates and duplicate them for new clients.</p>
            </details>
            <details class="oh-card p-4">
                <summary class="text-sm font-semibold">Do clients see invoices next to tasks?</summary>
                <p class="mt-2 text-sm text-text-subtle">They see status, approvals, and payment links in the portal—no email chasing.</p>
            </details>
        </div>
    </section>

    <section class="section py-16 bg-[rgb(var(--ui-bg))]">
        <div class="container max-w-5xl space-y-4 text-center">
            <h2 class="text-3xl font-semibold text-text-base">Structured creative work with reliable billing.</h2>
            <p class="text-base text-text-subtle">Projects, approvals, reviews, and payments stay connected so nothing slips between tools.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a class="oh-btn oh-btn--primary" href="{{ route('trial.show', ['type' => 'creative']) }}">Start free 14-day trial</a>
                <a class="oh-btn oh-btn--ghost" href="{{ route('contact.form') }}">Book a creative review</a>
            </div>
            <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">No credit card required · Cancel anytime</p>
        </div>
    </section>
@endsection
