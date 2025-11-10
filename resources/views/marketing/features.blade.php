@extends('layouts.marketing')
@section('title', 'Optic Hub — Features')

@section('content')
@php
  // If you want a custom <title> beyond @section('title'), keep this var for meta tags later:
  $pageTitle = 'Features | Optic Hub — CRM Clarity for Creatives';
@endphp

<!-- HERO -->
<section class="section" id="features-hero">
  <div class="container">
    <p class="eyebrow">What Optic Hub does</p>
    <h1 class="h2">Features built for the way you work.</h1>
    <p class="copy">Clients, projects, calendar, invoices, a client portal, and templates—organized in one calm hub so work keeps moving.</p>
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

<!-- CLIENTS -->
<section id="clients" class="section feature-deep">
  <div class="container grid-2">
    <div class="feature-deep__visual">
      <figure class="screenshot">
        <img src="{{ asset('images/feat-clients@2x.jpg') }}" alt="Client record with notes, files, and linked invoices">
      </figure>
    </div>
    <div class="feature-deep__content">
      <p class="eyebrow">Clients & Contacts</p>
      <h2 class="h3">Know every client by name—details, files, and history in one place.</h2>
      <p class="copy">From first inquiry to final invoice, every client’s information, notes, and files stay neatly connected. No more searching across spreadsheets or email threads—just clarity, all in one record.</p>
      <ul class="checklist">
        <li>Tag and filter clients instantly</li>
        <li>Attach files and call notes to the record</li>
        <li>See linked projects, invoices, and status</li>
        <li>Share updates via the client portal</li>
      </ul>
      <p class="before-after"><strong>Before:</strong> scattered docs and email chains. <strong>After:</strong> one clean record.</p>
      <details class="faq">
        <summary>Can I import my existing contacts?</summary>
        <p>Yes. CSV import maps name, email, phone, tags, and notes in minutes.</p>
      </details>
      <blockquote class="micro-quote">“I stopped hunting for files—everything’s on the client record.” <span>— Jamie, Studio Owner</span></blockquote>
      <div class="btn-row">
        <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
        <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
      </div>
    </div>
  </div>
</section>

<!-- PROJECTS -->
<section id="projects" class="section feature-deep">
  <div class="container grid-2">
    <div class="feature-deep__visual">
      <figure class="screenshot">
        <img
          src="{{ asset('images/features/projects-tasks@2x.jpg') }}"
          alt="Projects and task board with stages, due dates, and assignments in Optic Hub">
      </figure>
    </div>
    <div class="feature-deep__content">
      <p class="eyebrow">Projects & Tasks</p>
      <h3 class="h3">Keep every project on track—without the sticky notes.</h3>
      <p class="copy">Plan projects, assign work, and stay on top of every deadline—so you can focus on delivering, not managing.</p>
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
        <p>Yes. Save any project setup—including stages, due dates, and assigned roles—and reuse it with one click.</p>
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
  </div>
</section>

<!-- INVOICES -->
<section id="invoices" class="section feature-deep">
  <div class="container grid-2">
    <div class="feature-deep__visual">
      <figure class="screenshot">
        <img
          src="{{ asset('images/features/invoices-payments@2x.jpg') }}"
          alt="Invoice dashboard showing payment status and secure Stripe checkout in Optic Hub">
      </figure>
    </div>
    <div class="feature-deep__content">
      <p class="eyebrow">Invoices & Payments</p>
      <h3 class="h3">Send invoices that get paid—without the back-and-forth.</h3>
      <p class="copy">From first estimate to final payment, Optic Hub keeps your billing process organized and professional. Create branded invoices, track paid status in real time, and let clients pay securely online with Stripe—no awkward reminders required.</p>
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
        <summary>Can clients pay directly through Optic Hub?</summary>
        <p>Yes. Each invoice includes a secure Stripe checkout link, so clients can pay online instantly—no extra accounts required.</p>
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
  </div>
</section>

<!-- CLIENT PORTAL -->
<section id="portal" class="section feature-deep">
  <div class="container grid-2">
    <div class="feature-deep__visual">
      <figure class="screenshot">
        <img
          src="{{ asset('images/features/client-portal@2x.jpg') }}"
          alt="Client portal dashboard showing shared updates, files, and approvals in Optic Hub">
      </figure>
    </div>
    <div class="feature-deep__content">
      <p class="eyebrow">Client Portal</p>
      <h3 class="h3">Share updates and collect approvals—without inbox chaos.</h3>
      <p class="copy">Give your clients a simple, branded space where they can see project progress, review files, and leave feedback—all without endless email threads. Optic Hub’s client portal makes communication effortless, organized, and beautifully on-brand.</p>
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
        <p>Yes. You can share secure, time-limited links for quick reviews—no login required for simple approvals.</p>
      </details>
      <blockquote class="micro-quote">
        “My clients love how easy it is to see updates. It keeps everything clear, and I spend less time chasing replies.”
        <span>— Jordan, Web Designer</span>
      </blockquote>
      <div class="btn-row">
        <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
        <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
      </div>
    </div>
  </div>
</section>

<!-- TEMPLATES -->
<section id="templates" class="section feature-deep">
  <div class="container grid-2">
    <div class="feature-deep__visual">
      <figure class="screenshot">
        <img
          src="{{ asset('images/features/templates-automations@2x.jpg') }}"
          alt="Project template in Optic Hub with stages, tasks, and date offsets">
      </figure>
    </div>
    <div class="feature-deep__content">
      <p class="eyebrow">Templates & Automations</p>
      <h3 class="h3">Do the setup once—reuse it every time.</h3>
      <p class="copy">
        Turn a project you like into a reusable template. When you start a new job, pick the template, set a start date, and Optic Hub pre-fills stages, tasks, and due dates for you. Add gentle reminders to keep work moving.
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
        <p>No. Begin with templates and relative dates. You can add lightweight reminders later without changing your process.</p>
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
  </div>
</section>

<!-- CALENDAR (compact card callout) -->
<section id="calendar" class="section">
  <div class="container">
    <article class="card feature">
      <div class="feature__icon"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></div>
      <div class="feature__body">
        <h2 class="h4">Calendar & Scheduling</h2>
        <p class="copy">Deadlines and meetings that actually mirror your week.</p>
        <ul class="clean">
          <li>Milestones and task due dates in one view</li>
          <li>Lightweight reminders & statuses</li>
          <li>Color-coded clarity for what’s next</li>
        </ul>
        <p class="feature__tagline">See your week, not your stress.</p>
      </div>
    </article>
  </div>
</section>

<!-- SOCIAL PROOF -->
<section class="section">
  <div class="container">
    <blockquote class="quote">
      “Optic Hub cut my email back-and-forth in half and kept every client on track.”
      <span class="quote__meta">— Jamie, Studio Owner</span>
    </blockquote>
  </div>
</section>

<!-- FINAL CTA -->
<section class="section cta">
  <div class="container">
    <h2 class="h2">Ready to trade chaos for clarity?</h2>
    <p class="copy">Try Optic Hub free for 14 days. No credit card required.</p>
    <div class="btn-row">
      <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
      <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
    </div>
  </div>
</section>
@endsection
