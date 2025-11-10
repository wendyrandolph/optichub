@extends('layouts.app')

@section('title', 'Email Details')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;
        use Carbon\Carbon;

        // Resolve tenant (model or id)
        $t = request()->route('tenant') ?? ($tenant ?? optional(auth()->user())->tenant_id);
        $tenantId = $t instanceof \App\Models\Tenant ? $t->getKey() : (int) $t;

        // Safe access helper
        $get = fn($row, $key, $default = null) => data_get($row, $key, $default);

        // Normalize email fields
        $id = $get($email, 'id');
        $subject = $get($email, 'subject', 'Untitled');
        $body = $get($email, 'body', '');
        $recipient = $get($email, 'recipient_email', '—');

        $relatedType = strtolower((string) $get($email, 'related_type', ''));
        $relatedId = $get($email, 'related_id');

        $sentAtRaw = $get($email, 'date_sent') ?: $get($email, 'created_at');
        try {
            $sentAt = $sentAtRaw ? Carbon::parse($sentAtRaw)->format('M j, Y g:ia') : '—';
        } catch (\Throwable $e) {
            $sentAt = (string) $sentAtRaw ?: '—';
        }

        // Build a link to the related record if possible
        $relatedLabel = $relatedType ? ucfirst($relatedType) . ($relatedId ? " #{$relatedId}" : '') : '—';
        $relatedHref = '#';

        if ($relatedType === 'client') {
            if (Route::has('tenant.contacts.show') && $relatedId) {
                $relatedHref = route('tenant.contacts.show', ['tenant' => $tenantId, 'contact' => $relatedId]);
            } elseif (Route::has('tenant.clients.show') && $relatedId) {
                $relatedHref = route('tenant.clients.show', ['tenant' => $tenantId, 'client' => $relatedId]);
            }
        } elseif ($relatedType === 'lead') {
            if (Route::has('tenant.leads.show') && $relatedId) {
                $relatedHref = route('tenant.leads.show', ['tenant' => $tenantId, 'lead' => $relatedId]);
            }
        }

        // Tag styles per related type
        $tagClass = match ($relatedType) {
            'client'
                => 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-200 dark:border-green-800',
            'lead'
                => 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-800',
            default
                => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-slate-800/60 dark:text-slate-200 dark:border-slate-700',
        };
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Action bar --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('tenant.emails.index', ['tenant' => $tenantId]) }}"
                class="inline-flex items-center gap-2 text-sm text-blue-700 hover:text-blue-800">
                <i class="fa-solid fa-arrow-left"></i> Back to Email Log
            </a>

            <div class="flex items-center gap-2">
                @if (Route::has('tenant.emails.edit'))
                    <a href="{{ route('tenant.emails.edit', ['tenant' => $tenantId, 'email' => $id]) }}"
                        class="h-9 px-3 inline-flex items-center rounded-lg bg-surface-card text-text-base text-sm border border-border-default hover:bg-surface-card/90">
                        <i class="fa-solid fa-pen-to-square mr-2"></i> Edit
                    </a>
                @endif

                @if (Route::has('tenant.emails.destroy'))
                    <form method="POST"
                        action="{{ route('tenant.emails.destroy', ['tenant' => $tenantId, 'email' => $id]) }}"
                        onsubmit="return confirm('Delete this email log?');">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="h-9 px-3 inline-flex items-center rounded-lg bg-surface-card text-text-base text-sm border border-border-default hover:bg-surface-card/90">
                            <i class="fa-solid fa-trash mr-2"></i> Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Header card --}}
        <section class="rounded-2xl border border-border-default/60 bg-surface-card/70 p-5 sm:p-6 space-y-4">
            <div class="flex flex-col gap-2">
                <h1 class="text-xl sm:text-2xl font-semibold text-text-base break-words">{{ $subject }}</h1>

                <div class="flex flex-wrap items-center gap-2 text-sm">
                    {{-- Recipient --}}
                    <span
                        class="inline-flex items-center gap-2 rounded-md border border-border-default bg-white/70 dark:bg-gray-900/60 px-2.5 py-1">
                        <i class="fa-solid fa-envelope text-gray-500"></i>
                        <a href="mailto:{{ $recipient }}" class="text-blue-700 hover:underline">{{ $recipient }}</a>
                        <button type="button" class="ml-1 text-xs text-text-subtle hover:text-text-base"
                            onclick="navigator.clipboard.writeText('{{ $recipient }}')">Copy</button>
                    </span>

                    {{-- Related tag --}}
                    @if ($relatedType)
                        <a href="{{ $relatedHref }}"
                            class="inline-flex items-center gap-2 rounded-md border px-2.5 py-1 text-xs font-medium {{ $tagClass }}">
                            <i class="fa-solid {{ $relatedType === 'client' ? 'fa-user' : 'fa-user-plus' }}"></i>
                            {{ $relatedLabel }}
                        </a>
                    @endif

                    {{-- Sent timestamp --}}
                    <span class="inline-flex items-center gap-2 text-text-subtle ml-auto">
                        <i class="fa-regular fa-clock"></i> {{ $sentAt }}
                    </span>
                </div>
            </div>
        </section>

        {{-- Body card --}}
        <section class="rounded-2xl border border-border-default/60 bg-surface-card/70 p-5 sm:p-6">
            @if ($body)
                <div class="prose prose-sm max-w-none text-text-base dark:prose-invert">
                    {!! nl2br(e($body)) !!}
                </div>
            @else
                <p class="text-text-subtle text-sm italic">No message body logged.</p>
            @endif
        </section>

        {{-- Secondary actions (optional) --}}
        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="mailto:{{ $recipient }}?subject={{ urlencode($subject) }}"
                class="h-9 px-3 inline-flex items-center rounded-lg bg-white dark:bg-gray-900/60 text-sm text-text-base border border-border-default hover:bg-surface-card/90">
                <i class="fa-solid fa-paper-plane mr-2"></i> Compose Reply
            </a>
            <button type="button"
                class="h-9 px-3 inline-flex items-center rounded-lg bg-white dark:bg-gray-900/60 text-sm text-text-base border border-border-default hover:bg-surface-card/90"
                onclick="window.print()">
                <i class="fa-solid fa-print mr-2"></i> Print
            </button>
        </div>
    </div>
@endsection
