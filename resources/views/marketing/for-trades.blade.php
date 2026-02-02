@extends('layouts.marketing')

@section('title', 'Renlo for Trades')

@section('content')
    <section class="section py-16 bg-[rgb(var(--ui-bg))]">
        <div class="container max-w-5xl space-y-6">
            <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Built for trades & services</p>
            <h1 class="text-4xl font-semibold text-text-base">Turn estimates, visits, and payments into dependable workdays.</h1>
            <p class="text-base text-text-subtle">
                Scope, schedule, and invoice every job inside one workspace so crews, customers, and cash flow stay aligned.
            </p>
            <div class="flex flex-wrap gap-3">
                <a class="oh-btn oh-btn--primary" href="{{ route('trial.show', ['type' => 'trades']) }}">Start free 14-day trial</a>
                <a class="oh-btn oh-btn--ghost" href="{{ route('marketing.features') }}#demo">See a live workspace</a>
            </div>
            <p class="text-xs font-semibold tracking-[0.3em] text-text-subtle">14-day trial · No credit card required · Cancel anytime</p>
        </div>
    </section>

    <section class="section py-12">
        <div class="container max-w-6xl grid gap-6 lg:grid-cols-4">
            @foreach (['Visit scheduling', 'Instant estimates', 'Customer updates', 'Receipts in hand'] as $item)
                <div class="oh-card p-4 text-sm font-semibold text-text-subtle">✔︎ {{ $item }}</div>
            @endforeach
        </div>
    </section>

    <section class="section py-16 bg-slate-50">
        <div class="container max-w-6xl grid gap-8 md:grid-cols-2">
            @foreach ([
                ['title' => 'Clients & Contacts', 'copy' => 'Customer records that carry project notes, photos, and billing history.'],
                ['title' => 'Projects & Tasks', 'copy' => 'Jobs with tasks, materials, and technicians scheduled per visit.'],
                ['title' => 'Invoices & Payments', 'copy' => 'Estimates convert to invoices; reminders follow if payment is late.'],
                ['title' => 'Scheduling', 'copy' => 'Color-coded timeline tied to crews, locations, and follow-ups.'],
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
                    ['step' => 'Estimate', 'detail' => 'Capture scope, photos, and pricing for approval.'],
                    ['step' => 'Work', 'detail' => 'Schedule crews, record notes, and keep clients posted.'],
                    ['step' => 'Invoice', 'detail' => 'Convert approved work into an invoice and collect online.'],
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
                <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">How trades win</p>
                <p class="mt-3 text-base text-text-subtle">From customer intake to payment receipt, every visit is linked to the same invoice flow.</p>
            </div>
        </div>
    </section>

    <section class="section py-16">
        <div class="container max-w-5xl space-y-4">
            <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Quick Q&A</p>
            <details class="oh-card p-4">
                <summary class="text-sm font-semibold">Can I schedule repeat jobs?</summary>
                <p class="mt-2 text-sm text-text-subtle">Yes. Copy a previous job, adjust the visit date, and keep the same crew.</p>
            </details>
            <details class="oh-card p-4">
                <summary class="text-sm font-semibold">Do customers get payment reminders?</summary>
                <p class="mt-2 text-sm text-text-subtle">Yes. Renlo automatically reminds them when invoices go past due.</p>
            </details>
            <details class="oh-card p-4">
                <summary class="text-sm font-semibold">Are photos, notes, and materials tracked?</summary>
                <p class="mt-2 text-sm text-text-subtle">Everything saves to the job record so technicians stay aligned.</p>
            </details>
        </div>
    </section>

    <section class="section py-16 bg-[rgb(var(--ui-bg))]">
        <div class="container max-w-5xl space-y-4 text-center">
            <h2 class="text-3xl font-semibold text-text-base">Trades work with reliable schedules and faster invoices.</h2>
            <p class="text-base text-text-subtle">Jobs, crews, estimates, and payment links stay connected so you can focus on getting the work done.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a class="oh-btn oh-btn--primary" href="{{ route('trial.show', ['type' => 'trades']) }}">Start free 14-day trial</a>
                <a class="oh-btn oh-btn--ghost" href="{{ route('contact.form') }}">Book a live walk-through</a>
            </div>
            <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">No credit card required · Cancel anytime</p>
        </div>
    </section>
@endsection
