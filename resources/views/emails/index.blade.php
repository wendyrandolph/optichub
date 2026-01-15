@extends('layouts.app')

@section('title', 'Email Log')

@section('content')
    @php
        // Try to resolve a tenant param for tenant-prefixed routes.
        // Works if your route is like /{tenant}/emails
        $tenantParam = $tenant ?? (request()->route('tenant') ?? optional(auth()->user())->tenant_id);

        // Tiny helper for safe access whether $emails are arrays or models
        $get = fn($row, $key, $default = null) => data_get($row, $key, $default);
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Header + CTA --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-text-base">Email Log</h1>
                <p class="text-sm text-text-subtle mt-1">Your email communications with clients in one place.</p>
            </div>
            <a href="{{ route('tenant.emails.create', ['tenant' => $tenantParam]) }}" class="oh-btn oh-btn--primary">
                <i class="fa-solid fa-plus text-xs"></i>
                New Email
            </a>
        </header>

        {{-- Toolbar --}}
        <form method="GET" action="{{ route('tenant.emails.index', ['tenant' => $tenantParam]) }}"
            class="rounded-xl bg-surface-card/70 border border-border-default/60 p-4 flex flex-col md:flex-row md:items-center gap-3">
            <div class="flex-1">
                <input name="q" value="{{ request('q', '') }}" class="oh-input"
                    placeholder="Search subject, recipient, preview…">
            </div>
            <div class="flex items-center gap-2">
                <select name="sort" class="oh-select">
                    <option value="">Sort: Recently sent</option>
                    <option value="created_desc" @selected(request('sort')==='created_desc')>Recently created</option>
                    <option value="subject_asc" @selected(request('sort')==='subject_asc')>Subject A–Z</option>
                </select>
                <button type="submit" class="oh-btn oh-btn--primary">Apply</button>
                @if (request()->hasAny(['q','sort','status','related']))
                    <a href="{{ route('tenant.emails.index', ['tenant' => $tenantParam]) }}" class="oh-btn oh-btn--secondary">Reset</a>
                @endif
            </div>
        </form>

        {{-- Filter chips --}}
        <div class="flex flex-wrap gap-2 -mt-2">
            @php
                $chips = [
                    '' => 'All',
                    'draft' => 'Drafts',
                    'sent' => 'Sent',
                    'scheduled' => 'Scheduled',
                    'failed' => 'Failed',
                ];
                $activeStatus = request('status', '');
            @endphp
            @foreach ($chips as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['status' => $val ?: null, 'page' => null]) }}"
                    class="oh-chip {{ $activeStatus === $val ? 'is-active' : '' }}" aria-pressed="{{ $activeStatus === $val ? 'true' : 'false' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="oh-card border border-border-default bg-surface-card shadow-sm rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="oh-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>To</th>
                            <th>Related</th>
                            <th>Sent</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($emails as $email)
                            @php
                                $id = $get($email, 'id');
                                $subject = $get($email, 'subject', '—');
                                $recipient = $get($email, 'recipient_email', '—');
                                $related = (string) $get($email, 'related_type', 'record');
                                $relatedId = $get($email, 'related_id');
                                $sentAt = $get($email, 'date_sent') ?: $get($email, 'created_at');
                                try {
                                    $sentAtFmt = $sentAt
                                        ? \Carbon\Carbon::parse($sentAt)->format('M j, Y • g:ia')
                                        : '—';
                                } catch (\Throwable $e) {
                                    $sentAtFmt = (string) $sentAt ?: '—';
                                }
                                $chipColors = [
                                    'project' =>
                                        'bg-blue-50 text-blue-700 border-blue-200',
                                    'task' =>
                                        'bg-green-50 text-green-700 border-green-200',
                                    'lead' =>
                                        'bg-purple-50 text-purple-700 border-purple-200',
                                    'invoice' =>
                                        'bg-amber-50 text-amber-700 border-amber-200',
                                ];
                                $chipClass =
                                    $chipColors[strtolower($related)] ??
                                    'bg-slate-50 text-slate-700 border-slate-200';
                            @endphp

                            <tr class="hover:bg-surface-accent/50">
                                {{-- Subject (primary) --}}
                                <td>
                                    <div class="flex items-start gap-2">
                                        <div class="min-w-0">
                                            <a @if (Route::has('tenant.emails.show')) href="{{ route('tenant.emails.show', ['tenant' => $tenantParam, 'email' => $id]) }}"
                    @else
                      href="#" @endif
                                                class="text-text-base font-medium hover:text-brand-primary line-clamp-1"
                                                title="{{ $subject }}">{{ $subject }}</a>
                                            @if ($preview = $get($email, 'preview'))
                                                <p class="text-xs text-text-subtle line-clamp-1">
                                                    {{ $preview }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Recipient --}}
                                <td>
                                    <div class="text-text-base">{{ $recipient }}</div>
                                    @if ($name = $get($email, 'recipient_name'))
                                        <div class="text-xs text-text-subtle">{{ $name }}</div>
                                    @endif
                                </td>

                                {{-- Related --}}
                                <td>
                                    <span class="oh-pill oh-pill--muted text-[11px]">
                                        {{ ucfirst($related) }}@if ($relatedId)
                                            #{{ $relatedId }}
                                        @endif
                                    </span>
                                </td>

                                {{-- Sent --}}
                                <td>
                                    <span class="text-text-base">{{ $sentAtFmt }}</span>
                                </td>

                                {{-- Actions --}}
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('tenant.emails.show', ['tenant' => $tenantParam, 'email' => $id]) }}"
                                            class="oh-icon-btn oh-tooltip" data-tooltip="View">
                                            <i class="fa-regular fa-eye text-[12px]"></i>
                                        </a>
                                        @if ($tenantParam && Route::has('tenant.emails.edit'))
                                            <a href="{{ route('tenant.emails.edit', ['tenant' => $tenantParam, 'email' => $id]) }}"
                                                class="oh-icon-btn oh-tooltip" data-tooltip="Edit">
                                                <i class="fa-regular fa-pen-to-square text-[12px]"></i>
                                            </a>
                                        @endif
                                        @php
                                            $destroyUrl = $tenantParam && Route::has('tenant.emails.destroy')
                                                ? route('tenant.emails.destroy', ['tenant' => $tenantParam, 'email' => $id])
                                                : ($tenantParam ? url($tenantParam . '/emails/' . $id) : url('/emails/' . $id));
                                        @endphp
                                        <form method="POST" action="{{ $destroyUrl }}"
                                            onsubmit="return confirm('Delete this email?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="oh-icon-btn oh-tooltip" data-tooltip="Delete">
                                                <i class="fa-regular fa-trash-can text-[12px] text-rose-500"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12">
                                        <div class="text-center text-slate-500">
                                            No emails logged yet — click <span class="font-medium">New Email</span> to add your
                                            first.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if (method_exists($emails, 'links'))
                    <div class="px-4 py-3 border-t border-slate-100/80">
                        {{ $emails->links() }}
                    </div>
                @endif
            </div>

        </div>
    @endsection
