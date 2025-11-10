@extends('layouts.marketing')
@section('title', 'About — Optic Hub')

@push('head')
  {{-- Basic SEO/Open Graph (optional) --}}
  <link rel="canonical" href="https://yourdomain.com/about">
  <meta name="description" content="Built to bring clarity to the chaos of business. Rooted in simplicity, gratitude, and purpose.">
  <meta property="og:title" content="About – Optic Hub">
  <meta property="og:description" content="Built to bring clarity to the chaos of business. Rooted in simplicity, gratitude, and purpose.">
  <meta property="og:image" content="https://yourdomain.com/og/about.jpg">
@endpush

@section('content')
  <!-- HERO -->
  <section id="about-hero" class="section section--about-hero">
    <div class="container">
      <p class="eyebrow">Why Optic Hub exists</p>
      <h1 class="h2">Built to bring clarity. Rooted in purpose.</h1>
      <p class="copy">Optic Hub was created to help creatives and small studios run calmer, more focused businesses—so the work that matters can move forward. It’s simple by design, practical on purpose, and guided by gratitude.</p>
    </div>
  </section>

  <!-- MISSION -->
  <section class="section" id="mission">
    <div class="container">
      <h2 class="h3">Our mission</h2>
      <p class="copy">
        The Optic Hub is built to bring clarity to the chaos of business management.
        Designed with the creative in mind, this intuitive, clean, and easy-to-use CRM helps
        entrepreneurs stay organized, focused, and moving forward.
        Rooted in simplicity and purpose, it’s more than software—it’s peace of mind.
      </p>

      <blockquote class="scripture">
        “For God is not the author of confusion, but of peace…”
        <span>— 1 Corinthians 14:33 (KJV)</span>
      </blockquote>
    </div>
  </section>

  <!-- STORY -->
  <section class="section">
    <div class="container">
      <h2 class="h3">The story</h2>

      <p class="copy">
        Optic Hub was born out of a search for something that didn’t quite exist—a single, intuitive place to manage projects, clients, and communication. I tried the usual tools, but none of them worked the way I think. They felt cluttered, disconnected, and harder than they needed to be. I wanted something that made visual sense, that brought calm instead of more noise.
      </p>

      <p class="copy">
        So I started building. What began as a personal solution quickly became a calling—an opportunity to design a tool that helps others experience the same clarity I was looking for. For more than six months, I’ve built, refined, and prayed through every part of this system, trusting that each idea and every line of code could serve a greater purpose.
      </p>

      <p class="copy">
        My faith has guided this process: the patience to test and rebuild until it felt right, the gratitude to keep learning, and the conviction to create something that won’t fade when trends change. I want Optic Hub to be a steady foundation—built with care, integrity, and peace at its core.
      </p>

      <p class="copy">
        When someone finds Optic Hub, I want them to feel what I felt when the idea first clicked: that sense of relief and recognition—<em>“this is what I’ve been looking for.”</em> A space designed for them, simple yet personal, where they can run their business confidently and still have time for what matters most.
      </p>
    </div>
  </section>

  <!-- FOUNDER NOTE -->
  <section class="section" id="founder-note">
    <div class="container founder-note">
      <div class="founder-note__inner">
        <div class="founder-note__text">
          <h2 class="h4">A note from the founder</h2>
          <p class="copy">
            Building Optic Hub has been one of the most challenging—and rewarding—projects I’ve ever taken on.
            It’s more than a piece of software to me; it’s a reflection of what I believe business can be when it’s guided by purpose and peace.
          </p>
          <p class="copy">
            My hope is that Optic Hub gives you space to breathe. That it frees you to create, to lead, and to spend time where it counts most.
            Every small detail has been built prayerfully, with gratitude for those who trust their work inside it.
          </p>
          <p class="copy"><strong>— Wendy Causey</strong><br>Founder, Optic Hub</p>
        </div>

        <div class="founder-note__image">
          <img src="{{ asset('images/founder-placeholder.jpg') }}" alt="Founder of Optic Hub" loading="lazy">
        </div>
      </div>
    </div>
  </section>

  <!-- VALUES -->
  <section class="section">
    <div class="container">
      <h2 class="h3">What guides the work</h2>
      <div class="cards">
        <article class="card feature">
          <div class="feature__icon"><i class="fa-solid fa-seedling" aria-hidden="true"></i></div>
          <div class="feature__body">
            <h3 class="h4">Purpose</h3>
            <p class="copy">Build tools that serve people, not the other way around.</p>
          </div>
        </article>
        <article class="card feature">
          <div class="feature__icon feature__icon--accent"><i class="fa-solid fa-heart" aria-hidden="true"></i></div>
          <div class="feature__body">
            <h3 class="h4">Gratitude</h3>
            <p class="copy">Work with humility. Celebrate small wins. Thank customers often.</p>
          </div>
        </article>
        <article class="card feature">
          <div class="feature__icon"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i></div>
          <div class="feature__body">
            <h3 class="h4">Integrity</h3>
            <p class="copy">Clear pricing, honest roadmaps, and responsible handling of data.</p>
          </div>
        </article>
        <article class="card feature">
          <div class="feature__icon feature__icon--accent"><i class="fa-solid fa-circle-notch" aria-hidden="true"></i></div>
          <div class="feature__body">
            <h3 class="h4">Simplicity</h3>
            <p class="copy">Only what’s needed—so you can think less about tools and more about work.</p>
          </div>
        </article>
        <article class="card feature">
          <div class="feature__icon"><i class="fa-solid fa-hands-praying" aria-hidden="true"></i></div>
          <div class="feature__body">
            <h3 class="h4">Faith</h3>
            <p class="copy">Serve with excellence, extend grace, and build for the long view.</p>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- APPROACH -->
  <section class="section">
    <div class="container">
      <h2 class="h3">How Optic Hub is built</h2>
      <ul class="clean">
        <li>Fast, thoughtful updates (not noisy releases)</li>
        <li>Customer-led roadmap and real-world feedback</li>
        <li>Privacy first: encrypted data, role-based access, Stripe for payments</li>
      </ul>
    </div>
  </section>

  <!-- SOFT CTA -->
  <section class="section cta">
    <div class="container">
      <h2 class="h2">Explore the tools at your pace.</h2>
      <p class="copy">See how Optic Hub keeps work organized and calm—then start your trial when you’re ready.</p>
      <div class="btn-row">
        <a class="btn btn--ghost" href="{{ route('marketing.features') }}">Explore the Features</a>
        <a class="btn" href="{{ route('contact.form') }}">Say hello</a>
      </div>
    </div>
  </section>
@endsection
@section('footer')