@extends('layouts.marketing')
@section('title', 'Optic Hub - FAQ')

@section('content')
@php
$meta = [
  'title'       => 'FAQ — Optic Hub',
  'description' => 'Answers about the free trial, pricing, client portal, templates, data, and support.',
  'canonical'   => 'https://portal.causeywebsolutions.com/faq',
  'image'       => 'https://portal.causeywebsolutions.com/og/faq.jpg'
];
@endphp

<!-- HERO -->
<section class="section" id="faq-hero">
  <div class="container">
    <p class="eyebrow">Questions, answered</p>
    <h1 class="h2">FAQ</h1>
    <p class="copy">Short, straightforward answers about trial, pricing, features, and data—so you can move forward with confidence.</p>
  </div>
</section>

<!-- GENERAL -->
<section class="section" id="faq-general">
  <div class="container">
    <h2 class="h3">Getting started</h2>

    <details class="faq">
      <summary>Do I need a credit card for the 14-day trial?</summary>
      <p>No. Explore all core features first and upgrade only if it’s a fit.</p>
    </details>

    <details class="faq">
      <summary>Can I cancel anytime?</summary>
      <p>Yes. It’s month-to-month. Cancelling stops future charges and your account won’t be billed again.</p>
    </details>

    <details class="faq">
      <summary>What happens to my data if I cancel?</summary>
      <p>You can export your data before cancelling. We retain it for a short grace period and then delete it securely.</p>
    </details>

    <details class="faq">
      <summary>Is there a free plan?</summary>
      <p>Not currently. We keep pricing simple: one plan with full access and a free trial to test everything.</p>
    </details>
  </div>
</section>

<!-- FEATURES -->
<section class="section" id="faq-features">
  <div class="container">
    <h2 class="h3">Features & workflow</h2>

    <details class="faq">
      <summary>Does Optic Hub include a client portal?</summary>
      <p>Yes. Share updates, files, and approvals in one simple place—reducing email back-and-forth. You can also use secure, time-limited links for quick reviews.</p>
    </details>

    <details class="faq">
      <summary>How do templates work?</summary>
      <p>Save any project as a template with stages, tasks, and relative due dates. When you start a new project, pick a template and a start date—everything pre-fills.</p>
    </details>

    <details class="faq">
      <summary>Can I customize stages?</summary>
      <p>Yes. You can rename stage labels to match your workflow and hide stages you don’t use. Templates reference stable stage slugs so renaming won’t break them.</p>
    </details>

    <details class="faq">
      <summary>Do you support automations?</summary>
      <p>Light automations are available (due-soon and overdue reminders). More automation options are on the roadmap.</p>
    </details>
  </div>
</section>

<!-- BILLING -->
<section class="section" id="faq-billing">
  <div class="container">
    <h2 class="h3">Billing & payments</h2>

    <details class="faq">
      <summary>How do clients pay invoices?</summary>
      <p>Invoices include a secure Stripe checkout. Clients can pay online by card and you’ll see paid status automatically.</p>
    </details>

    <details class="faq">
      <summary>Do you charge setup or hidden fees?</summary>
      <p>No. One plan, one price. No setup fees or add-ons.</p>
    </details>

    <details class="faq">
      <summary>Do you offer team pricing?</summary>
      <p>Team workspaces are planned. For now, Optic Hub is optimized for solo creatives and small studios.</p>
    </details>
  </div>
</section>

<!-- DATA & SECURITY -->
<section class="section" id="faq-security">
  <div class="container">
    <h2 class="h3">Data & security</h2>

    <details class="faq">
      <summary>Is my data secure?</summary>
      <p>Yes. We use encrypted storage, role-based access, and Stripe for payments. You can export your data anytime.</p>
    </details>

    <details class="faq">
      <summary>Do you integrate with other tools?</summary>
      <p>Core flows are built-in (clients, projects, invoices, portal). Selected integrations are planned based on customer demand.</p>
    </details>

    <details class="faq">
      <summary>Where is my data hosted?</summary>
      <p>In reputable, secure U.S.-based data centers. We continuously review hosting and security best practices.</p>
    </details>
  </div>
</section>

<!-- CONTACT / SOFT CTA -->
<section class="section cta">
  <div class="container">
    <h2 class="h2">Still have a question?</h2>
    <p class="copy">We’re happy to help. Tell us what you’re trying to do and we’ll point you in the right direction.</p>
    <div class="btn-row">
      <a class="btn btn--ghost" href="/contact">Contact</a>
      <a class="btn" href="/features">Explore the Features</a>
    </div>
  </div>
</section>

<script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [{
        "@type": "Question",
        "name": "Do I need a credit card for the 14-day trial?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "No. Explore all core features first and upgrade only if it’s a fit."
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
        "name": "What happens to my data if I cancel?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "You can export your data before cancelling. We retain it briefly and then delete it securely."
        }
      },
      {
        "@type": "Question",
        "name": "Does Optic Hub include a client portal?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Yes. Share updates, files, and approvals in one place, including secure review links."
        }
      },
      {
        "@type": "Question",
        "name": "How do templates work?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Save any project as a template with stages/tasks and relative due dates, then prefill new projects from it."
        }
      },
      {
        "@type": "Question",
        "name": "Can I customize stages?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Yes—rename labels and hide unused stages. Templates reference stable slugs so renaming won’t break them."
        }
      },
      {
        "@type": "Question",
        "name": "How do clients pay invoices?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Invoices include a secure Stripe checkout. Clients pay online and paid status updates automatically."
        }
      },
      {
        "@type": "Question",
        "name": "Is my data secure?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Yes. Encrypted storage, role-based access, and Stripe for payments. Export anytime."
        }
      }
    ]
  }
</script>

@endsection