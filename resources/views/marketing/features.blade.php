@extends('layouts.marketing')
@section('title', 'Renlo — Feature stack')

@section('content')
    @php
        $sections = [
            [
                'id' => 'clients',
                'eyebrow' => 'Clients & Records',
                'title' => 'Every client record keeps projects, notes, and invoices aligned.',
                'body' => 'Import contacts, add tags, and see linked projects, invoices, and portal activity from one screen.',
                'bullets' => [
                    'CSV import maps names, emails, tags, and notes in minutes',
                    'Attach files, call notes, and approvals to the client record',
                    'See open projects and billing status without context switching',
                ],
                'img' => 'images/feat-clients@2x.jpg',
                'alt' => 'Client record with notes, files, and invoices',
                'reverse' => false,
            ],
            [
                'id' => 'projects',
                'eyebrow' => 'Projects & Tasks',
                'title' => 'Stages, tasks, and templates keep you ahead of blockers.',
                'body' => 'Plan work with stages, assign owners, and filter by clients, statuses, or dates to keep momentum.',
                'bullets' => [
                    'Drag-and-drop tasks between stages with dependencies',
                    'Save templates with stages, owners, and dates for repeat work',
                    'Filters surface stalled work across clients and projects',
                ],
                'img' => 'images/features/projects-tasks@2x.jpg',
                'alt' => 'Project board with stages and tasks in Renlo',
                'reverse' => true,
            ],
            [
                'id' => 'billing',
                'eyebrow' => 'Billing & Payments',
                'title' => 'Invoices live with projects, not in a separate tool.',
                'body' => 'Create branded invoices from jobs, track paid/overdue status, and let clients pay securely through Stripe.',
                'bullets' => [
                    'Convert estimates to invoices in one click',
                    'Track payment status and send reminders automatically',
                    'Keep receipts, histories, and reconciliations on the same project',
                ],
                'img' => 'images/features/invoices-payments@2x.jpg',
                'alt' => 'Invoice dashboard showing payment status and Stripe checkout',
                'reverse' => false,
            ],
            [
                'id' => 'portal',
                'eyebrow' => 'Client Portal',
                'title' => 'Clients see updates, files, and approvals without extra check-ins.',
                'body' => 'Share project updates, request approvals, and upload files inside a branded portal that mirrors your workflow.',
                'bullets' => [
                    'Approve files, leave comments, and review timelines in one place',
                    'Share secure, time-limited links when clients need quick access',
                    'Control what clients see with private or shared views',
                ],
                'img' => 'images/features/client-portal@2x.jpg',
                'alt' => 'Client portal dashboard with updates and approvals',
                'reverse' => true,
            ],
            [
                'id' => 'templates',
                'eyebrow' => 'Templates & Automations',
                'title' => 'Turn a project into a reusable workflow in minutes.',
                'body' => 'Save stages, tasks, owners, and reminders, then launch with relative dates for a consistent kickoff.',
                'bullets' => [
                    'Save any project as a template with stages and owners',
                    'Apply relative due dates from a chosen start point',
                    'Enable reminders for “due soon” and “overdue” notifications',
                ],
                'img' => 'images/features/templates-automations@2x.jpg',
                'alt' => 'Project template with stages and relative dates',
                'reverse' => false,
            ],
            [
                'id' => 'calendar',
                'eyebrow' => 'Scheduling',
                'title' => 'Your schedule mirrors real commitments, not wishful planning.',
                'body' => 'Deadlines, milestones, and meetings appear alongside projects so you always know what needs attention.',
                'bullets' => [
                    'Color-coded clarity for today, this week, and upcoming work',
                    'Lightweight reminders and statuses you can scan at a glance',
                    'Sync milestones while keeping the timeline tied to each project',
                ],
                'img' => 'images/features/templates-automations@2x.jpg',
                'alt' => 'Calendar showing milestones, meetings, and dates',
                'reverse' => true,
            ],
        ];
@endphp

    <style>
        .workflow-section {
            scroll-margin-top: 120px;
        }

        .feature-subnav {
            position: sticky;
            top: 0;
            z-index: 10;
            background: white;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

        .feature-subnav__row {
            gap: 1rem;
            justify-content: center;
        }
    </style>

    <section class="section section--features-hero" id="features-hero">
        <div class="container max-w-5xl text-center space-y-4">
            <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">What Renlo does</p>
            <h1 class="text-4xl font-semibold text-text-base leading-tight">Projects, clients, billing, and portals kept together.</h1>
            <p class="text-base text-text-subtle">
                Run every client engagement—creative or trades—inside one system that brings projects, tasks, messaging, invoices, and portals into a single, reliable flow.
            </p>
            <div class="flex flex-wrap justify-center gap-3">
                <a class="oh-btn oh-btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
                <a class="oh-btn oh-btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
            </div>
            <p class="text-xs text-text-subtle font-semibold tracking-[0.3em]">14-day trial · No credit card required</p>
        </div>
    </section>

    <nav class="feature-subnav" aria-label="Feature navigation">
        <div class="container feature-subnav__row">
            @foreach ($sections as $section)
                <a href="#{{ $section['id'] }}">{{ explode('&', $section['eyebrow'])[0] }}</a>
            @endforeach
        </div>
    </nav>

    <section class="py-16 bg-slate-50">
        <div class="mx-auto max-w-6xl space-y-16 px-6 lg:px-0">
            @foreach ($sections as $section)
                @include('marketing.features._workflow-section', $section)
                @if ($loop->iteration === 3)
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="oh-card oh-card--muted p-6 space-y-3">
                            <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">Without structure</p>
                            <h3 class="text-lg font-semibold text-text-base">Scattered tools</h3>
                            <ul class="space-y-2 text-sm text-text-subtle list-disc list-inside">
                                <li>Tasks, billing, and messaging live in different tabs.</li>
                                <li>Clients track progress via email, not the portal.</li>
                                <li>Invoicing status gets lost in spreadsheets.</li>
                            </ul>
                        </div>
                        <div class="oh-card p-6 space-y-3">
                            <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">With Renlo</p>
                            <h3 class="text-lg font-semibold text-text-base">One connected workflow</h3>
                            <ul class="space-y-2 text-sm text-text-subtle list-disc list-inside">
                                <li>Projects show tasks, clients, and invoices side-by-side.</li>
                                <li>Clients interact inside the portal rather than email.</li>
                                <li>Invoices stay tied to the project so you always know what’s true.</li>
                            </ul>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    <section class="py-16 bg-slate-100">
        <div class="mx-auto max-w-5xl space-y-4 px-6">
            <h2 class="text-3xl font-semibold text-text-base">Finish features with one confident move.</h2>
            <p class="text-base text-text-subtle leading-relaxed">
                Bring projects, client records, billing, and scheduling into Renlo and move from plan to payment without flipping between tools.
            </p>
            <div class="flex flex-wrap gap-3">
                <a class="oh-btn oh-btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
                <a class="oh-btn oh-btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
            </div>
        </div>
    </section>
@endsection
