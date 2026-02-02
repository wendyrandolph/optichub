@extends('layouts.trial')

@section('title', 'Start Your Free Trial | Renlo')

@section('content')
    <section class="trial-shell">
        <div class="trial-frame">

            {{-- LEFT: Brand panel --}}
            <aside class="trial-panel trial-panel--left">
                <div class="trial-left-inner">
                    <div class="trial-logo-lockup">
                        <span class="trial-logo-text">Renlo</span>
                        <span class="trial-pill">beta</span>
                    </div>

                    <div class="trial-left-copy">
                        <p class="trial-eyebrow">CLIENT WORK, MADE CLEAR</p>

                        <h1 class="trial-left-heading">
                            One place to run<br>
                            client work—without<br>
                            juggling tools.
                        </h1>

                        <p class="trial-left-sub">
                            Projects, tasks, files, and billing stay connected so you always know what’s next—and what needs
                            attention.
                        </p>

                        <ul class="trial-left-list">
                            <li>No credit card required.</li>
                            <li>Start with a real project—not a blank screen.</li>
                            <li>Built for service businesses and solo operators.</li>
                        </ul>
                    </div>

                    <div class="trial-left-foot">
                        <p class="trial-left-meta">
                            After you create your account, we’ll guide a quick setup so your workspace starts in a usable
                            place—right away.
                        </p>

                        <p class="trial-left-meta trial-left-meta--copyright">
                            @php
                                $startYear = 2026;
                                $currentYear = (int) date('Y');
                                $yearRange = $startYear . ' - ' . $currentYear;
                            @endphp
                            © {{ $yearRange }} Renlo. All rights reserved.
                        </p>
                    </div>
                </div>
            </aside>

            {{-- RIGHT: Form panel --}}
            <main class="trial-right">
                <div id="trial-right-inner" class="trial-right-inner">

                    {{-- Step header --}}
                    <header class="trial-step-head">
                        {{-- Optional: step indicator (remove if you don’t want it) --}}
                        <p class="trial-step-kicker">Step 1 of 2</p>

                        <h2 class="trial-step-title">Let’s get started</h2>
                        <p class="trial-step-sub">
                            This secures your account and pre-fills your workspace so you can get moving fast.
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

                        {{-- Name --}}
                        <div class="trial-row trial-row--split">
                            <label class="trial-field">
                                <span class="trial-label">First name</span>
                                <input type="text" name="first_name"
                                    class="oh-input @error('first_name') has-error @enderror"
                                    value="{{ old('first_name') }}" autocomplete="given-name" required>
                                @error('first_name')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="trial-field">
                                <span class="trial-label">Last name</span>
                                <input type="text" name="last_name"
                                    class="oh-input @error('last_name') has-error @enderror" value="{{ old('last_name') }}"
                                    autocomplete="family-name" required>
                                @error('last_name')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>

                        {{-- Company --}}
                        <div class="trial-row">
                            <label class="trial-field">
                                <span class="trial-label">Business / studio name</span>
                                <input type="text" name="company_name"
                                    class="oh-input @error('company_name') has-error @enderror"
                                    value="{{ old('company_name') }}" autocomplete="organization" required>
                                @error('company_name')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>

                        {{-- Workspace type --}}
                        <div class="trial-row">
                            <label class="trial-field">
                                <span class="trial-label">Workspace type</span>
                                <select name="workspace_type" class="oh-select @error('workspace_type') has-error @enderror" required>
                                    <option value="creative" @selected(old('workspace_type', $workspaceType ?? 'creative') === 'creative')>Creative</option>
                                    <option value="trades" @selected(old('workspace_type', $workspaceType ?? 'creative') === 'trades')>Trades / Services</option>
                                </select>

                                <p class="trial-help">
                                    Helps us tailor templates and defaults for your work.
                                </p>

                                @error('workspace_type')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>

                        @if (!empty($leadSource))
                            <input type="hidden" name="lead_source" value="{{ $leadSource }}">
                        @endif

                        {{-- Email --}}
                        <div class="trial-row">
                            <label class="trial-field">
                                <span class="trial-label">Work email</span>
                                <input type="email" name="email" class="oh-input @error('email') has-error @enderror"
                                    value="{{ old('email') }}" autocomplete="email" required>
                                @error('email')
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
                                    required>
                                @error('password')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="trial-field">
                                <span class="trial-label">Confirm password</span>
                                <input type="password" name="password_confirmation" class="oh-input"
                                    autocomplete="new-password" required>
                            </label>
                        </div>

                        {{-- Optional --}}
                        <div class="trial-row trial-row--split">
                            <label class="trial-field">
                                <span class="trial-label">
                                    Industry <span class="trial-meta">(optional)</span>
                                </span>
                                <input type="text" name="industry"
                                    class="oh-input @error('industry') has-error @enderror" value="{{ old('industry') }}"
                                    placeholder="Web design, plumbing, architecture…">
                                @error('industry')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="trial-field">
                                <span class="trial-label">
                                    Website <span class="trial-meta">(optional)</span>
                                </span>
                                <input type="url" name="website" class="oh-input @error('website') has-error @enderror"
                                    value="{{ old('website') }}" placeholder="https://yourstudio.com"
                                    autocomplete="url">
                                @error('website')
                                    <span class="trial-error">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>

                        {{-- Terms --}}
                        <div class="trial-row">
                            <label class="trial-field trial-field--checkbox">
                                <input type="checkbox" name="agree_terms" value="1"
                                    {{ old('agree_terms') ? 'checked' : '' }} required>
                                <span class="trial-label">
                                    I agree to the
                                    <span class="terms-link">
                                        <a class="text-[rgb(var(--ui-primary))] font-semibold hover:underline" href="{{ route('terms') }}" target="_blank" rel="noopener">Terms of Service</a>
                                    </span>
                                    and
                                    <span class="terms-link">
                                        <a class="text-[rgb(var(--ui-primary))] font-semibold hover:underline" href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>
                                    </span>
                                    <span class="trial-help">
                                        We only use your info to run your workspace.
                                    </span>
                                </span>
                            </label>

                            @error('agree_terms')
                                <span class="trial-error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Submit --}}
                        <div class="trial-row" style="margin-top:.5rem;">
                            <button type="submit" class="btn btn--primary btn--full btn--lg">
                                Start your free 14-day trial
                            </button>
                            <p class="trial-help" style="margin-top:.5rem;">
                                Account setup takes about 2 minutes.
                            </p>
                        </div>

                        <p class="meta trial-meta-foot">
                            Already have an account?
                            <a href="{{ route('login') }}">Sign in instead</a>.
                        </p>
                    </form>
                </div>

                <div id="shape-1" class="trial-blob"></div>
                <div id="shape-2" class="trial-blob"></div>
                <div id="shape-3" class="trial-blob"></div>
            </main>

        </div>
    </section>
@endsection
