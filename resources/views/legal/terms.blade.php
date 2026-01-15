@extends('layouts.marketing') {{-- or layouts.app, adjust based on your public-facing layout --}}

@section('title', 'Terms of Service – Renlo')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-12">

        {{-- Page Header --}}
        <div class="mb-10">
            <h1 class="text-3xl font-semibold text-slate-900 tracking-tight">
                Terms of Service
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                Last updated: {{ now()->format('F j, Y') }}
            </p>
        </div>

        {{-- Intro Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6 mb-8">
            <p class="text-slate-700 leading-relaxed">
                Welcome to Renlo. These Terms of Service (“Terms”) describe how you may use the platform,
                the responsibilities that come with that, and the areas where we protect both you and the work
                we’re doing behind the scenes.
            </p>
            <p class="mt-3 text-slate-700 leading-relaxed">
                By creating an account, signing in, or continuing to use Renlo, you acknowledge that you’ve
                read and agree to these Terms.
            </p>
        </div>

        {{-- Section Component --}}
        @php
            $sec = fn($title, $content) => "
            <div class='rounded-2xl border border-slate-200 bg-white shadow-sm p-6 mb-8'>
                <h2 class='text-xl font-semibold text-slate-900 mb-3'>$title</h2>
                <div class='space-y-3 text-slate-700 leading-relaxed'>$content</div>
            </div>
        ";
        @endphp

        {!! $sec(
            '1. Introduction',
            '
                        <p>These Terms apply to all users of Renlo. If you do not agree, please discontinue use of the platform.</p>
                    ',
        ) !!}

        {!! $sec(
            '2. Definitions',
            '
                        <p><strong>"Renlo," "we," "our,"</strong> and <strong>"us"</strong> refer to the platform and its owner.</p>
                        <p><strong>"User," "you," "your"</strong> refers to anyone using the platform.</p>
                        <p><strong>"Tenant"</strong> is a business or organization using Renlo internally.</p>
                        <p><strong>"Client"</strong> is a customer of a tenant.</p>
                        <p><strong>"Services"</strong> refers to all functionality provided through Renlo.</p>
                    ',
        ) !!}

        {!! $sec(
            '3. Eligibility & Accounts',
            '
                        <p>You must be at least 18 years old to use the platform.</p>
                        <p>You are responsible for maintaining the confidentiality of your login credentials
                           and for all activity that occurs under your account.</p>
                        <p>Unauthorized access, shared passwords, or compromised accounts should be reported immediately.</p>
                    ',
        ) !!}

        {!! $sec(
            '4. Use of the Platform',
            '
                        <p>You agree to use Renlo for lawful business purposes only.</p>
                        <p>Activities not permitted include: disrupting the platform, attempting unauthorized data access,
                           uploading harmful content, or using the system for fraudulent, misleading, or unlawful purposes.</p>
                        <p>We reserve the right to suspend or terminate accounts that violate these Terms.</p>
                    ',
        ) !!}

        {!! $sec(
            '5. Payments, Subscriptions & Billing',
            '
                        <p>Paid subscriptions renew automatically unless canceled.</p>
                        <p>Fees are non-refundable unless otherwise required by law.</p>
                        <p>Changes in pricing will be communicated when applicable.</p>
                        <p>Subscription access may be restricted if payments fail or accounts become delinquent.</p>
                    ',
        ) !!}

        {!! $sec(
            '6. Tax Responsibilities (Important)',
            '
                        <p><strong>Renlo does not provide accounting, tax, or legal advice.</strong></p>
                        <p>Any tax-related features within the platform are simply tools you may enable and configure.</p>
                        <p>You are solely responsible for determining whether your business must collect, charge, report,
                           or remit taxes in your jurisdiction.</p>
                        <p>We are not liable for tax calculation errors, missed obligations, compliance issues, or decisions
                           you make regarding the use or non-use of provided tax features.</p>
                    ',
        ) !!}

        {!! $sec(
            '7. Invoicing, Payments, and Financial Data',
            '
                        <p>Tenants are responsible for the accuracy of their invoices, rates, taxes, and communication with clients.</p>
                        <p>Renlo is not responsible for delayed payments, disputes, financial losses, or billing errors.</p>
                        <p>Use of third-party payment processors is governed by their respective terms.</p>
                    ',
        ) !!}

        {!! $sec(
            '8. Data Ownership & Privacy',
            '
                        <p>You own the content and data you store within Renlo.</p>
                        <p>We will never sell your data.</p>
                        <p>We may access data for support, security, or operational reasons, as outlined in our Privacy Policy.</p>
                        <p>Reasonable security measures are implemented, but no platform can guarantee absolute protection.</p>
                    ',
        ) !!}

        {!! $sec(
            '9. Intellectual Property',
            '
                        <p>The Renlo brand, interface, design, and platform architecture are protected intellectual property.</p>
                        <p>You may not copy, modify, redistribute, or attempt to reverse engineer any part of the platform.</p>
                        <p>Use of our trademarks requires written permission.</p>
                    ',
        ) !!}

        {!! $sec(
            '10. Third-Party Services',
            '
                        <p>Renlo integrates with external tools such as Stripe.</p>
                        <p>Their terms apply to your usage of their services.</p>
                        <p>We’re not responsible for outages, errors, or data loss originating from third-party providers.</p>
                    ',
        ) !!}

        {!! $sec(
            '11. Service Availability',
            '
                        <p>We aim for a reliable platform but cannot guarantee uninterrupted service or error-free operation.</p>
                        <p>We may modify or discontinue features at any time if needed for improvement, safety, or performance.</p>
                    ',
        ) !!}

        {!! $sec(
            '12. Limitation of Liability',
            '
                        <p>To the fullest extent permitted by law, Renlo and its owners are not liable for indirect, incidental,
                           or consequential damages.</p>
                        <p>Total liability shall not exceed the amount paid for Renlo in the last twelve (12) months.</p>
                        <p>You use the platform at your own risk.</p>
                    ',
        ) !!}

        {!! $sec(
            '13. Indemnification',
            '
                        <p>You agree to indemnify and hold Renlo harmless from claims or damages arising from:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>your misuse of the platform,</li>
                            <li>your tax, legal, or compliance decisions,</li>
                            <li>disputes between you and your clients.</li>
                        </ul>
                    ',
        ) !!}

        {!! $sec(
            '14. Termination',
            '
                        <p>We may suspend or terminate your account for violation of these Terms.</p>
                        <p>You may cancel your account or subscription at any time.</p>
                        <p>Upon termination, some data may be retained as required by law.</p>
                    ',
        ) !!}

        {!! $sec(
            '15. Changes to These Terms',
            '
                        <p>Occasionally we may update these Terms to reflect changes to the platform or legal requirements.</p>
                        <p>When we update the Terms, the “Last Updated” date will change accordingly.</p>
                        <p>Your continued use of Renlo after such changes constitutes acceptance.</p>
                    ',
        ) !!}

        {!! $sec(
            '16. Contact Information',
            '
                        <p>If you have questions or concerns about these Terms, feel free to reach out:</p>
                        <p class="font-medium text-slate-900 mt-2">support@renlo.com</p>
                    ',
        ) !!}

    </div>
@endsection
