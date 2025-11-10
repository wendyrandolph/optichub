@extends('layouts.marketing')
@section('title', 'Optic Hub - Home')

@section('content')
<?php /* expects $csrf, $pageTitle set by controller */ ?>
<main id="mainContent" class="page-container">

  <!-- Hero / intro -->
  <section class="oh-hero oh-hero--aperture">
    <div class="oh-hero__inner">
      <h1><span class="grad-text">Start your 14-Day Trial</span></h1>
      <p class="sub">No card required. Get your workspace, onboarding link, and API key in minutes.</p>
      <p class="meta">You can add a payment method anytime. We’ll email a reminder before day 14.</p>
    </div>
  </section>

  <!-- Trial form -->
  <?php
  // expects $csrf from controller
  ?>
  <section class="section section--white">
    <div class="container container--narrow">
      <h2 class="h2">Start your 14-Day Free Trial</h2>
      <p class="copy">No credit card required. We’ll email you a secure link to set your password.</p>

      <?php if (!empty($_SESSION['trial_error'])): ?>
        <div class="card card--glass" style="padding:12px; margin:12px 0; color:#9b1c1c; background:#fff5f5;">
          <?= htmlspecialchars($_SESSION['trial_error']);
          unset($_SESSION['trial_error']); ?>
        </div>
      <?php endif; ?>

      <form action="/trial/start" method="POST" class="card card--glass" style="padding:20px; display:grid; gap:12px;">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

        <label class="copy">
          Company (optional)
          <input name="company" type="text" class="input" placeholder="Your company or project name">
        </label>

        <label class="copy">
          Email (required)
          <input name="email" type="email" class="input" placeholder="you@business.com" required>
        </label>

        <div class="btn-row">
          <button class="btn btn--primary btn--glow" type="submit">Start Free Trial</button>
          <a class="btn btn--ghost" href="/pricing">See pricing</a>
        </div>
        <p class="meta">By starting a trial, you agree to receive trial reminders and onboarding tips. You can unsubscribe anytime.</p>
      </form>
    </div>
  </section>


  <!-- Why/assurance (optional) -->
  <section class="section section--brand">
    <div class="container container--narrow copy">
      <h2 class="h2">What you’ll get during the trial</h2>
      <ul class="clean">
        <li><span class="icon-check"></span> Lead capture from your website (API key ready).</li>
        <li><span class="icon-check"></span> Pipeline & tasks to track every opportunity.</li>
        <li><span class="icon-check"></span> Quotes and invoices with Stripe (when you’re ready).</li>
        <li><span class="icon-check"></span> Client portal and clean, simple project tracking.</li>
      </ul>
    </div>
  </section>

</main>
@endsection