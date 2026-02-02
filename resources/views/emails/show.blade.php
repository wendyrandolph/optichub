@extends('layouts.app')

@section('title', 'Email Details')

@section('content')
    @php
        use Carbon\Carbon;
        $tenantId = request()->route('tenant') ?? ($tenant ?? optional(auth()->user())->tenant_id);
        $tenantId = $tenantId instanceof \App\Models\Tenant ? $tenantId->id : (int) $tenantId;
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex items-start justify-between gap-3">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Emails</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $email->subject ?? 'Email' }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.emails.index', ['tenant' => $tenantId]) }}" class="oh-btn oh-btn--secondary">
                    <i class="fa-solid fa-arrow-left text-[12px]"></i>
                    Back to Email Log
                </a>
                <a href="{{ route('tenant.emails.edit', ['tenant' => $tenantId, 'email' => $email->id]) }}"
                    class="oh-btn oh-btn--secondary">
                    <i class="fa-regular fa-pen-to-square text-[12px]"></i>
                    Edit
                </a>
            </div>
        </header>

        <div class="oh-card p-6 space-y-4 border border-border-default">
            @php
                $statusRaw = strtolower((string) ($email->status ?? 'sent'));
                $statusLabel = ucwords(str_replace('_', ' ', $statusRaw));
                $statusPill = match ($statusRaw) {
                    'sent' => 'oh-pill oh-pill--success',
                    'failed' => 'oh-pill oh-pill--danger',
                    'draft' => 'oh-pill oh-pill--muted',
                    'scheduled' => 'oh-pill oh-pill--info',
                    default => 'oh-pill oh-pill--muted',
                };
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <div class="text-text-subtle text-xs uppercase tracking-wide">Subject</div>
                    <div class="text-text-base font-semibold">{{ $email->subject ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-text-subtle text-xs uppercase tracking-wide">Sent</div>
                    <div class="text-text-base">
                        {{ $email->date_sent ? Carbon::parse($email->date_sent)->format('M j, Y · g:ia') : '—' }}
                    </div>
                </div>
                <div>
                    <div class="text-text-subtle text-xs uppercase tracking-wide">Recipient</div>
                    <div class="text-text-base">{{ $email->recipient_email ?? '—' }}</div>
                    @if ($email->recipient_name)
                        <div class="text-xs text-text-subtle">{{ $email->recipient_name }}</div>
                    @endif
                </div>
                <div>
                    <div class="text-text-subtle text-xs uppercase tracking-wide">Status</div>
                    <div class="{{ $statusPill }} text-[11px]">{{ $statusLabel }}</div>
                </div>
            </div>

            <div>
                <div class="text-text-subtle text-xs uppercase tracking-wide mb-1">Body</div>
                <div class="rounded-xl border border-border-default bg-surface-card p-4 text-sm whitespace-pre-line">
                    {{ $email->body ?? '—' }}
                </div>
            </div>
        </div>
    </div>
@endsection
