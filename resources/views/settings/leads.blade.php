@extends('layouts.app')

@section('title', 'Lead Intake')

@section('content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp

    <div class="oh-page space-y-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-text-subtle">Settings</div>
                <h1 class="text-2xl font-semibold text-text-base mt-1">Lead Intake</h1>
                <p class="text-sm text-text-subtle mt-1">Connect your website forms to Renlo leads.</p>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="oh-card p-5 space-y-3">
                <div class="text-sm font-semibold text-text-base">Inbound endpoint</div>
                <p class="text-xs text-text-subtle">POST leads to this tenant-specific endpoint.</p>
                <div class="rounded-lg border border-border-default bg-surface-muted px-3 py-2 text-xs break-all">
                    {{ $endpointUrl }}
                </div>
                <div class="text-xs text-text-subtle">Secret token</div>
                <div class="flex flex-wrap items-center gap-2">
                    <code class="rounded-lg border border-border-default bg-surface-muted px-3 py-2 text-xs break-all"
                        data-secret>{{ $settings->inbound_secret }}</code>
                    <button type="button" class="oh-btn" data-toggle-secret>Hide</button>
                    <form method="POST" action="{{ route('tenant.settings.leads.regenerate', ['tenant' => $tenantKey]) }}">
                        @csrf
                        <button type="submit" class="oh-btn">Regenerate</button>
                    </form>
                </div>
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
      await fetch('{{ $endpointUrl }}', {
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
            <pre class="rounded-lg border border-border-default bg-surface-muted p-3 text-[11px] overflow-x-auto"><code>&lt;form action=&quot;{{ $endpointUrl }}&quot; method=&quot;post&quot;&gt;
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="oh-card p-5 space-y-3">
                <div class="text-sm font-semibold text-text-base">Notification email</div>
                <p class="text-xs text-text-subtle">Send new lead alerts to this address (comma or line separated).</p>
                <form method="POST" action="{{ route('tenant.settings.leads.update', ['tenant' => $tenantKey]) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <textarea name="notify_email" class="oh-input min-h-[110px]" rows="4"
                        placeholder="owner@company.com">{{ $settings->notify_email ?? '' }}</textarea>
                    <div>
                        <button type="submit" class="oh-btn oh-btn--primary">Save recipients</button>
                    </div>
                </form>
            </div>

            <div class="oh-card p-5 space-y-3">
                <div class="text-sm font-semibold text-text-base">Security + auto reply</div>
                <form method="POST" action="{{ route('tenant.settings.leads.update', ['tenant' => $tenantKey]) }}" class="space-y-3">
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
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const secret = document.querySelector('[data-secret]');
                const toggleSecret = document.querySelector('[data-toggle-secret]');
                if (!secret || !toggleSecret) return;
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
            });
        </script>
    @endpush
@endsection
