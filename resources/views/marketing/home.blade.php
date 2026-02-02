@extends('layouts.marketing')
@section('title', 'Renlo - Home')

@section('content')
    @php
        $isLoggedIn = auth('admin')->check() || auth()->check();
        $productName = 'Renlo';
    @endphp

    {{-- PAGE WRAPPER --}}
    <div class="bg-white text-slate-900">

        {{-- HERO (Wide) --}}
        <section class="bg-slate-50">
            <div class="mx-auto max-w-6xl px-6 py-16">
                <div class="grid gap-10 lg:grid-cols-12 items-start">
                    <div class="lg:col-span-7 space-y-6">
                        <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Built for client work</p>
                        <h1 class="text-4xl font-semibold text-text-base leading-tight">
                            Stop juggling files, tasks, projects, and billing in separate tools.
                        </h1>
                        <p class="text-base text-text-subtle leading-relaxed max-w-2xl">
                            Renlo keeps projects, tasks, messages, and invoices connected, plus a client portal so you
                            always know
                            what’s next with every client.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            @if ($isLoggedIn)
                                <a href="{{ route('admin.dashboard') }}" class="oh-btn oh-btn--primary">Go to your
                                    dashboard</a>
                            @else
                                <a href="{{ route('trial.show') }}" class="oh-btn oh-btn--primary">Start your free 14-day
                                    trial</a>
                            @endif
                            <a href="#demo" class="oh-btn oh-btn--ghost">Book a demo</a>
                        </div>
                    </div>
                    <div class="lg:col-span-5">
                        <div class="oh-card space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs uppercase tracking-[0.3em] text-text-subtle">Today</span>
                                <span class="oh-pill oh-pill--muted text-[10px]">Client work</span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-start gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-text-base">Website strategy session</p>
                                        <p class="text-sm text-text-subtle">Maple Studio · 2:00pm</p>
                                    </div>
                                    <span class="oh-pill oh-pill--info text-[11px]">In progress</span>
                                </div>
                                <div class="flex justify-between items-start gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-text-base">Brand refresh concepts</p>
                                        <p class="text-sm text-text-subtle">Draft review due tomorrow</p>
                                    </div>
                                    <span class="oh-pill oh-pill--success text-[11px]">On track</span>
                                </div>
                                <div class="flex justify-between items-start gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-text-base">Invoice INV-1003</p>
                                        <p class="text-sm text-text-subtle">Waiting on client payment</p>
                                    </div>
                                    <span class="oh-pill oh-pill--warning text-[11px]">Due soon</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-sm text-text-subtle">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.3em]">Invoices paid</p>
                                    <p class="text-lg font-semibold text-text-base">84%</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.3em]">Active projects</p>
                                    <p class="text-lg font-semibold text-text-base">7</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- WHY (No mini-cards; use callouts) --}}
        <section id="why" class="bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-10 py-16 md:py-20">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                    <div class="lg:col-span-6">
                        <p class="text-xs tracking-[0.18em] uppercase text-slate-500">Why {{ $productName }}</p>
                        <h2 class="mt-3 text-2xl md:text-3xl font-semibold text-slate-900">
                            From scattered to structured—fast.
                        </h2>
                        <p class="mt-4 text-base md:text-lg text-slate-600 leading-relaxed">
                            {{ $productName }} gives service-based teams one place for client work—projects, tasks, files,
                            and payments—
                            without heavyweight “enterprise” bloat or disconnected tools.
                        </p>

                        <ul class="mt-6 space-y-3 text-slate-600">
                            <li><span class="font-semibold text-slate-900">Less mental overhead.</span> Next steps, due
                                dates, and follow-ups stay visible.</li>
                            <li><span class="font-semibold text-slate-900">Cleaner client communication.</span> Files,
                                updates, and approvals live alongside the work.</li>
                            <li><span class="font-semibold text-slate-900">More predictable cash flow.</span> Invoices and
                                payment status sit next to the work.</li>
                        </ul>
                    </div>

                    <div class="lg:col-span-6">
                        <div class="space-y-4">
                            <div class="flex gap-3">
                                <span class="mt-2 h-2.5 w-2.5 rounded-full bg-slate-900/80"></span>
                                <div>
                                    <div class="font-semibold text-slate-900">Workdays run smoother.</div>
                                    <div class="text-slate-600">See what’s due, what’s waiting, and what needs
                                        attention—without digging.</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <span class="mt-2 h-2.5 w-2.5 rounded-full bg-emerald-600"></span>
                                <div>
                                    <div class="font-semibold text-slate-900">Clients stay informed.</div>
                                    <div class="text-slate-600">A clear portal and next steps reduce back-and-forth.</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <span class="mt-2 h-2.5 w-2.5 rounded-full bg-indigo-600"></span>
                                <div>
                                    <div class="font-semibold text-slate-900">You stay in control.</div>
                                    <div class="text-slate-600">Track work, follow-ups, and billing with fewer moving parts.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section py-16 bg-[rgb(var(--ui-bg))]">
            <div class="mx-auto max-w-6xl px-6 lg:px-10 grid gap-8 lg:grid-cols-2">
                <div class="oh-card p-6 space-y-3">
                    <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Industry spotlight</p>
                    <h3 class="text-2xl font-semibold text-text-base">For creatives</h3>
                    <p class="text-sm text-text-subtle">Stages, approvals, feedback, and invoices live together.</p>
                    <div class="flex flex-wrap gap-3">
                    <a class="oh-btn oh-btn--primary" href="{{ url('/for-creatives') }}">Explore creatives</a>
                        <a class="oh-btn oh-btn--ghost" href="{{ route('trial.show', ['type' => 'creative']) }}">Start creative trial</a>
                    </div>
                </div>
                <div class="oh-card p-6 space-y-3">
                    <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Industry spotlight</p>
                    <h3 class="text-2xl font-semibold text-text-base">For trades</h3>
                    <p class="text-sm text-text-subtle">Estimates, visits, crews, and payments stay connected with your customers.</p>
                    <div class="flex flex-wrap gap-3">
                    <a class="oh-btn oh-btn--primary" href="{{ url('/for-trades') }}">Explore trades</a>
                        <a class="oh-btn oh-btn--ghost" href="{{ route('trial.show', ['type' => 'trades']) }}">Start trades trial</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- WORKFLOW STRIP --}}
        <section class="bg-slate-50/60">
            <div class="mx-auto max-w-6xl px-6 lg:px-10 py-12">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 text-sm text-text-subtle">
                    @foreach ([
            'Projects' => 'Stages + owners stay together',
            'Tasks' => 'Every task keeps notes, files, and due dates',
            'Billing' => 'Invoices + payments remain tied to the job',
            'Messaging' => 'Notes, notifications, and replies stay attached',
            'Client portal' => 'Clients see status without email ping-pong',
        ] as $title => $desc)
                        <div class="oh-card oh-card--muted p-4 space-y-1">
                            <div class="text-xs uppercase tracking-[0.3em] text-text-subtle">{{ $title }}</div>
                            <div class="text-sm font-semibold text-text-base">{{ $desc }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- FEATURES / PROOF --}}
        <section id="features" class="bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-10 py-16 space-y-10">
                <header class="max-w-3xl space-y-2">
                    <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Renlo focus</p>
                    <h2 class="text-3xl font-semibold text-text-base">What makes Renlo different</h2>
                    <p class="text-base text-text-subtle leading-relaxed">
                        Unlike generic CRMs or siloed invoicing tools, Renlo keeps projects, tasks, billing, messaging, and
                        the portal in one aligned workspace.
                    </p>
                </header>

                <div class="grid md:grid-cols-3 gap-6">
                    <div class="oh-card space-y-3">
                        <div class="text-xs uppercase tracking-[0.3em] text-text-subtle">Workflow clarity</div>
                        <p class="font-semibold text-text-base">Projects stay connected to billing</p>
                        <p class="text-sm text-text-subtle">
                            Every project page shows tasks, clients, and invoices side-by-side so you never guess what’s
                            paid.
                        </p>
                    </div>
                    <div class="oh-card space-y-3">
                        <div class="text-xs uppercase tracking-[0.3em] text-text-subtle">Client focus</div>
                        <p class="font-semibold text-text-base">Client portal + messaging</p>
                        <p class="text-sm text-text-subtle">
                            Clients reply to work updates inside the portal; Renlo keeps those replies tied to the job.
                        </p>
                    </div>
                    <div class="oh-card space-y-3">
                        <div class="text-xs uppercase tracking-[0.3em] text-text-subtle">Operations confidence</div>
                        <p class="font-semibold text-text-base">Everything built for service teams</p>
                        <p class="text-sm text-text-subtle">
                            Tasks, approvals, time tracking, and invoices follow the same playbook as the Renlo workspace so
                            nothing slips.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- USE CASES (Soft surfaces, not boxed hard) --}}
        <section id="use-cases" class="bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-10 py-16 md:py-20">
                <div class="max-w-3xl">
                    <p class="text-xs tracking-[0.18em] uppercase text-slate-500">Who it’s for</p>
                    <h2 class="mt-3 text-2xl md:text-3xl font-semibold text-slate-900">Built for solo operators and small
                        teams.</h2>
                    <p class="mt-3 text-base md:text-lg text-slate-600 leading-relaxed">
                        Designers, photographers, freelancers, and small studios use Renlo to manage client work, deadlines,
                        and payments—
                        without juggling tools or losing context.
                    </p>
                </div>

                <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <article class="rounded-3xl bg-slate-50/60 ring-1 ring-slate-200/60 p-6">
                        <h3 class="text-lg font-semibold text-slate-900">Studios & Agencies</h3>
                        <p class="mt-2 text-slate-600">Run multiple projects with shared visibility and fewer status
                            meetings.</p>
                        <ul class="mt-4 text-sm text-slate-600 space-y-2">
                            <li>• Branded proposals & invoices</li>
                            <li>• Clear stages & approvals</li>
                        </ul>
                    </article>

                    <article class="rounded-3xl bg-slate-50/60 ring-1 ring-slate-200/60 p-6">
                        <h3 class="text-lg font-semibold text-slate-900">Freelancers</h3>
                        <p class="mt-2 text-slate-600">Keep tasks, files, and invoices together—no spreadsheets required.
                        </p>
                        <ul class="mt-4 text-sm text-slate-600 space-y-2">
                            <li>• Next steps always visible</li>
                            <li>• Paid status at a glance</li>
                        </ul>
                    </article>

                    <article class="rounded-3xl bg-slate-50/60 ring-1 ring-slate-200/60 p-6">
                        <h3 class="text-lg font-semibold text-slate-900">Trades & Services</h3>
                        <p class="mt-2 text-slate-600">Quote, schedule, and collect with a straightforward client
                            experience.</p>
                        <ul class="mt-4 text-sm text-slate-600 space-y-2">
                            <li>• On-site notes and photos</li>
                            <li>• Simple invoices & receipts</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        {{-- HOW IT WORKS (No cards) --}}
        <section id="how-it-works" class="bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-10 py-16 space-y-10">
                <header class="max-w-3xl space-y-2">
                    <h2 class="text-3xl font-semibold text-text-base">From signup to the first project—fast.</h2>
                    <p class="text-base text-text-subtle leading-relaxed">
                        A guided setup gets you working with real clients inside Renlo—no hopping between billing, task
                        lists, and messages.
                    </p>
                </header>
                <div class="grid gap-6 md:grid-cols-3">
                    <article class="oh-card oh-card--muted p-5 space-y-3">
                        <div class="text-xs uppercase tracking-[0.3em] text-text-subtle">Step 1</div>
                        <h3 class="text-lg font-semibold text-text-base">Start your workspace</h3>
                        <p class="text-sm text-text-subtle">
                            Create your Renlo workspace, invite clients, and bring in projects with one simple setup.
                        </p>
                        <ul class="text-sm text-text-subtle space-y-1 list-disc list-inside">
                            <li>Pick your branding and timezone.</li>
                            <li>Invite teammates and clients in one place.</li>
                        </ul>
                    </article>
                    <article class="oh-card oh-card--muted p-5 space-y-3">
                        <div class="text-xs uppercase tracking-[0.3em] text-text-subtle">Step 2</div>
                        <h3 class="text-lg font-semibold text-text-base">Pick a workflow</h3>
                        <p class="text-sm text-text-subtle">
                            Choose a template for projects, retainers, or jobs—tasks, stages, and approvals already mapped
                            out.
                        </p>
                        <ul class="text-sm text-text-subtle space-y-1 list-disc list-inside">
                            <li>Prebuilt stages keep everyone aligned.</li>
                            <li>Templates carry billing reminders and messaging.</li>
                        </ul>
                    </article>
                    <article class="oh-card oh-card--muted p-5 space-y-3">
                        <div class="text-xs uppercase tracking-[0.3em] text-text-subtle">Step 3</div>
                        <h3 class="text-lg font-semibold text-text-base">Run the work</h3>
                        <p class="text-sm text-text-subtle">
                            Tasks, messages, files, and invoicing stay connected so you can move from kickoff to payment
                            with confidence.
                        </p>
                        <ul class="text-sm text-text-subtle space-y-1 list-disc list-inside">
                            <li>Message clients inside the project portal.</li>
                            <li>Send invoices and see payment status without leaving the workspace.</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        {{-- DEMO (Wide decision moment) --}}
        <section id="demo" class="bg-white">
            <div class="mx-auto max-w-6xl px-6 py-16">
                <div class="oh-card lg:grid lg:grid-cols-[1.1fr,0.9fr] gap-10 p-8">
                    <div class="space-y-4">
                        <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Guided walkthrough</p>
                        <h2 class="text-3xl font-semibold text-text-base">See Renlo move work forward.</h2>
                        <p class="text-base text-text-subtle leading-relaxed">
                            We’ll show a real workspace—projects, tasks, client messaging, and billing—so you can see how
                            the pieces stay aligned.
                        </p>
                        <ul class="space-y-2 text-sm text-text-subtle">
                            <li>• Project + billing workflow, from task to invoice</li>
                            <li>• How the client portal keeps approvals and files tied to the job</li>
                            <li>• Messaging and reminders that keep accountability without extra meetings</li>
                        </ul>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('trial.show') }}" class="oh-btn oh-btn--primary">Book a live demo</a>
                            <a href="#" class="oh-btn oh-btn--ghost">See the agenda</a>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-text-base">What to expect</h3>
                        <div class="space-y-2 text-sm text-text-subtle">
                            <p>30–40 minute walkthrough focused on projects, client billing, and the portal experience.</p>
                            <p>Bring your own workspace or have us demo a sample with real-stage clarity.</p>
                            <p class="text-xs text-text-slate">No hard pitch. Just a live look and honest Q&A.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        {{-- PRICING --}}
        <section id="pricing" class="section section--white">
            <div class="mx-auto max-w-6xl px-6 lg:px-10 py-16">
                <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl space-y-2">
                        <p class="text-xs uppercase tracking-[0.28em] text-text-subtle">Pricing</p>
                        <h2 class="text-3xl font-semibold text-text-base">One plan. Straightforward pricing.</h2>
                        <p class="text-base text-text-subtle leading-relaxed">
                            Renlo keeps projects, tasks, files, billing, and client updates connected—so your team works
                            from one place.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="oh-pill oh-pill--muted text-[11px]">14-day trial</span>
                        <span class="oh-pill oh-pill--muted text-[11px]">No credit card</span>
                        <span class="oh-pill oh-pill--muted text-[11px]">Cancel anytime</span>
                    </div>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-2">
                    {{-- Plan card --}}
                    <article class="oh-card p-6 lg:p-7">
                        <div class="flex items-start justify-between gap-6">
                            <div class="space-y-1">
                                <p class="text-xs uppercase tracking-[0.24em] text-text-subtle">Solo workspace</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-4xl font-semibold text-text-base">$49</span>
                                    <span class="text-sm text-text-subtle">/month</span>
                                </div>
                                <p class="text-sm text-text-subtle">
                                    Full access for one workspace. Built for solo operators and small teams.
                                </p>
                            </div>

                            <div class="flex flex-col items-end gap-2">
                                <span class="oh-pill oh-pill--success text-[11px]">All features enabled</span>
                                <span class="text-xs text-text-subtle">Billed monthly</span>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="flex gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                                <p class="text-sm text-text-subtle">Unlimited clients, projects, tasks, and invoices</p>
                            </div>
                            <div class="flex gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                                <p class="text-sm text-text-subtle">Client portal for files, approvals, and updates</p>
                            </div>
                            <div class="flex gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                                <p class="text-sm text-text-subtle">Messaging + project communication in context</p>
                            </div>
                            <div class="flex gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                                <p class="text-sm text-text-subtle">Stripe billing, reminders, and payment tracking</p>
                            </div>
                        </div>

                        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('trial.show') }}" class="oh-btn oh-btn--primary w-full justify-center">
                                Start your 14-day trial
                            </a>
                            <a href="#demo" class="oh-btn oh-btn--ghost w-full justify-center">
                                Book a demo
                            </a>
                        </div>

                        <div class="mt-4 text-xs text-text-subtle">
                            Secure Stripe checkout. Export your data anytime.
                        </div>
                    </article>

                    {{-- “Good to know” / trust + future plans --}}
                    <aside class="oh-card p-6 lg:p-7 bg-surface-muted/30">
                        <div class="space-y-2">
                            <p class="text-xs uppercase tracking-[0.24em] text-text-subtle">Good to know</p>
                            <h3 class="text-xl font-semibold text-text-base">Designed for real client work.</h3>
                            <p class="text-sm text-text-subtle leading-relaxed">
                                Renlo is built around the workflow: projects → tasks → files → client updates → invoices.
                                That linkage is the point.
                            </p>
                        </div>

                        <div class="mt-6 space-y-3">
                            <div class="flex items-start gap-3">
                                <span class="oh-pill oh-pill--muted text-[11px]">Trial</span>
                                <p class="text-sm text-text-subtle">Explore the full workflow for 14 days before paying.
                                </p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="oh-pill oh-pill--muted text-[11px]">No contracts</span>
                                <p class="text-sm text-text-subtle">Month-to-month billing. Cancel in one click.</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="oh-pill oh-pill--muted text-[11px]">Team plans</span>
                                <p class="text-sm text-text-subtle">Multi-user workspaces are coming soon.</p>
                            </div>
                        </div>

                        <div class="mt-7">
                            <a href="#demo" class="oh-btn w-full justify-center">
                                Ask about team access
                            </a>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        {{-- FINAL CTA (Stronger, less boxed) --}}
        <section id="cta" class="bg-slate-50/60">
            <div class="mx-auto max-w-7xl px-6 lg:px-10 py-16 md:py-20">
                <div class="rounded-3xl bg-white ring-1 ring-slate-200/60 p-8 md:p-10">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <div class="lg:col-span-7">
                            <h2 class="text-2xl md:text-3xl font-semibold text-slate-900">
                                Ready to run client work with fewer moving parts?
                            </h2>
                            <p class="mt-3 text-base md:text-lg text-slate-600 leading-relaxed">
                                Join teams who keep projects, files, and billing connected—so the next step is always
                                obvious.
                            </p>
                        </div>
                        <div class="lg:col-span-5 flex flex-col sm:flex-row gap-3 lg:justify-end">
                            <a class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold bg-[rgb(var(--ui-primary))] text-white shadow-sm hover:opacity-95"
                                href="{{ route('trial.show') }}">
                                Start your 14-day free trial
                            </a>
                            <a class="inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold bg-white ring-1 ring-slate-200 hover:bg-slate-50"
                                href="#demo">
                                Book a demo instead
                            </a>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">
                        No credit card required. Cancel anytime.
                    </p>
                </div>
            </div>
        </section>

    </div>
@endsection
