@extends('layouts.marketing')
@section('title', 'Pricing — Optic Hub')

@push('head')
  <link rel="canonical" href="https://yourdomain.com/pricing">
  <meta name="description" content="One simple plan. Full access. 14-day free trial.">
  <meta property="og:title" content="Pricing — Optic Hub">
  <meta property="og:description" content="One simple plan. Full access. 14-day free trial.">
  <meta property="og:image" content="https://yourdomain.com/og/pricing.jpg">
@endpush

@section('content')
  <!-- HERO -->
  <section class="section" id="pricing-hero">
    <div class="container">
      <p class="eyebrow">Simple, no surprises</p>
      <h1 class="h2">One plan that fits your growth.</h1>
      <p class="copy">Full access to every core feature. 14-day free trial — no credit card required.</p>
    </div>
  </section>

  <!-- PLAN -->
  <section class="section" id="pricing">
    <div class="container pricing__wrap">
      <article class="card card--pricing">
        <header class="card__header">
          <p class="amount">$49</p><span class="per">/month</span>
        </header>

        <ul class="clean bullets">
          <li>Full access to every core feature</li>
          <li>14-day free trial — no credit card</li>
          <li>Month-to-month — cancel anytime</li>
          <li>Secure checkout powered by Stripe</li>
        </ul>

        <p class="copy mt-2 text-gray-500">
          One simple plan. No tiers, no add-ons, no surprises.
        </p>

        <div class="btn-row mt-4">
          <a class="btn btn--primary btn--glow" href="{{ url('/trial/start') }}">Start Your 14-Day Free Trial</a>
          <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
        </div>
      </article>
    </div>
  </section>

  <!-- WHAT'S INCLUDED -->
  <section class="section">
    <div class="container cards">
      <article class="card feature">
        <div class="feature__icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
        <div class="feature__body">
          <h3 class="h4">Clients & Contacts</h3>
          <p class="copy">All client details, files, and history in one place.</p>
        </div>
      </article>
      <article class="card feature">
        <div class="feature__icon feature__icon--accent"><i class="fa-solid fa-list-check" aria-hidden="true"></i></div>
        <div class="feature__body">
          <h3 class="h4">Projects & Tasks</h3>
          <p class="copy">Stages, due dates, and templates that keep work moving.</p>
        </div>
      </article>
      <article class="card feature">
        <div class="feature__icon"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></div>
        <div class="feature__body">
          <h3 class="h4">Calendar</h3>
          <p class="copy">See milestones and meetings in one clean view.</p>
        </div>
      </article>
      <article class="card feature">
        <div class="feature__icon feature__icon--accent"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i></div>
        <div class="feature__body">
          <h3 class="h4">Invoices</h3>
          <p class="copy">Estimates → invoices, paid status, Stripe checkout.</p>
        </div>
      </article>
      <article class="card feature">
        <div class="feature__icon"><i class="fa-solid fa-handshake" aria-hidden="true"></i></div>
        <div class="feature__body">
          <h3 class="h4">Client Portal</h3>
          <p class="copy">Share updates, files, and approvals—minus email chaos.</p>
        </div>
      </article>
      <article class="card feature">
        <div class="feature__icon feature__icon--accent"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></div>
        <div class="feature__body">
          <h3 class="h4">Templates</h3>
          <p class="copy">Start projects in minutes with saved setups.</p>
        </div>
      </article>
    </div>
  </section>

  <!-- TRUST -->
  <section class="section">
    <div class="container">
      <div class="pill-list">
        <span class="badge"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Encrypted at rest</span>
        <span class="badge"><i class="fa-brands fa-stripe" aria-hidden="true"></i> Stripe payments</span>
        <span class="badge"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Role-based access</span>
        <span class="badge"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Export anytime</span>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section class="section">
    <div class="container">
      <h2 class="h3">Pricing FAQ</h2>

      <details class="faq">
        <summary>Do I need a credit card for the trial?</summary>
        <p>No. Try all core features first and upgrade when you’re ready.</p>
      </details>

      <details class="faq">
        <summary>Can I cancel anytime?</summary>
        <p>Yes—month-to-month billing. Cancelling stops future charges.</p>
      </details>

      <details class="faq">
        <summary>Is my data secure?</summary>
        <p>Yes. We use encrypted storage, role-based permissions, and Stripe for payments.</p>
      </details>

      <details class="faq">
        <summary>Do you offer team plans?</summary>
        <p>Team workspaces are coming soon. Join the waitlist during signup.</p>
      </details>
    </div>
  </section>

  <!-- FINAL CTA -->
  <section class="section cta">
    <div class="container">
      <h2 class="h2">Start free. See the calm.</h2>
      <p class="copy">Set up clients, pick a template, and send your first invoice—fast.</p>
      <div class="btn-row">
        <a class="btn btn--primary" href="{{ url('/trial/start') }}">Start Free Trial</a>
        <a class="btn btn--ghost" href="{{ route('marketing.home') }}#demo">Book a Demo</a>
      </div>
    </div>
  </section>
@endsection
@section('footer')