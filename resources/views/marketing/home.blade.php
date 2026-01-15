@extends('layouts.marketing')
@section('title', 'Renlo - Home')

@section('content')
    @php
        $isLoggedIn = auth('admin')->check() || auth()->check();
        $productName = 'Renlo';
    @endphp

    <!-- HERO -->
    <section class="marketing-hero" id="home">
        <div class="marketing-hero__frame">
            {{-- LEFT: Copy + CTAs --}}
            <div class="marketing-hero__copy">
                <p class="marketing-hero__copy-eyebrow">
                    Built for people who do the work
                </p>

                <h1>
                    <span class="grad-text">Stop juggling</span> clients, projects, and files. {{ $productName }} keeps
                    your work organized and moving forward.
                </h1>

                <p class="marketing-hero__sub">
                    {{ $productName }} brings client work, tasks, and billing into one organized workspace—so you always
                    know what’s next.
                </p>

                <div class="marketing-hero__actions">
                    @if ($isLoggedIn)
                        <a href="{{ route('admin.dashboard') }}" class="oh-cta-primary">
                            Go to your dashboard
                        </a>
                    @else
                        <a href="{{ route('trial.show') }}" class="oh-cta-primary">
                            Start your free 14-day trial
                        </a>
                    @endif

                    <a href="#demo" class="oh-cta-secondary">
                        Book a demo
                    </a>
                </div>

                <p class="marketing-hero__meta">
                    14-day free trial. No credit card required. Cancel anytime.
                </p>
            </div>

            {{-- RIGHT: Preview card --}}
            <aside class="marketing-hero__preview" aria-label="Snapshot of a day inside {{ $productName }}">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-xs font-semibold tracking-[0.16em] text-[rgb(var(--ui-text-subtle))] uppercase">
                        Today
                    </div>
                    <span class="badge-pill badge-pill--progress">
                        Client work
                    </span>
                </div>

                {{-- Line items --}}
                <div class="space-y-2.5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[0.78rem] font-semibold text-[rgb(var(--ui-text))]">
                                Website strategy session
                            </p>
                            <p class="text-[0.7rem] text-[rgb(var(--ui-text-subtle))]">
                                Client: Maple Studio · 2:00pm
                            </p>
                        </div>
                        <span class="badge-pill badge-pill--progress">
                            In progress
                        </span>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[0.78rem] font-semibold text-[rgb(var(--ui-text))]">
                                Brand refresh concepts
                            </p>
                            <p class="text-[0.7rem] text-[rgb(var(--ui-text-subtle))]">
                                Draft review due tomorrow
                            </p>
                        </div>
                        <span class="badge-pill badge-pill--ontime">
                            On track
                        </span>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[0.78rem] font-semibold text-[rgb(var(--ui-text))]">
                                Invoice INV-1003
                            </p>
                            <p class="text-[0.7rem] text-[rgb(var(--ui-text-subtle))]">
                                Waiting on client payment
                            </p>
                        </div>
                        <span class="badge-pill badge-pill--due">
                            Due soon
                        </span>
                    </div>
                </div>

                {{-- Footer stats --}}
                <div class="marketing-hero__preview-footer">
                    <div>
                        <div class="marketing-hero__preview-label">Invoices paid</div>
                        <div class="marketing-hero__preview-value">84%</div>
                    </div>
                    <div>
                        <div class="marketing-hero__preview-label">Active projects</div>
                        <div class="marketing-hero__preview-value">7</div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <!-- WHY -->
    <section class="section section--why" id="why">
        <div class="container why-layout">
            {{-- Left: narrative --}}
            <div class="why-main copy">
                <p class="eyebrow">Why {{ $productName }}</p>
                <h2 class="h2 mb-4">
                    From chaos to client clarity—fast.
                </h2>

                <p class="mb-3">
                    {{ $productName }} gives service-based teams one calm place for clients, projects, and payments—without
                    heavyweight “enterprise” bloat or a maze of disconnected tools.
                </p>

                <ul class="clean why-list">
                    <li><strong>All your work, organized.</strong> Clients, files, tasks, and billing live in one place
                        instead of five different tools.
                    </li>
                    <li><strong>Built for real client work.</strong> Flows that match how service businesses actually
                        work—not generic CRM pipelines.</li>
                    <li><strong>Nothing slips through.</strong> See what’s due, what’s blocked, and what’s done—without
                        mental notes or spreadsheets.</li>
                </ul>
            </div>

            {{-- Right: three “reasons” mini-cards --}}
            <div class="why-grid">
                <article class="why-tile">
                    <div class="why-tile__icon">
                        <span class="dot dot--blue"></span>
                    </div>
                    <div>
                        <h3 class="why-tile__title">Calmer workdays</h3>
                        <p class="why-tile__body">
                            See calls, deadlines, and invoices in one view so your team isn’t living in email.
                        </p>
                    </div>
                </article>

                <article class="why-tile">
                    <div class="why-tile__icon">
                        <span class="dot dot--green"></span>
                    </div>
                    <div>
                        <h3 class="why-tile__title">Clients feel taken care of</h3>
                        <p class="why-tile__body">
                            A clear portal and next steps mean fewer “just checking in” messages.
                        </p>
                    </div>
                </article>

                <article class="why-tile">
                    <div class="why-tile__icon">
                        <span class="dot dot--purple"></span>
                    </div>
                    <div>
                        <h3 class="why-tile__title">You stay in control</h3>
                        <p class="why-tile__body">
                            Track work, cash flow, and follow-ups without spreadsheets; get nudges before things slip.
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- FEATURES (lighter rows with merged template highlights) -->
    <section class="section section--white" id="features">
        <div class="container container--narrow">
            <div class="feature-head">
                <h2 class="h2">Everything you need to run client work—without the clutter.</h2>
                <p class="copy feature-sub">The essentials for client work, arranged in the order you actually need them.
                </p>
            </div>

            <div class="space-y-6 divide-y divide-[rgb(var(--ui-border))]">
                <div class="pt-1 flex gap-3">
                    <div
                        class="h-9 w-9 rounded-lg bg-[rgba(var(--ui-primary),0.08)] text-[rgb(var(--ui-primary))] grid place-items-center flex-shrink-0">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="h4">When you need context, it’s already there.</h3>
                        <p class="copy">Clients, files, and notes stay together so you don’t hunt across tools.</p>
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <div
                        class="h-9 w-9 rounded-lg bg-[rgba(var(--ui-primary),0.08)] text-[rgb(var(--ui-primary))] grid place-items-center flex-shrink-0">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="h4">You always know what’s next—and what’s blocked.</h3>
                        <p class="copy">Stages and tasks stay clear, with owners and due dates front and center.</p>
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <div
                        class="h-9 w-9 rounded-lg bg-[rgba(var(--ui-primary),0.08)] text-[rgb(var(--ui-primary))] grid place-items-center flex-shrink-0">
                        <i class="fa-solid fa-calendar-week"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="h4">Your schedule reflects real work—not wishful planning.</h3>
                        <p class="copy">Deadlines, meetings, and stages mirror your week so plans stay realistic.</p>
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <div
                        class="h-9 w-9 rounded-lg bg-[rgba(var(--ui-primary),0.08)] text-[rgb(var(--ui-primary))] grid place-items-center flex-shrink-0">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="h4">Get paid without chasing clients.</h3>
                        <p class="copy">Invoices and payment status sit next to the work, so you know where money stands.</p>
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <div
                        class="h-9 w-9 rounded-lg bg-[rgba(var(--ui-primary),0.08)] text-[rgb(var(--ui-primary))] grid place-items-center flex-shrink-0">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="h4">Clients stay informed without check-ins.</h3>
                        <p class="copy">Approvals, uploads, and payments live in one portal so updates aren’t stuck in email.</p>
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <div
                        class="h-9 w-9 rounded-lg bg-[rgba(var(--ui-primary),0.08)] text-[rgb(var(--ui-primary))] grid place-items-center flex-shrink-0">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="h4">Do the setup once—reuse it every time.</h3>
                        <p class="copy">Templates and automations keep recurring projects consistent without extra steps.</p>
                    </div>
                </div>
            </div>

            <p class="meta feature-foot">
                {{ $productName }} keeps your client workflow organized—clients, projects, billing, and collaboration—in
                one calm place.
            </p>
        </div>
    </section>

    {{-- USE CASES — simplified to three --}}
    <section class="section section--white" id="use-cases">
        <div class="container">
            <header class="uc-head">
                <p class="uc-eyebrow">Who it's for</p>
                <h2 class="h2">Built for solo operators and small studios.</h2>
                <p class="copy uc-lead">
                    Designers, photographers, freelancers, and small studios use Renlo to manage client work, deadlines, and
                    payments—without juggling tools or losing context.
                </p>
            </header>

            <div class="grid gap-4 md:gap-6 grid-cols-1 md:grid-cols-3">
                <article class="card usecase">
                    <h3 class="h4 uc-title">Studios & Agencies</h3>
                    <p class="copy uc-body">Plan projects, share portals, and keep approvals moving without chaos.</p>
                    <ul class="uc-bullets">
                        <li>Branded proposals & invoices</li>
                        <li>Project stages with due dates</li>
                    </ul>
                </article>
                <article class="card usecase">
                    <h3 class="h4 uc-title">Freelancers</h3>
                    <p class="copy uc-body">Track clients and cash flow in one place, no spreadsheets required.</p>
                    <ul class="uc-bullets">
                        <li>Tasks and next steps in one view</li>
                        <li>Invoices & paid status at a glance</li>
                    </ul>
                </article>
                <article class="card usecase">
                    <h3 class="h4 uc-title">Trades & Services</h3>
                    <p class="copy uc-body">Quote, schedule, and collect on time with a clean client experience.</p>
                    <ul class="uc-bullets">
                        <li>On-site notes and photos</li>
                        <li>Simple invoices & receipts</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- HOW IT WORKS (lighter, 3 steps) --}}
    <section class="section section--white" id="how-it-works">
        <div class="container container--narrow">
            <header class="how-head">
                <h2 class="h2">From signup to your first project—fast.</h2>
                <p class="copy how-lead">
                    A guided setup gets you to your first organized client flow fast. Prefer a walkthrough? Book a call
                    anytime.
                </p>
            </header>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="card card--glass">
                    <p class="how-step__label">Step 1</p>
                    <h4 class="h4 how-step__title">Start your trial</h4>
                    <p class="copy how-step__body">Create your workspace and import clients—or begin fresh with calm
                        defaults.</p>
                </div>
                <div class="card card--glass">
                    <p class="how-step__label">Step 2</p>
                    <h4 class="h4 how-step__title">Pick a template</h4>
                    <p class="copy how-step__body">Choose a simple pipeline for projects, retainers, or jobs with tasks
                        pre-set.</p>
                </div>
                <div class="card card--glass">
                    <p class="how-step__label">Step 3</p>
                    <h4 class="h4 how-step__title">Work in flow</h4>
                    <p class="copy how-step__body">Tasks, files, and billing stay together—clients see a clean portal from
                        day one.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- DEMO (ANCHOR ONLY; PLACE YOUR SCHEDULER/FORM HERE) --}}
    <section class="section section--white" id="demo">
        <div class="container container--narrow">
            <div class="demo-card">
                <div class="demo-copy">
                    <p class="eyebrow">Optional walkthrough</p>
                    <h2 class="h2">See how the work actually flows.</h2>
                    <p class="lead copy">
                        We’ll walk through a real workspace and show how client work moves from inquiry to payment—so you
                        can decide if {{ $productName }} fits your workflow.
                    </p>
                    <div class="btn-row">
                        <a class="btn btn--secondary" href="#demo">
                            Book a live demo
                        </a>
                        <a class="btn btn--ghost" href="{{ route('trial.show') }}">
                            Explore on your own
                        </a>
                    </div>
                </div>

                <div class="demo-meta">
                    <div class="demo-tag">30–40 minutes</div>
                    <p>We’ll cover projects, billing, and client portal. No hard pitch.</p>
                </div>
            </div>
            {{-- your scheduler embed / form can live below this card if you want --}}
        </div>
    </section>

    <!-- PRICING -->
    <section class="section section--brand" id="pricing">
        <div class="container container--narrow">
            <h2 class="h2 text-center mb-2">Simple pricing. No contracts.</h2>
            <p class="copy text-center mb-8">
                One clear plan for solo operators and small studios. No tiers, no surprises.
            </p>
            @include('partials.pricing-card')

            <p class="meta text-center mt-6">
                Team plans for multi-user workspaces coming soon. Limited early Lifetime Deal may be offered at launch.
            </p>
        </div>
    </section>

    {{-- FINAL CTA --}}
    <section class="section cta cta--band" id="cta">
        <div class="container container--narrow cta-inner">
            <div class="cta-copy">
                <h2 class="h2">Ready to trade chaos for clarity?</h2>
                <p class="lead copy">
                    Join designers, photographers, and studios who run calm, focused businesses with {{ $productName }}.
                </p>
            </div>
            <div class="cta-actions">
                <a class="btn btn--primary btn--lg" href="{{ route('trial.show') }}">
                    Start your 14-day free trial
                </a>
                <a class="btn btn--ghost btn--lg" href="#demo">
                    Book a demo instead
                </a>
            </div>
        </div>
    </section>
@endsection
