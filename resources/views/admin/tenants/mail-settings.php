@extends('layouts.app')

@section('title','Mail Settings')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-8 space-y-4">
  @if (session('success'))
  <div class="rounded border border-green-200 bg-green-50 px-4 py-2 text-green-700">{{ session('success') }}</div>
  @endif

  <form method="POST" action="{{ route('tenant.settings.mail.update', ['tenant'=>$tenant]) }}" class="space-y-4">
    @csrf

    <div>
      <label class="block text-sm font-medium mb-1">Provider</label>
      <select name="provider" class="w-full h-10 rounded border px-3">
        <option value="smtp" @selected($settings->provider === 'smtp')>SMTP</option>
      </select>
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium mb-1">SMTP Host</label>
        <input name="smtp_host" value="{{ old('smtp_host',$settings->smtp_host) }}" class="w-full h-10 rounded border px-3" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">SMTP Port</label>
        <input name="smtp_port" type="number" value="{{ old('smtp_port',$settings->smtp_port) }}" class="w-full h-10 rounded border px-3" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">SMTP Username</label>
        <input name="smtp_user" value="{{ old('smtp_user',$settings->smtp_user) }}" class="w-full h-10 rounded border px-3" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">SMTP Password</label>
        <input name="smtp_password" type="password" value="{{ old('smtp_password',$settings->smtp_password) }}" class="w-full h-10 rounded border px-3" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Use TLS</label>
        <input name="smtp_tls" type="checkbox" value="1" @checked($settings->smtp_tls) />
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium mb-1">From Name</label>
        <input name="from_name" value="{{ old('from_name',$settings->from_name) }}" class="w-full h-10 rounded border px-3" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">From Email</label>
        <input name="from_email" type="email" value="{{ old('from_email',$settings->from_email) }}" class="w-full h-10 rounded border px-3" />
      </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-3">
      <div>
        <label class="block text-sm font-medium mb-1">Inbound Localpart</label>
        <input name="inbound_localpart" value="{{ old('inbound_localpart',$settings->inbound_localpart) }}" class="w-full h-10 rounded border px-3" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Inbound Domain</label>
        <input name="inbound_domain" value="{{ old('inbound_domain',$settings->inbound_domain) }}" class="w-full h-10 rounded border px-3" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Auto BCC Outbound</label>
        <input name="auto_bcc_outbound" type="checkbox" value="1" @checked($settings->auto_bcc_outbound) />
      </div>
    </div>

    <p class="text-xs text-gray-500">
      Capture address preview:
      <code>{{ $settings->inbound_localpart }}+{{ $tenant->id }}-{{ $settings->inbound_token }}@{{ $settings->inbound_domain }}</code>
    </p>

    <div class="pt-2">
      <button class="h-10 px-4 rounded bg-blue-600 text-white">Save Settings</button>
    </div>
  </form>
</div>
@endsection