@extends('layouts.marketing')
@section('title', 'Renlo — Features')

@section('content')
    @php
        $pageTitle = 'Features | Renlo — Client work clarity';
    @endphp

    <!-- HERO -->
    <section class="section section--features-hero" id="features-hero">
        <div class="container">
            <p class="eyebrow">What Renlo does</p>
            <h1 class="h2">Everything you need to run client work—without the clutter.</h1>
            <p class="copy">
                Clients, projects, calendar, invoices, a client portal, and templates—organized in one place so work keeps
                moving.
            </p>
            <div class="btn-row">
                <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
                <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
            </div>
        </div>
    </section>

    <!-- STICKY SUBNAV -->
    <nav class="feature-subnav" aria-label="Feature navigation">
        <div class="container feature-subnav__row">
            <a href="#clients">Clients</a>
            <a href="#projects">Projects</a>
            <a href="#calendar">Calendar</a>
            <a href="#invoices">Invoices</a>
            <a href="#portal">Client Portal</a>
            <a href="#templates">Templates</a>
        </div>
    </nav>

    {{-- FEATURE STACK --}}
    <section class="section section--feature-stack pb-50">
        <div class="container feature-stack">

            {{-- CLIENTS --}}
            <article id="clients" class="feature-card feature-card--left">
                <div class="feature-card__visual">
                    <figure class="screenshot-frame">
                        <img src="{{ asset('images/feat-clients@2x.jpg') }}"
                            alt="Client record with notes, files, and linked invoices">
                    </figure>
                </div>
                <div class="feature-card__content">
                    <p class="eyebrow">Clients & Contacts</p>
                    <h2 class="h3">When you need context, it’s already there.</h2>
                    <p class="copy">
                        From first inquiry to paid invoice, every client’s information, notes, and files stay together—no
                        hunting through spreadsheets or email threads.
                    </p>
                    <ul class="checklist">
                        <li>Tag and filter clients instantly</li>
                        <li>Attach files and call notes to the record</li>
                        <li>See linked projects, invoices, and status</li>
                        <li>Share updates via the client portal</li>
                    </ul>
                    <p class="before-after">
                        <strong>Before:</strong> scattered docs and email chains.
                        <strong>After:</strong> one clean record.
                    </p>
                    <details class="faq">
                        <summary>Can I import my existing contacts?</summary>
                        <p>Yes. CSV import maps name, email, phone, tags, and notes in minutes.</p>
                    </details>
                    <blockquote class="micro-quote">
                        “I stopped hunting for files—everything’s on the client record.”
                        <span>— Jamie, Studio Owner</span>
                    </blockquote>
                    <div class="btn-row">
                        <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
                        <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
                    </div>
                </div>
            </article>

            {{-- PROJECTS --}}
            <article id="projects" class="feature-card feature-card--right">
                <div class="feature-card__visual">
                    <figure class="screenshot-frame">
                        <img src="{{ asset('images/features/projects-tasks@2x.jpg') }}"
                            alt="Projects and task board with stages, due dates, and assignments in Renlo">
                    </figure>
                </div>
                <div class="feature-card__content">
                    <p class="eyebrow">Projects & Tasks</p>
                    <h3 class="h3">You always know what’s next—and what’s blocked.</h3>
                    <p class="copy">
                        Plan stages, assign work, and see status at a glance so you can focus on delivering, not chasing
                        updates.
                    </p>
                    <ul class="checklist">
                        <li>Stage-based project views for clear progress</li>
                        <li>Assign owners, set due dates, and track status</li>
                        <li>Drag-and-drop tasks between stages</li>
                        <li>Save reusable templates for repeat work</li>
                        <li>Filter by client, status, or date for instant clarity</li>
                    </ul>
                    <p class="before-after">
                        <strong>Before:</strong> scattered to-dos and sticky notes.
                        <strong>After:</strong> one organized view that moves projects forward.
                    </p>
                    <details class="faq">
                        <summary>Can I create recurring project templates?</summary>
                        <p>Yes. Save any project setup—including stages, due dates, and assigned roles—and reuse it with one
                            click.</p>
                    </details>
                    <blockquote class="micro-quote">
                        “Now I know exactly what’s in progress and what’s next. It keeps my team aligned every day.”
                        <span>— Riley, Design Studio Lead</span>
                    </blockquote>
                    <div class="btn-row">
                        <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
                        <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
                    </div>
                </div>
            </article>

            {{-- INVOICES --}}
            <article id="invoices" class="feature-card feature-card--left">
                <div class="feature-card__visual">
                    <figure class="screenshot-frame">
                        <img src="{{ asset('images/features/invoices-payments@2x.jpg') }}"
                            alt="Invoice dashboard showing payment status and secure Stripe checkout in Renlo">
                    </figure>
                </div>
                <div class="feature-card__content">
                    <p class="eyebrow">Invoices & Payments</p>
                    <h3 class="h3">Get paid without chasing clients.</h3>
                    <p class="copy">
                        From first estimate to final payment, Renlo keeps billing organized. Create branded invoices, track
                        status in real time, and let clients pay securely online—no awkward reminders.
                    </p>
                    <ul class="checklist">
                        <li>Create and send invoices or estimates in seconds</li>
                        <li>Convert estimates to invoices with one click</li>
                        <li>Track paid and overdue statuses automatically</li>
                        <li>Accept credit card payments via Stripe</li>
                        <li>Generate receipts and payment history instantly</li>
                    </ul>
                    <p class="before-after">
                        <strong>Before:</strong> chasing payments across emails and spreadsheets.
                        <strong>After:</strong> invoices sent, tracked, and paid—all in one place.
                    </p>
                    <details class="faq">
                        <summary>Can clients pay directly through Renlo?</summary>
                        <p>Yes. Each invoice includes a secure Stripe checkout link for instant online payment.</p>
                    </details>
                    <blockquote class="micro-quote">
                        “Getting paid used to take weeks. Now, I send an invoice and see it cleared within a day.”
                        <span>— Morgan, Branding Consultant</span>
                    </blockquote>
                    <div class="btn-row">
                        <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
                        <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
                    </div>
                </div>
            </article>

            {{-- CLIENT PORTAL --}}
            <article id="portal" class="feature-card feature-card--right">
                <div class="feature-card__visual">
                    <figure class="screenshot-frame">
                        <img src="{{ asset('images/features/client-portal@2x.jpg') }}"
                            alt="Client portal dashboard showing shared updates, files, and approvals in Renlo">
                    </figure>
                </div>
                <div class="feature-card__content">
                    <p class="eyebrow">Client Portal</p>
                    <h3 class="h3">Clients stay informed without constant check-ins.</h3>
                    <p class="copy">
                        Give clients a simple, branded space to see progress, review files, and leave feedback—without
                        endless email threads.
                    </p>
                    <ul class="checklist">
                        <li>Share project updates, timelines, and deliverables</li>
                        <li>Upload files for review and collect client feedback</li>
                        <li>Keep comments and approvals tied to the right project</li>
                        <li>Control client visibility with private or shared views</li>
                        <li>Branded portal that reflects your studio identity</li>
                    </ul>
                    <p class="before-after">
                        <strong>Before:</strong> scattered feedback and missed messages.
                        <strong>After:</strong> one calm, professional space your clients will actually enjoy using.
                    </p>
                    <details class="faq">
                        <summary>Can clients access the portal without an account?</summary>
                        <p>Yes. You can share secure, time-limited links for quick reviews—no login required.</p>
                    </details>
                    <blockquote class="micro-quote">
                        “My clients love how easy it is to see updates. It keeps everything clear, and I spend less time
                        chasing replies.”
                        <span>— Jordan, Web Designer</span>
                    </blockquote>
                    <div class="btn-row">
                        <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
                        <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
                    </div>
                </div>
            </article>

            {{-- TEMPLATES --}}
            <article id="templates" class="feature-card feature-card--left">
                <div class="feature-card__visual">
                    <figure class="screenshot-frame">
                        <img src="{{ asset('images/features/templates-automations@2x.jpg') }}"
                            alt="Project template in Renlo with stages, tasks, and date offsets">
                    </figure>
                </div>
                <div class="feature-card__content">
                    <p class="eyebrow">Templates & Automations</p>
                    <h3 class="h3">Do the setup once—reuse it every time.</h3>
                    <p class="copy">
                        Turn a project you like into a reusable template. Set the start date and Renlo pre-fills stages,
                        tasks, and due dates for you.
                    </p>
                    <ul class="checklist">
                        <li>Save any project as a template</li>
                        <li>Auto-apply stages, tasks, and owners</li>
                        <li>Relative due dates from a chosen start date</li>
                        <li>Optional “due soon” and “overdue” reminders</li>
                    </ul>
                    <p class="before-after">
                        <strong>Before:</strong> rebuilding the same plan from scratch.
                        <strong>After:</strong> consistent projects that start in minutes.
                    </p>
                    <details class="faq">
                        <summary>Do I need full automation to start?</summary>
                        <p>No. Start with templates and relative dates; add reminders later if you need them.</p>
                    </details>
                    <blockquote class="micro-quote">
                        “Templates turned our kickoff from 2 hours into 10 minutes.”
                        <span>— Casey, Agency Owner</span>
                    </blockquote>
                    <div class="btn-row">
                        <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
                        <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
                    </div>
                </div>
            </article>



            {{-- CALENDAR FEATURE BAND --}}
            <article id="calendar" class="feature-card feature-card--right">
                <div class="feature-card__visual">
                    <figure class="screenshot-frame">
                        <img src="{{ asset('images/features/templates-automations@2x.jpg') }}"
                            alt="Project template in Renlo with stages, tasks, and date offsets">
                    </figure>
                </div>

                <div class="feature-card__content">
                    <p class="eyebrow">Calendar & Scheduling</p>
                    <h3 class="h3">Your schedule reflects real work—not wishful planning.</h3>

                    <p class="copy">
                        Deadlines, meetings, and milestones mirror your real schedule, so you know what needs attention next.
                    </p>


                    <ul class="checklist">
                        <li>Milestones and task due dates in one timeline</li>
                        <li>Lightweight reminders and statuses you can scan at a glance</li>
                        <li>Color-coded clarity for today, this week, and what’s coming up</li>
                    </ul>
                    <p class="before-after">
                        <strong>Before:</strong> overstuffed calendars that didn’t match reality.<br>
                        <strong>After:</strong> a timeline you can actually follow.
                    </p>


                    <details class="faq">
                        <summary>Do I need full automation to start?</summary>
                        <p>No. Start with templates and relative dates; add reminders later if you need them.</p>
                    </details>
                    <blockquote class="micro-quote">
                        “Templates turned our kickoff from 2 hours into 10 minutes.”
                        <span>— Casey, Agency Owner</span>
                    </blockquote>
                    <div class="btn-row">
                        <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
                        <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
                    </div>
                </div>
            </article>

        </div>
        <div class="section-divider"></div>
        {{-- FINAL CTA SECTION --}}
        <section class="section section--cta final-cta final-cta--dark">
            <div class="container final-cta__container">
                <div class="final-cta__content">
                    <p class="final-cta__eyebrow">Ready for a calmer way to run your studio?</p>

                    <h2 class="h2 final-cta__headline">Turn your client work into one calm hub.</h2>

                    <p class="final-cta__copy">
                        Bring clients, projects, invoices, your calendar, and a client portal into one place—so you always
                        know what’s next and nothing slips through the cracks.
                    </p>

                    <ul class="checklist final-cta__features">
                        <li>See your week, projects, and clients at a glance</li>
                        <li>Keep files, feedback, and invoices tied to every record</li>
                        <li>Replace scattered tools with one streamlined workflow</li>
                    </ul>

                    <blockquote class="micro-quote final-cta__proof">
                        “Templates turned our kickoff from 2 hours into 10 minutes.”
                        <span id="cta-proof">— Casey, Agency Owner</span>
                    </blockquote>

                    <div class="btn-row final-cta__actions">
                        <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
                        <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
                    </div>

                    <p class="meta final-cta__meta">
                        14-day free trial · No credit card required.
                    </p>
                </div>
            </div>
        </section>



    </section>
@endsection
