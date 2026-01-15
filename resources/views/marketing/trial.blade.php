@extends('layouts.trial')

@section('title', 'Start Your Free Trial | Renlo')

@section('content')
    <section class="trial-shell">
        <div class="trial-frame">

            {{-- LEFT: Brand panel --}}
            <aside class="trial-panel trial-panel--left">
                <div class="trial-left-inner">
                    <div class="trial-logo-lockup">
                        <span class="trial-logo-text">
                            Renlo
                        </span>
                        <span class="trial-pill">beta</span>
                    </div>

                    <div class="trial-left-copy">
                        <p class="trial-eyebrow">CLIENT WORK, MADE CLEAR</p>
                        <h1 class="trial-left-heading">
                            Stop juggling clients,<br>
                            projects, and invoices.
                        </h1>
                        <p class="trial-left-sub">
                            Renlo brings your work into one organized workspace—so you always know what’s next.
                        </p>

                        <ul class="trial-left-list">
                            <li> No credit card required.</li>
                            <li>Guided setup in minutes. </li>
                            <li>Built for real client work. </li>
                        </ul>
                    </div>

                    <div class="trial-left-foot">
                        <p class="trial-left-meta">
                            After you create your account, we’ll walk you through a quick setup so you start with a real
                            project—not a blank screen.
                        </p>

                        <p class="trial-left-meta trial-left-meta--copyright m-10">
                            © {{ date('Y') }} Renlo. All rights reserved.
                        </p>
                    </div>
            </aside>

            {{-- RIGHT: Form panel --}}

            <main class="trial-right">
                <div id="trial-right-inner" class="trial-right-inner">

                    {{-- Step header --}}
                    <header class="trial-step-head">
                        <h2 class="trial-step-title">Let’s get started</h2>
                        <p class="trial-step-sub">
                            This info secures your account and pre-fills your workspace so you can get moving fast.
                        </p>

                    </header>

                    {{-- Global error notice --}}
                    @if ($errors->any())
                        <div class="trial-errors">
                            <p>We found a few things to update. Please check the fields in red and try again.</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('trial.start') }}" class="trial-form">
                        @csrf

                        {{-- Name + business --}}
                        <div class="trial-row trial-row--split">
                            <label class="trial-field">
                                <span class="trial-label">Your name</span>
                                <input type="text" name="first_name"
                                    class="oh-input @error('first_name') has-error @enderror"
                                    value="{{ old('first_name') }}" autocomplete="first_name" required
                                    aria-label="Your first name">
                                @error('name')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="trial-field">
                                <span class="trial-label">Last Name</span>
                                <input type="text" name="last_name"
                                    class="oh-input @error('last_name') has-error @enderror" value="{{ old('last_name') }}"
                                    autocomplete="last_name" required aria-label="Your last name">
                                @error('name')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                        <div class="trial-row">
                            <label class="trial-field">
                                <span class="trial-label">Business / studio name</span>
                                <input type="text" name="company_name"
                                    class="oh-input @error('company_name') has-error @enderror"
                                    value="{{ old('company_name') }}" autocomplete="organization" required
                                    aria-label="Business or studio name">
                                @error('company_name')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                        <div class="trial-row">
                            <label class="trial-field">
                                <span class="trial-label">Workspace type</span>
                                <select name="workspace_type" class="oh-select @error('workspace_type') has-error @enderror">
                                    <option value="creative" @selected(old('workspace_type', 'creative') === 'creative')>
                                        Creative
                                    </option>
                                    <option value="trades" @selected(old('workspace_type') === 'trades')>
                                        Trades / Services
                                    </option>
                                </select>
                                @error('workspace_type')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>

                        {{-- Email --}}
                        <div class="trial-row">
                            <label class="trial-field">
                                <span class="trial-label">Work email</span>
                                <input type="email" name="email" class="oh-input @error('email') has-error @enderror"
                                    value="{{ old('email') }}" autocomplete="email" required aria-label="Work email">
                                @error('email')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                        <div class="trial-row">
                            <label class="trial-field">
                                <span class="trial-label">Username</span>
                                <input type="text" name="username"
                                    class="oh-input @error('username') has-error @enderror" value="{{ old('username') }}"
                                    autocomplete="username" required aria-label="Username">
                                @error('name')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                        {{-- Passwords --}}
                        <div class="trial-row trial-row--split">
                            <label class="trial-field">
                                <span class="trial-label">Password</span>
                                <input type="password" name="password"
                                    class="oh-input @error('password') has-error @enderror" autocomplete="new-password"
                                    required aria-label="Password">
                                @error('password')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="trial-field">
                                <span class="trial-label">Confirm password</span>
                                <input type="password" name="password_confirmation" class="oh-input"
                                    autocomplete="new-password" required aria-label="Confirm password">
                            </label>
                        </div>

                        {{-- Optional details --}}
                        <div class="trial-row trial-row--split">
                            <label class="trial-field">
                                <span class="trial-label">
                                    Industry
                                    <span class="trial-meta">(optional)</span>
                                </span>
                                <input type="text" name="industry"
                                    class="oh-input @error('industry') has-error @enderror" value="{{ old('industry') }}"
                                    placeholder="Web design, plumbing, architecture…" aria-label="Industry (optional)">
                                @error('industry')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="trial-field">
                                <span class="trial-label">
                                    Website
                                    <span class="trial-meta">(optional)</span>
                                </span>
                                <input type="text" name="website"
                                    class="oh-input @error('website') has-error @enderror" value="{{ old('website') }}"
                                    placeholder="https://yourstudio.com" aria-label="Website (optional)">
                                @error('website')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>

                        {{-- Terms --}}
                        <div class="trial-row">
                            <label class="trial-field trial-field--checkbox">
                                <input type="checkbox" name="agree_terms" value="1"
                                    {{ old('agree_terms') ? 'checked' : '' }} required aria-label="Agree to terms of service and privacy policy">
                                <span class="trial-label">
                                    I agree to the
                                    <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms of
                                        Service</a>
                                    and
                                    <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy
                                        Policy</a>.
                                </span>
                            </label>
                            @error('agree_terms')
                                <span class="trial-error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Submit --}}
                        <div class="trial-row" style="margin-top:.5rem;">
                            <button type="submit" class="btn btn--primary btn--full btn--lg">
                                Create Account &amp; Start Free Trial
                            </button>
                        </div>

                        <p class="meta trial-meta-foot">
                            Already have an account?
                            <a href="{{ route('login') }}">Sign in instead</a>.
                        </p>
                    </form>
                </div>
                <div id="shape-1" class="trial-blob">

                </div>
                <div id="shape-2" class="trial-blob">

                </div>
                <div id="shape-3" class="trial-blob">

                </div>
            </main>

        </div>
    </section>
@endsection
