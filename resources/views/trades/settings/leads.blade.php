@extends('layouts.trades')

@section('title', 'Lead Intake')

@section('trades-content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <h1 class="text-2xl font-semibold text-text-base">Lead Intake</h1>
            <p class="text-sm text-text-subtle mt-1">Capture new leads from your website or campaigns.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="oh-card p-5 space-y-3">
                <div class="text-sm font-semibold text-text-base">Webhook URL</div>
                <p class="text-xs text-text-subtle">Use this endpoint for forms and integrations.</p>
                <div class="rounded-lg border border-border-default bg-surface-muted px-3 py-2 text-xs break-all">
                    {{ $webhookUrl }}
                </div>
                <form method="POST" action="{{ route('tenant.trades.settings.leads.test', ['tenant' => $tenant->getRouteKey()]) }}">
                    @csrf
                    <button type="submit" class="oh-btn">Send test lead</button>
                </form>
            </div>

            <div class="oh-card p-5 space-y-3">
                <div class="text-sm font-semibold text-text-base">Form snippet</div>
                <p class="text-xs text-text-subtle">Paste this into your site to capture leads.</p>
                <pre class="rounded-lg border border-border-default bg-surface-muted p-3 text-[11px] overflow-x-auto"><code>&lt;form action=&quot;{{ $webhookUrl }}&quot; method=&quot;post&quot;&gt;
  &lt;input type=&quot;text&quot; name=&quot;name&quot; placeholder=&quot;Name&quot; /&gt;
  &lt;input type=&quot;email&quot; name=&quot;email&quot; placeholder=&quot;Email&quot; /&gt;
  &lt;input type=&quot;tel&quot; name=&quot;phone&quot; placeholder=&quot;Phone&quot; /&gt;
  &lt;textarea name=&quot;message&quot; placeholder=&quot;How can we help?&quot;&gt;&lt;/textarea&gt;
  &lt;input type=&quot;hidden&quot; name=&quot;form_name&quot; value=&quot;Website Contact&quot; /&gt;
  &lt;button type=&quot;submit&quot;&gt;Send&lt;/button&gt;
&lt;/form&gt;</code></pre>
            </div>
        </div>

        <div class="oh-card p-5 space-y-3">
            <div class="text-sm font-semibold text-text-base">Notification recipients</div>
            <p class="text-xs text-text-subtle">Send new lead alerts to these addresses (one per line).</p>
            <form method="POST" action="{{ route('tenant.trades.settings.leads.recipients', ['tenant' => $tenant->getRouteKey()]) }}" class="space-y-3">
                @csrf
                @method('PATCH')
                <textarea name="lead_notification_recipients" class="oh-input min-h-[110px]" rows="4"
                    placeholder="owner@company.com&#10;dispatch@company.com">{{ implode("\n", (array) ($tenant->lead_notification_recipients ?? [])) }}</textarea>
                <div>
                    <button type="submit" class="oh-btn oh-btn--primary">Save recipients</button>
                </div>
            </form>
        </div>

        @php
            $defaultMapping = $leadFieldMapping['default'] ?? ['standard' => [], 'custom' => []];
            $defaultStandard = $defaultMapping['standard'] ?? [];
            $defaultCustom = array_pad($defaultMapping['custom'] ?? [], 5, ['key' => '', 'label' => '']);
            $forms = array_pad($leadFieldMapping['forms'] ?? [], 2, ['name' => '', 'standard' => [], 'custom' => []]);
            $secondForm = $forms[1] ?? ['name' => '', 'standard' => [], 'custom' => []];
            $secondFormHasData = trim((string) ($secondForm['name'] ?? '')) !== '' || !empty($secondForm['custom'] ?? []);
        @endphp

        <div class="oh-card p-5 space-y-4">
            <div>
                <div class="text-sm font-semibold text-text-base">Field mapping</div>
                <p class="text-xs text-text-subtle">Map your form fields to Renlo lead fields. Extra fields will appear on the lead record.</p>
            </div>
            <form method="POST" action="{{ route('tenant.trades.settings.leads.mapping', ['tenant' => $tenant->getRouteKey()]) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="md:col-span-2 text-xs uppercase tracking-wide text-text-subtle">Default mapping</div>
                    <div class="space-y-1.5">
                        <label class="text-xs uppercase tracking-wide text-text-subtle">Name field</label>
                        <input type="text" name="mapping[default][standard][name]" class="oh-input h-9"
                            value="{{ $defaultStandard['name'] ?? 'name' }}" placeholder="name">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs uppercase tracking-wide text-text-subtle">Email field</label>
                        <input type="text" name="mapping[default][standard][email]" class="oh-input h-9"
                            value="{{ $defaultStandard['email'] ?? 'email' }}" placeholder="email">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs uppercase tracking-wide text-text-subtle">Phone field</label>
                        <input type="text" name="mapping[default][standard][phone]" class="oh-input h-9"
                            value="{{ $defaultStandard['phone'] ?? 'phone' }}" placeholder="phone">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs uppercase tracking-wide text-text-subtle">Notes field</label>
                        <input type="text" name="mapping[default][standard][notes]" class="oh-input h-9"
                            value="{{ $defaultStandard['notes'] ?? 'message' }}" placeholder="message">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs uppercase tracking-wide text-text-subtle">Description field</label>
                        <input type="text" name="mapping[default][standard][description]" class="oh-input h-9"
                            value="{{ $defaultStandard['description'] ?? 'description' }}" placeholder="description">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs uppercase tracking-wide text-text-subtle">Preferred time field</label>
                        <input type="text" name="mapping[default][standard][preferred_time]" class="oh-input h-9"
                            value="{{ $defaultStandard['preferred_time'] ?? 'preferred_time' }}" placeholder="preferred_time">
                    </div>
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs uppercase tracking-wide text-text-subtle">Service address field</label>
                        <input type="text" name="mapping[default][standard][service_address]" class="oh-input h-9"
                            value="{{ $defaultStandard['service_address'] ?? 'service_address' }}" placeholder="service_address">
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="text-xs uppercase tracking-wide text-text-subtle">Custom fields</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach ($defaultCustom as $index => $row)
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" name="mapping[default][custom][{{ $index }}][key]" class="oh-input h-9"
                                    value="{{ $row['key'] ?? '' }}" placeholder="field_key">
                                <input type="text" name="mapping[default][custom][{{ $index }}][label]" class="oh-input h-9"
                                    value="{{ $row['label'] ?? '' }}" placeholder="Label shown in Renlo">
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-text-subtle">Use the exact field names from your form payload.</p>
                </div>

                <div class="space-y-4">
                    <div class="text-xs uppercase tracking-wide text-text-subtle">Per-form overrides (max 2)</div>
                    <label class="flex items-center gap-2 text-xs text-text-subtle">
                        <input type="checkbox" class="rounded border-border-default text-brand-primary"
                            data-second-form-toggle @checked($secondFormHasData)>
                        <span>Enable a second form override</span>
                    </label>
                    @foreach ($forms as $formIndex => $form)
                        @php
                            $formStandard = $form['standard'] ?? [];
                            $formCustom = array_pad($form['custom'] ?? [], 5, ['key' => '', 'label' => '']);
                        @endphp
                        <div class="rounded-xl border border-border-default/70 bg-surface-muted/40 p-4 space-y-3 {{ $formIndex === 1 && !$secondFormHasData ? 'hidden' : '' }}"
                            data-form-index="{{ $formIndex }}">
                            <div class="space-y-1.5">
                                <label class="text-xs uppercase tracking-wide text-text-subtle">Form name</label>
                                <input type="text" name="mapping[forms][{{ $formIndex }}][name]" class="oh-input h-9"
                                    value="{{ $form['name'] ?? '' }}" placeholder="Website Contact">
                                <p class="text-xs text-text-subtle">Must match the form_name sent in the payload.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <div class="space-y-1.5">
                                    <label class="text-xs uppercase tracking-wide text-text-subtle">Name field</label>
                                    <input type="text" name="mapping[forms][{{ $formIndex }}][standard][name]" class="oh-input h-9"
                                        value="{{ $formStandard['name'] ?? '' }}" placeholder="name">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs uppercase tracking-wide text-text-subtle">Email field</label>
                                    <input type="text" name="mapping[forms][{{ $formIndex }}][standard][email]" class="oh-input h-9"
                                        value="{{ $formStandard['email'] ?? '' }}" placeholder="email">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs uppercase tracking-wide text-text-subtle">Phone field</label>
                                    <input type="text" name="mapping[forms][{{ $formIndex }}][standard][phone]" class="oh-input h-9"
                                        value="{{ $formStandard['phone'] ?? '' }}" placeholder="phone">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs uppercase tracking-wide text-text-subtle">Notes field</label>
                                    <input type="text" name="mapping[forms][{{ $formIndex }}][standard][notes]" class="oh-input h-9"
                                        value="{{ $formStandard['notes'] ?? '' }}" placeholder="message">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs uppercase tracking-wide text-text-subtle">Description field</label>
                                    <input type="text" name="mapping[forms][{{ $formIndex }}][standard][description]" class="oh-input h-9"
                                        value="{{ $formStandard['description'] ?? '' }}" placeholder="description">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs uppercase tracking-wide text-text-subtle">Preferred time field</label>
                                    <input type="text" name="mapping[forms][{{ $formIndex }}][standard][preferred_time]" class="oh-input h-9"
                                        value="{{ $formStandard['preferred_time'] ?? '' }}" placeholder="preferred_time">
                                </div>
                                <div class="space-y-1.5 md:col-span-2">
                                    <label class="text-xs uppercase tracking-wide text-text-subtle">Service address field</label>
                                    <input type="text" name="mapping[forms][{{ $formIndex }}][standard][service_address]" class="oh-input h-9"
                                        value="{{ $formStandard['service_address'] ?? '' }}" placeholder="service_address">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-xs uppercase tracking-wide text-text-subtle">Custom fields</div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach ($formCustom as $customIndex => $row)
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" name="mapping[forms][{{ $formIndex }}][custom][{{ $customIndex }}][key]" class="oh-input h-9"
                                                value="{{ $row['key'] ?? '' }}" placeholder="field_key">
                                            <input type="text" name="mapping[forms][{{ $formIndex }}][custom][{{ $customIndex }}][label]" class="oh-input h-9"
                                                value="{{ $row['label'] ?? '' }}" placeholder="Label shown in Renlo">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="oh-btn oh-btn--primary">Save field mapping</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.querySelector('[data-second-form-toggle]');
            const secondForm = document.querySelector('[data-form-index="1"]');
            if (!toggle || !secondForm) return;
            const sync = () => {
                secondForm.classList.toggle('hidden', !toggle.checked);
            };
            toggle.addEventListener('change', sync);
            sync();
        });
    </script>
@endsection
