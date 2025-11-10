@extends('layouts.marketing')
@section('title', 'Optic Hub - Home')

@section('content')
<!-- HERO -->
<section class="oh-hero oh-hero--aperture" id="home">
  <div class="oh-hero__inner">
    <h1><span class="grad-text">Clarity</span> for creatives who juggle clients.</h1>
    <p class="sub">
      Keep projects, timelines, and invoices in one calm place—so you can focus on the work you love.
    </p>

    <div class="btn-row">
      <a class="btn btn--primary btn--glow" href="/trial/start">Start Free Trial</a>
      <a class="btn btn--ghost" href="#demo">Book a Demo</a>
    </div>

    <p class="meta">14-day free trial. Month-to-month. Stripe-secured.</p>
  </div>
</section>


<!-- WHY OPTIC HUB -->
<section class="section" id="why">
  <div class="container container--narrow copy">
    <div class="eyebrow">Why Optic Hub</div>
    <h2 class="h2">From chaos to client clarity—fast.</h2>
    <ul>
      <li><strong>All your work, organized.</strong> Clients, files, tasks and billing -- together at last. </li>
      <li><strong>Built for creatives. </strong> Simple, intuitive flows that feel familiar (not enterprise bloat). </li>
      <li><strong>Peace of mind. </strong> See what's due, what's next, and what's done -- without the mental load. </li>
    </ul>
  </div>
</section>

<!-- CORE VALUE POINTS (SCANNABLE) -->
<section class="section section--white section--scannable">
  <div class="container container--narrow copy">
    <h2 class="h2">Zero-setup templates for projects & retainers</h2>
    <ul class="clean">
      <li><span class="icon-check"></span> Calendar that actually mirrors your week</li>
      <li><span class="icon-check"></span> One-click invoices & paid status tracking</li>
      <li><span class="icon-check"></span> Notes & files right on the client record</li>
      <li><span class="icon-check"></span> Clean client portal (fewer emails, faster approvals)</li>
      <li><span class="icon-check"></span> Light, fast, and friendly on mobile</li>
    </ul>
  </div>
</section>

<!-- FEATURES GRID -->
<section class="section section--brand" id="features">
  <div class="container">
    <h2 class="h2">Tools that bring clarity to your day.</h2>
    <p class="copy"> Everything you need to manage clients, projects, and payments—without the complexity.</p>
    <div class="cards">
      <article id="clients" class="card feature">
        <div class="feature__icon" aria-hidden="true"><i class="fa fa-solid fa-users"></i></div>
        <div class="feature__body">
          <h3 class="h4">Clients & Contacts</h3>
          <p class="copy"> Keep every client detail, file, and note in one clean record.</p>
          <p class="feature__tagline">
            Know every client by name.
          </p>
          <a class="link link--tiny" href="/features#clients">Learn more →</a>

        </div>

      </article>

      <article id="projects" class="card feature">
        <div class="feature__icon feature__icon--accent" aria-hidden="true"><i class="fa fa-solid fa-list-check"></i></div>
        <div class="feature__body">
          <h3 class="h4">Projects and Tasks</h3>
          <p class="copy">
            Plan, assign, and check off work without the clutter.
          </p>
          <p class="feature__tagline">
            Focus on progress, not lists.
          </p>
          <a class="link link--tiny" href="/features#projects">Learn more →</a>
        </div>
      </article>

      <article id="calendar" class="card feature">
        <div class="feature__icon" aria-hidden="true"><i class="fa fa-solid fa-calendar-days"></i></div>
        <div class="feature__body">
          <h3 class="h4">Calendar & Scheduling</h3>
          <p class="copy">
            Deadlines and meetings sync automatically to your week view.
          </p>
          <p class="feature__tagline">
            See your week, not your stress.
          </p>
          <a class="link link--tiny" href="/features#calendar">Learn more →</a>
        </div>
      </article>

      <article id="invoices" class="card feature">
        <div class="feature__icon feature__icon--accent" aria-hidden="true"><i class="fa fa-solid fa-file-invoice-dollar"></i></div>
        <div class="feature__body">
          <h3 class="h4">Estimates & Invoices</h3>
          <p class="copy">
            Send quotes, track payments, and get paid faster with Stripe.
          </p>
          <p class="feature__tagline">
            No chasing payments.
          </p>
          <a class="link link--tiny" href="/features#invoices">Learn more →</a>
        </div>
      </article>

      <article id="portal" class="card feature">
        <div class="feature__icon" aria-hidden="true"><i class="fa fa-solid fa-handshake"></i></div>
        <div class="feature__body">
          <h3 class="h4">Client Portal</h3>
          <p class="copy">
            Clients can review, approve, and upload—all in one place.
          </p>
          <p class="feature__tagline">
            Fewer emails. Faster approvals.
          </p>
          <a class="link link--tiny" href="/features#portal">Learn more →</a>
        </div>
      </article>

      <article id="templates" class="card feature">
        <div class="feature__icon feature__icon--accent" aria-hidden="true"><i class="fa fa-solid fa-wand-magic-sparkles"></i></div>
        <div class="feature__body">
          <h3 class="h4">Templates & Automations</h3>
          <p class="copy">
            Save recurring projects and let Optic Hub handle the routine.
          </p>
          <p class="feature__tagline">
            Do the work once, repeat the wins. </p>
          <a class="link link--tiny" href="/features#automations">Learn more →</a>
        </div>
      </article>

    </div>

    <!-- Roadmap callouts (optional) -->
    <div class="copy" style="margin-top:20px">
      <p class="meta">
        Optic Hub keeps your creative workflow organized—clients, projects, billing, and collaboration—all in one place.
      </p>
    </div>
  </div>
</section>


<!-- USE CASES — CREATIVES FOCUS -->
<section class="section" id="use-cases">
  <div class="container section--brand">
    <p class="eyebrow">Who it's for</p>
    <h2 class="h2">Made for creatives who wear all the hats.</h2>
    <p class="copy">Designers, photographers, videographers, and small studios use Optic Hub to keep
      inquiries, projects, and payments in one calm place—so work keeps moving.</p>

    <div class="usecases">
      <article class="card usecase" data-uc="design-web-studio">
        <a class="usecase__link" href="/features#projects" aria-label="Learn more: Projects & Tasks"></a>
        <div class="usecase__badge"><i class="fa-solid fa-palette" aria-hidden="true"></i>Design & Web Studios</div>
        <h3 class="h4"> Ship projects without the scramble </h3>
        <p class="copy"> Capture project requests, plan next steps, and keep approvals moving.</p>
        <ul class="clean">
          <li>Branded proposals & invoices</li>
          <li>Project stages with due dates</li>
        </ul>
      </article>

      <article class="card usecase" data-uc="photographers">
        <a class="usecase__link" href="/features#invoices" aria-label="Learn more: Estimates & Invoices"></a>
        <span class="usecase__badge"><i class="fa-solid fa-camera" aria-hidden="true"></i> Photographers</span>
        <h3 class="h4">From inquiry to paid gallery—clean.</h3>
        <p class="copy">Book sessions, track edits, and send invoices without switching tools.</p>
        <ul class="clean">
          <li>Packages, deposits, confirmations</li>
          <li>Portal updates (fewer emails)</li>
        </ul>
      </article>

      <article class="card usecase" data-uc="creators-coaches">
        <a class="usecase__link" href="/features#templates" aria-label="Learn more: Templates & Automations"></a>
        <span class="usecase__badge"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> Creators & Coaches</span>
        <h3 class="h4">Packages that sell, workflows that repeat.</h3>
        <p class="copy">Productize services and let templates do the busywork.</p>
        <ul class="clean">
          <li>Service packages & retainers</li>
          <li>Recurring tasks & reminders</li>
        </ul>
      </article>

      <!-- Trades & Services -->
      <article class="card usecase" data-uc="trades-services">
        <a class="usecase__link" href="/features#invoices" aria-label="Learn more: Estimates & Invoices"></a>
        <span class="usecase__badge"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Trades & Services</span>
        <h3 class="h4">Jobs, estimates, and scheduling—together.</h3>
        <p class="copy">Track site visits, send quotes, and collect payments on time.</p>
        <ul class="clean">
          <li>On-site notes and photos</li>
          <li>Simple invoices & receipts</li>
        </ul>
      </article>

      <!-- Freelances & Solopreneurs -->
      <article class="card usecase" data-uc="freelancers">
        <a class="usecase__link" href="/features#portal" aria-label="Learn more: Client Portal"></a>
        <span class="usecase__badge"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Freelancers & Solopreneurs</span>
        <h3 class="h4">Stay on top of clients and cash flow—without the spreadsheet shuffle.</h3>
        <p class="copy">Keep projects moving and payments predictable—without the spreadsheet shuffle.</p>
        <ul class="clean">
          <li>Invoices & paid status at a glance</li>
          <li>Tasks and next steps in one view</li>
        </ul>
      </article>
      <!-- Studio Lead / Team -->
      <article class="card usecase" data-uc="studio-lead">
        <a class="usecase__link" href="/features#overview" aria-label="Learn more"></a>
        <span class="usecase__badge"><i class="fa-solid fa-people-group" aria-hidden="true"></i> Studio Lead</span>
        <h3 class="h4">See workload at a glance. Ship on time.</h3>
        <p class="copy">One view of people, projects, and payments—no spreadsheet chaos.</p>
        <ul class="clean">
          <li>Team assignments & capacity</li>
          <li>Milestones & timelines</li>
        </ul>
      </article>
    </div>


    <div class="card">
      <div class="eyebrow">Suggested pipeline</div>
      <div class="copy" style="margin-top:10px;">
        <span class="badge">Inquiry</span>
        <span class="badge">Discovery</span>
        <span class="badge">Proposal Sent</span>
        <span class="badge">In Production</span>
        <span class="badge">Delivered / Archived</span>
      </div>

      <div class="btn-row" style="margin-top:12px;">
        <a class="btn btn--primary" href="/trial/start">Start Free Trial</a>
        <a class="btn btn--ghost" href="#demo">Book a Demo</a>
      </div>
    </div>
  </div>
</section>



<!-- HOW IT WORKS -->
<section class="section section--white" id="how-it-works">
  <div class="container container--narrow">
    <h2 class="h2">From signup to your first lead in under 10 minutes.</h2>
    <p class="copy" style="margin-bottom:12px">
      A guided setup walks you through the essentials so you’re ready to capture your first inquiry fast.
      Prefer a personal touch? You can schedule a walkthrough call anytime.
    </p>
    <ol class="timeline">
      <li class="timeline__step">
        <div class="timeline__dot">1</div>
        <div class="card card--glass timeline__card">
          <h4 class="h4">Start Free Trial</h4>
          <p class="copy">Import clients or begin fresh.</p>
        </div>
      </li>
      <li class="timeline__step">
        <div class="timeline__dot">2</div>
        <div class="card card--glass timeline__card">
          <h4 class="h4">Pick a Template</h4>
          <p class="copy">Project, retainer, or job—pre-built for you.</p>
        </div>
      </li>
      <li class="timeline__step">
        <div class="timeline__dot">3</div>
        <div class="card card--glass timeline__card">
          <h4 class="h4">Work in Flow</h4>
          <p class="copy">Tasks, files, billing—all synced to your calendar.</p>
        </div>
      </li>
      <li class="timeline__step">
        <div class="timeline__dot">4</div>
        <div class="card card--glass timeline__card">
          <h4 class="h4">Share the Portal</h4>
          <p class="copy">Keep clients updated without email threads.</p>
        </div>
      </li>
    </ol>

    <blockquote class="card card--glass quote copy" style="margin-top:14px">
      “We went from missed calls and sticky notes to a clean pipeline with next steps for every lead. It paid for itself in the first week.”
      <br><small>— Beta user, home services</small>
    </blockquote>
  </div>
</section>

<!-- DEMO (ANCHOR ONLY; PLACE YOUR SCHEDULER/FORM HERE) -->
<section class="section section--white" id="demo">
  <div class="container container--narrow copy">
    <div class="eyebrow">Optional Walkthrough</div>
    <h2 class="h2">See the calm, not just the features.</h2>
    <p class="lead copy">A clean, intuitive dashboard that turns “Where is that?” into “Done.”</p>
    <a class="btn btn--ghost" href="#demo">Book a Demo</a>
    <!-- Embed your scheduler or contact form here -->
  </div>
</section>

<!-- PRICING -->
<section class="section section--brand" id="pricing">
  <div class="container container--narrow">
    <h2 class="h2">Simple pricing. No contracts.</h2>

    <div class="pricing__wrap">
      <div class="card card--pricing">
        <div class="card__header">
          <p class="amount">$49</p>
          <span class="per">/month</span>
        </div>

        <ul class="clean bullets">
          <li>Full access to every core feature</li>
          <li>14-day free trial — no credit card required</li>
          <li>Month-to-month billing — cancel anytime</li>
          <li>Secure checkout powered by Stripe</li>
        </ul>

        <p class="copy" style="color: var(--optic-secondary-text); margin-top:8px;">
          One simple plan. No tiers, no add-ons, no surprises.
        </p>

        <div class="btn-row" style="margin-top:16px;">
          <a class="btn btn--primary btn--glow" href="/trial/start">Start Your 14-Day Free Trial</a>
          <a class="btn btn--ghost" href="#demo">Book a Demo</a>
        </div>
      </div>
    </div>


    <div class="copy" style="margin-top:16px;">
      <p class="meta">Team plans coming soon for multi-user workspaces. Limited early Lifetime Deal may be offered at launch.</p>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section class="section cta" id="cta">
  <div class="container container--narrow">
    <h2 class="h2">Ready to trade chaos for clarity?</h2>
    <p class="lead copy">
      Join designers, photographers, and studios who run calm, focused businesses with Optic Hub.
    </p>
    <div class="btn-row">
      <a class="btn btn--primary" href="/trial/start">Start Free Trial</a>
      <a class="btn btn--ghost" href="#demo">Book a Demo</a>
    </div>
  </div>
</section>
@endsection