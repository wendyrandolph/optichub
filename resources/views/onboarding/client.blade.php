@extends('layouts.app')

@section('title', 'Add your first client')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('onboarding._steps', ['current' => 4])

        <div class="rounded-xl bg-surface-card/80 border border-border-default/60 p-6 shadow-sm">
            <h1 class="text-2xl font-semibold text-text-base mb-1">Add your first client</h1>
            <p class="text-sm text-text-subtle mb-4">
                Projects are linked to a client contact. Add one now so your first project can be created next.
            </p>

            <form method="POST" action="{{ route('onboarding.client.store') }}" class="space-y-4">
                @csrf

                @php
                    $defaultType = old('default_client_type', $tenant->default_client_type ?? 'person');
                    $promptSetting = old('client_type_prompt', $tenant->client_type_prompt ?? 'use_default');
                    $perClientType = old('client_type', $defaultType);
                @endphp

                <div class="grid gap-2 text-sm">
                    <span class="text-text-subtle font-medium">Who do you usually work with?</span>
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 text-text-base">
                            <input type="radio" name="default_client_type" value="person"
                                class="accent-[rgb(var(--ui-primary))]" @checked($defaultType === 'person') required>
                            <span class="text-sm">Mostly people</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-text-base">
                            <input type="radio" name="default_client_type" value="company"
                                class="accent-[rgb(var(--ui-primary))]" @checked($defaultType === 'company') required>
                            <span class="text-sm">Mostly businesses</span>
                        </label>
                    </div>
                    <p class="text-xs text-text-subtle">This is just a default. You can change it anytime in Settings.</p>
                </div>

                <div class="grid gap-2 text-sm">
                    <span class="text-text-subtle font-medium">When creating a client</span>
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 text-text-base">
                            <input type="radio" name="client_type_prompt" value="use_default"
                                class="accent-[rgb(var(--ui-primary))]" @checked($promptSetting === 'use_default') required>
                            <span class="text-sm">Use my default</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-text-base">
                            <input type="radio" name="client_type_prompt" value="ask_each_time"
                                class="accent-[rgb(var(--ui-primary))]" @checked($promptSetting === 'ask_each_time') required>
                            <span class="text-sm">Ask each time</span>
                        </label>
                    </div>
                </div>

                <div class="grid gap-2 text-sm" data-per-client-toggle>
                    <span class="text-text-subtle font-medium">This client is a:</span>
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 text-text-base">
                            <input type="radio" name="client_type" value="person"
                                class="accent-[rgb(var(--ui-primary))]" @checked($perClientType === 'person')>
                            <span class="text-sm">Person</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-text-base">
                            <input type="radio" name="client_type" value="company"
                                class="accent-[rgb(var(--ui-primary))]" @checked($perClientType === 'company')>
                            <span class="text-sm">Company</span>
                        </label>
                    </div>
                </div>

                <div id="company-block" class="grid gap-2 hidden">
                    <p class="text-sm font-medium text-text-base">Company</p>
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Company name</span>
                        <input type="text" name="company_name" class="oh-input" value="{{ old('company_name') }}">
                    </label>

                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Industry (optional)</span>
                            <input type="text" name="industry" class="oh-input" value="{{ old('industry') }}">
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Website (optional)</span>
                            <input type="text" name="website" class="oh-input" value="{{ old('website') }}">
                        </label>
                    </div>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Company phone (optional)</span>
                        <input type="text" name="phone" class="oh-input" value="{{ old('phone') }}">
                    </label>

                    <p class="text-xs text-text-subtle">
                        For businesses, add the company and a primary contact at the same time.
                    </p>
                </div>

                <div id="contact-block" class="grid gap-2 hidden">
                    <p class="text-sm font-medium text-text-base" id="contact-heading">Contact</p>
                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">First name</span>
                            <input type="text" name="contact_first_name" class="oh-input"
                                value="{{ old('contact_first_name') }}">
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Last name</span>
                            <input type="text" name="contact_last_name" class="oh-input"
                                value="{{ old('contact_last_name') }}">
                        </label>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Contact email (optional)</span>
                            <input type="email" name="contact_email" class="oh-input"
                                value="{{ old('contact_email') }}">
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Contact phone (optional)</span>
                            <input type="text" name="contact_phone" class="oh-input"
                                value="{{ old('contact_phone') }}">
                        </label>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-border-default/60 mt-4">
                    <button type="submit"
                        class="btn btn--primary text-sm bg-[rgb(var(--ui-primary))] hover:bg-[rgb(var(--ui-primary-hover))] text-white border border-transparent">
                        Save & continue
                    </button>
                    <a href="{{ route('admin.dashboard') }}"
                        class="text-sm text-text-subtle hover:text-text-base whitespace-nowrap">
                        Skip — finish onboarding later
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const promptRadios = document.querySelectorAll('input[name="client_type_prompt"]');
            const defaultTypeRadios = document.querySelectorAll('input[name="default_client_type"]');
            const perClientToggle = document.querySelector('[data-per-client-toggle]');
            const typeInputs = document.querySelectorAll('input[name="client_type"]');
            const contactBlock = document.getElementById('contact-block');
            const companyBlock = document.getElementById('company-block');
            const contactHeading = document.getElementById('contact-heading');
            const companyName = document.querySelector('input[name="company_name"]');

            function syncVisibility() {
                const prompt = document.querySelector('input[name="client_type_prompt"]:checked')?.value || 'use_default';
                if (perClientToggle) {
                    const showPerClient = prompt === 'ask_each_time';
                    perClientToggle.classList.toggle('hidden', !showPerClient);
                    perClientToggle.querySelectorAll('input').forEach((el) => (el.disabled = !showPerClient));
                }

                const defaultType = document.querySelector('input[name="default_client_type"]:checked')?.value || null;
                const effectiveType = prompt === 'ask_each_time'
                    ? document.querySelector('input[name="client_type"]:checked')?.value || defaultType
                    : defaultType;

                // Hide everything until a choice is made
                if (!effectiveType) {
                    contactBlock.classList.add('hidden');
                    companyBlock.classList.add('hidden');
                    contactBlock.querySelectorAll('input').forEach((el) => (el.disabled = true));
                    companyBlock.querySelectorAll('input').forEach((el) => (el.disabled = true));
                    if (companyName) companyName.required = false;
                    return;
                }

                // People: show contact only; Business: show both (company first)
                const showCompany = effectiveType === 'company';
                contactBlock.classList.remove('hidden');
                contactBlock.querySelectorAll('input').forEach((el) => (el.disabled = false));
                if (contactHeading) {
                    contactHeading.textContent = showCompany ? 'Primary contact' : 'Contact';
                }

                companyBlock.classList.toggle('hidden', !showCompany);
                companyBlock.querySelectorAll('input').forEach((el) => (el.disabled = !showCompany));
                if (companyName) companyName.required = showCompany;
            }

            promptRadios.forEach((r) => r.addEventListener('change', syncVisibility));
            defaultTypeRadios.forEach((r) => r.addEventListener('change', syncVisibility));
            typeInputs.forEach((t) => t.addEventListener('change', syncVisibility));
            syncVisibility();
        })();
    </script>
@endsection
