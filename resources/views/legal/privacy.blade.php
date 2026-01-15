@extends('layouts.marketing') {{-- or layouts.app, adjust based on your public-facing layout --}}

@section('title', 'Privacy Policy – Renlo')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-12">

        {{-- Page Header --}}
        <div class="mb-10">
            <h1 class="text-3xl font-semibold text-slate-900 tracking-tight">
                Privacy Policy
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                Last updated: {{ now()->format('F j, Y') }}
            </p>
        </div>

        {{-- Intro Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6 mb-8">
            <p class="text-slate-700 leading-relaxed">
                This Privacy Policy explains how Renlo collects, uses, and protects information when you use our
                platform and services. We know your data represents real people, real projects, and real trust, and we
                take that seriously.
            </p>
            <p class="mt-3 text-slate-700 leading-relaxed">
                By using Renlo, you agree to the practices described in this Privacy Policy.
            </p>
        </div>

        @php
            $sec = fn($title, $content) => "
            <div class='rounded-2xl border border-slate-200 bg-white shadow-sm p-6 mb-8'>
                <h2 class='text-xl font-semibold text-slate-900 mb-3'>$title</h2>
                <div class='space-y-3 text-slate-700 leading-relaxed'>$content</div>
            </div>
        ";
        @endphp

        {!! $sec(
            '1. Who We Are',
            '
                        <p>Renlo is a platform that helps service-based businesses, creatives, and teams manage projects,
                           clients, and billing in one place.</p>
                        <p>For the purposes of this Privacy Policy, Renlo (and its owner) is the "data controller" for
                           information we collect about you when you use our own site and platform.</p>
                    ',
        ) !!}

        {!! $sec(
            '2. Information We Collect',
            '
                        <p>We collect information in a few different ways:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Account information:</strong> name, email address, password, and tenant/workspace details.</li>
                            <li><strong>Business information:</strong> company name, logo, branding, billing details, and settings.</li>
                            <li><strong>Usage information:</strong> pages visited, features used, approximate location, device and browser type.</li>
                            <li><strong>Client data:</strong> names, contact details, project information, invoices, and related records
                                that you choose to store in Renlo.</li>
                            <li><strong>Payment information:</strong> limited billing details as required for subscription management.
                                Payment card details are handled by our payment processor and are not stored in full by Renlo.</li>
                        </ul>
                    ',
        ) !!}

        {!! $sec(
            '3. How We Use Your Information',
            '
                        <p>We use the information we collect to:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Provide, maintain, and improve the Renlo platform.</li>
                            <li>Personalize your experience and workspace settings.</li>
                            <li>Communicate with you about updates, support, and account-related matters.</li>
                            <li>Monitor performance, usage, and security of the platform.</li>
                            <li>Comply with legal obligations and enforce our Terms of Service.</li>
                        </ul>
                        <p>We do not sell your personal data.</p>
                    ',
        ) !!}

        {!! $sec(
            '4. Cookies & Tracking Technologies',
            '
                        <p>Like most online platforms, we use cookies and similar technologies to:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Keep you signed in between visits.</li>
                            <li>Remember basic preferences (such as sidebar state or theme).</li>
                            <li>Understand which features are being used so we can improve the experience.</li>
                        </ul>
                        <p>You can control or disable cookies at the browser level, but some features of Renlo may not work
                           correctly if you do.</p>
                    ',
        ) !!}

        {!! $sec(
            '5. Legal Bases for Processing (if applicable)',
            '
                        <p>Where required by law (such as in the EU/EEA or similar jurisdictions), we rely on the following legal
                           bases to process personal data:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Contract:</strong> When processing is necessary to provide the services you have signed up for.</li>
                            <li><strong>Legitimate interests:</strong> For running, securing, and improving the platform in ways that
                                do not override your rights and freedoms.</li>
                            <li><strong>Consent:</strong> For certain communications or optional features, where you explicitly agree.</li>
                            <li><strong>Legal obligations:</strong> When we are required to retain or share certain information by law.</li>
                        </ul>
                    ',
        ) !!}

        {!! $sec(
            '6. How We Share Information',
            '
                        <p>We may share information in the following limited situations:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Service providers:</strong> with trusted vendors who help us operate Renlo (e.g., hosting,
                                email providers, payment processors), under appropriate data protection agreements.</li>
                            <li><strong>Legal and compliance:</strong> when we are required to do so by law, legal process, or a
                                valid government request.</li>
                            <li><strong>Business transfers:</strong> in the event of a merger, acquisition, or sale of all or part of
                                the business, in which case we will take steps to ensure your privacy is respected.</li>
                        </ul>
                        <p>We do not sell or rent your personal information to third parties.</p>
                    ',
        ) !!}

        {!! $sec(
            '7. How We Handle Client Data (Your Customers)',
            '
                        <p>As a tenant, you may store information about your own clients inside Renlo (for example: contact
                           details, project records, invoices, and notes).</p>
                        <p>In these cases, you are typically the "data controller" and Renlo acts as a "data processor" on
                           your behalf.</p>
                        <p>You are responsible for ensuring that you have the right to store, process, or use your clients\' data
                           in compliance with applicable laws. We will only access your client data as needed to provide the service,
                           support your account, or comply with legal obligations.</p>
                    ',
        ) !!}

        {!! $sec(
            '8. Data Retention',
            '
                        <p>We retain personal data for as long as it is reasonably necessary to:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Provide and maintain your account and tenant workspace.</li>
                            <li>Meet legal, accounting, or reporting requirements.</li>
                        </ul>
                        <p>When data is no longer needed, it may be anonymized or securely deleted.</p>
                    ',
        ) !!}

        {!! $sec(
            '9. Your Rights & Choices',
            '
                        <p>Depending on where you live, you may have certain rights regarding your personal information, such as:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>The right to access the personal data we hold about you.</li>
                            <li>The right to request correction or update of your information.</li>
                            <li>The right to request deletion of certain data, subject to legal or operational obligations.</li>
                            <li>The right to object to certain types of processing or withdraw consent where applicable.</li>
                        </ul>
                        <p>You can exercise many of these rights from within your account settings, or by contacting us directly.</p>
                    ',
        ) !!}

        {!! $sec(
            '10. Security',
            '
                        <p>We use reasonable technical and organizational measures to protect your information, including encrypted
                           connections, access controls, and monitoring.</p>
                        <p>No online platform can guarantee absolute security, but we are committed to maintaining a strong,
                           security-conscious environment for your data.</p>
                    ',
        ) !!}

        {!! $sec(
            '11. Children\'s Privacy',
            '
                        <p>Renlo is not directed at children under 16, and we do not knowingly collect personal information
                           from children. If you believe a child has provided us with personal data, please contact us so we
                           can take appropriate action.</p>
                    ',
        ) !!}

        {!! $sec(
            '12. International Data Transfers',
            '
                        <p>Renlo may process data on servers located in different regions. By using the platform, you understand
                           that your information may be transferred across borders, subject to appropriate safeguards where required
                           by law.</p>
                    ',
        ) !!}

        {!! $sec(
            '13. Changes to This Privacy Policy',
            '
                        <p>We may update this Privacy Policy from time to time to reflect changes to our services or legal obligations.</p>
                        <p>When we make changes, we will update the "Last updated" date at the top of this page. Where appropriate,
                           we may also notify you through the platform or by email.</p>
                    ',
        ) !!}

        {!! $sec(
            '14. Contact Us',
            '
                        <p>If you have questions, concerns, or requests related to this Privacy Policy, you can reach us at:</p>
                        <p class="font-medium text-slate-900 mt-2">support@renlo.com</p>
                    ',
        ) !!}

    </div>
@endsection
