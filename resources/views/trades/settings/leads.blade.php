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
                <div class="text-sm font-semibold text-text-base">Inbound endpoint</div>
                <p class="text-xs text-text-subtle">POST leads to this tenant-specific endpoint.</p>
                <div class="rounded-lg border border-border-default bg-surface-muted px-3 py-2 text-xs break-all">
                    {{ $webhookUrl }}
                </div>
                <div class="text-xs text-text-subtle">Secret token</div>
                <div class="flex flex-wrap items-center gap-2">
                    <code class="rounded-lg border border-border-default bg-surface-muted px-3 py-2 text-xs break-all"
                        data-secret>{{ $settings->inbound_secret }}</code>
                    <button type="button" class="oh-btn" data-toggle-secret>Hide</button>
                    <form method="POST" action="{{ route('tenant.trades.settings.leads.regenerate', ['tenant' => $tenant->getRouteKey()]) }}">
                        @csrf
                        <button type="submit" class="oh-btn">Regenerate</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('tenant.trades.settings.leads.test', ['tenant' => $tenant->getRouteKey()]) }}">
                    @csrf
                    <button type="submit" class="oh-btn">Send test lead</button>
                </form>
            </div>

            <div class="oh-card p-5 space-y-3">
                <div class="text-sm font-semibold text-text-base">JavaScript snippet (preferred)</div>
                <p class="text-xs text-text-subtle">Attach this to your form to send JSON securely.</p>
                <pre class="rounded-lg border border-border-default bg-surface-muted p-3 text-[11px] overflow-x-auto"><code>&lt;script&gt;
  const form = document.querySelector('[data-renlo-lead]');
  if (form) {
    const startedAt = Date.now();
    form.addEventListener('submit', async (event) =&gt; {
      event.preventDefault();
      const formData = new FormData(form);
      formData.append('started_at', startedAt);
      formData.append('page_url', window.location.href);
      const payload = Object.fromEntries(formData.entries());
      await fetch('{{ $webhookUrl }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Renlo-Token': '{{ $settings->inbound_secret }}',
        },
        body: JSON.stringify(payload),
      });
      form.reset();
    });
  }
&lt;/script&gt;</code></pre>
                <p class="text-xs text-text-subtle">Add <code>data-renlo-lead</code> to your form tag.</p>
            </div>
        </div>

        <div class="oh-card p-5 space-y-3">
            <div class="text-sm font-semibold text-text-base">HTML fallback (no JavaScript)</div>
            <p class="text-xs text-text-subtle">Use this if you cannot run custom scripts.</p>
            <pre class="rounded-lg border border-border-default bg-surface-muted p-3 text-[11px] overflow-x-auto"><code>&lt;form action=&quot;{{ $webhookUrl }}&quot; method=&quot;post&quot;&gt;
  &lt;input type=&quot;text&quot; name=&quot;name&quot; placeholder=&quot;Name&quot; /&gt;
  &lt;input type=&quot;email&quot; name=&quot;email&quot; placeholder=&quot;Email&quot; /&gt;
  &lt;input type=&quot;tel&quot; name=&quot;phone&quot; placeholder=&quot;Phone&quot; /&gt;
  &lt;textarea name=&quot;message&quot; placeholder=&quot;How can we help?&quot;&gt;&lt;/textarea&gt;
  &lt;input type=&quot;text&quot; name=&quot;website&quot; style=&quot;display:none&quot; tabindex=&quot;-1&quot; autocomplete=&quot;off&quot; /&gt;
  &lt;input type=&quot;hidden&quot; name=&quot;renlo_token&quot; value=&quot;{{ $settings->inbound_secret }}&quot; /&gt;
  &lt;input type=&quot;hidden&quot; name=&quot;form_name&quot; value=&quot;Website Contact&quot; /&gt;
  &lt;input type=&quot;hidden&quot; name=&quot;started_at&quot; value=&quot;&quot; /&gt;
  &lt;button type=&quot;submit&quot;&gt;Send&lt;/button&gt;
&lt;/form&gt;</code></pre>
        </div>

        <div class="oh-card p-5 space-y-3">
            <div class="text-sm font-semibold text-text-base">Notification email</div>
            <p class="text-xs text-text-subtle">Send new lead alerts to this address (comma or line separated).</p>
            <form method="POST" action="{{ route('tenant.trades.settings.leads.recipients', ['tenant' => $tenant->getRouteKey()]) }}" class="space-y-3">
                @csrf
                @method('PATCH')
                <textarea name="lead_notification_recipients" class="oh-input min-h-[110px]" rows="4"
                    placeholder="owner@company.com">{{ $settings->notify_email ?? '' }}</textarea>
                <div>
                    <button type="submit" class="oh-btn oh-btn--primary">Save recipients</button>
                </div>
            </form>
        </div>

        <div class="oh-card p-5 space-y-3">
            <div class="text-sm font-semibold text-text-base">Security + auto reply</div>
            <form method="POST" action="{{ route('tenant.trades.settings.leads.config', ['tenant' => $tenant->getRouteKey()]) }}" class="space-y-3">
                @csrf
                @method('PATCH')
                <div>
                    <label class="text-xs uppercase tracking-wide text-text-subtle">Allowlist domains</label>
                    <textarea name="allowlist_domains" class="oh-input min-h-[110px]" rows="3"
                        placeholder="yourdomain.com&#10;forms.yourdomain.com">{{ implode("\n", (array) ($settings->allowlist_domains ?? [])) }}</textarea>
                    <p class="text-xs text-text-subtle mt-1">Optional: restrict submissions to specific domains.</p>
                </div>
                <label class="flex items-center gap-2 text-sm text-text-subtle">
                    <input type="checkbox" name="auto_reply_enabled" value="1" class="rounded border-border-default text-brand-primary"
                        @checked($settings->auto_reply_enabled)>
                    <span>Enable auto-reply email (coming soon)</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs uppercase tracking-wide text-text-subtle">Auto-reply subject</label>
                        <input type="text" name="auto_reply_subject" class="oh-input h-9" value="{{ $settings->auto_reply_subject ?? '' }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs uppercase tracking-wide text-text-subtle">Auto-reply body</label>
                        <textarea name="auto_reply_body" class="oh-input min-h-[110px]" rows="3">{{ $settings->auto_reply_body ?? '' }}</textarea>
                    </div>
                </div>
                <div>
                    <button type="submit" class="oh-btn oh-btn--primary">Save settings</button>
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

            const secret = document.querySelector('[data-secret]');
            const toggleSecret = document.querySelector('[data-toggle-secret]');
            if (secret && toggleSecret) {
                const full = secret.textContent;
                const mask = () => {
                    secret.textContent = full.replace(/.(?=.{4})/g, '*');
                    toggleSecret.textContent = 'Show';
                };
                const show = () => {
                    secret.textContent = full;
                    toggleSecret.textContent = 'Hide';
                };
                mask();
                toggleSecret.addEventListener('click', () => {
                    if (toggleSecret.textContent === 'Show') {
                        show();
                    } else {
                        mask();
                    }
                });
            }
        });
    </script>
@endsection
