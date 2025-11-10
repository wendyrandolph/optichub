@extends('layouts.marketing')
@section('title', 'Optic Hub - Contact')

@section('content')
@php
  $form    = $formData ?? [];
  $started = $formStartedAt ?? now()->timestamp;
@endphp

<section class="section section--aperture" id="contact-hero">
  <div class="container">
    <p class="eyebrow">Say hello</p>
    <h1 class="h2">We’d love to hear from you.</h1>
    <p class="copy">Whether you have a question, idea, or story to share — every message helps us shape a tool that serves you better.</p>
  </div>
</section>

<section class="section" id="contact-form">
  <div class="container">
    {{-- Success / status --}}
    @if(session('status'))
      <div class="mb-4 rounded-xl bg-green-50 text-green-800 p-3 ring-1 ring-green-200">{{ session('status') }}</div>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
      <div class="mb-4 rounded-xl bg-red-50 text-red-800 p-3 ring-1 ring-red-200">
        <ul class="list-disc pl-5 space-y-1">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('contact.submit') }}" class="form form--card" novalidate>
      @csrf
      <input type="hidden" name="started_at" value="{{ (int) $started }}">

      {{-- Honeypot (keep hidden) --}}
      <div class="sr-only" aria-hidden="true">
        <label for="website">Website</label>
        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="field">
          <label for="name">Name<span aria-hidden="true">*</span></label>
          <input id="name" name="name" type="text" autocomplete="name" required
                 value="{{ old('name', $form['name'] ?? '') }}">
        </div>

        <div class="field">
          <label for="email">Email<span aria-hidden="true">*</span></label>
          <input id="email" name="email" type="email" autocomplete="email" required
                 value="{{ old('email', $form['email'] ?? '') }}">
        </div>
      </div>

      <div class="field">
        <label for="topic">Topic</label>
        <select id="topic" name="topic">
          @php $topic = old('topic', $form['topic'] ?? ''); @endphp
          <option value=""         {{ $topic === '' ? 'selected' : '' }}>General</option>
          <option value="billing"  {{ $topic === 'billing' ? 'selected' : '' }}>Billing</option>
          <option value="feature"  {{ $topic === 'feature' ? 'selected' : '' }}>Feature request</option>
          <option value="support"  {{ $topic === 'support' ? 'selected' : '' }}>Support</option>
        </select>
      </div>

      <div class="field">
        <label for="message">Message<span aria-hidden="true">*</span></label>
        <textarea id="message" name="message" rows="6" required>{{ old('message', $form['message'] ?? '') }}</textarea>
      </div>

      <div class="btn-row mt-2">
        <button class="btn btn--primary" type="submit">Send message</button>
        <a class="btn btn--ghost" href="{{ route('marketing.features') }}">Explore the Features</a>
      </div>

      <p class="meta mt-2">We’ll never share your email. Replies typically within 1–2 business days.</p>
    </form>
  </div>
</section>
@endsection
