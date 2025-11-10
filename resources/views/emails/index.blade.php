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
            <a href="{{ route('tenant.emails.create', ['tenant' => $tenantParam]) }}"
                class="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm font-medium text-white
              bg-gradient-to-b from-brand-primary to-blue-700 hover:brightness-110 transition">
                <i class="fa-solid fa-plus mr-2 text-xs"></i> New Email
            </a>
        </header>

        {{-- Toolbar --}}
        <form method="GET" action="{{ route('tenant.emails.index', ['tenant' => $tenantParam]) }}"
            class="rounded-xl bg-surface-card/70 border border-border-default/60 p-3 md:p-4 flex flex-col md:flex-row gap-3 md:items-center">
            <input name="q" value="{{ request('q', '') }}"
                class="md:w-80 h-10 rounded-lg bg-white/80 dark:bg-gray-900/60 border border-border-default px-3 text-sm
                focus:outline-none focus:ring-1 focus:ring-brand-primary"
                placeholder="Search subject, recipient, preview…">
            <div class="ml-auto flex gap-2">
                <button
                    class="h-10 px-4 rounded-lg bg-surface-card/60 hover:bg-surface-card/90 text-text-base text-sm">Apply</button>
                @if (request()->hasAny(['q']))
                    <a href="{{ route('tenant.emails.index', ['tenant' => $tenantParam]) }}"
                        class="h-10 px-4 rounded-lg bg-surface-card/60 hover:bg-surface-card/90 text-text-base text-sm">Reset</a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="rounded-2xl overflow-hidden border border-border-default/60 bg-white/70 dark:bg-gray-900/60">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-border-default/60">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Subject</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">To</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Related</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Sent</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-200">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100/80 dark:divide-slate-800">
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
                                        'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-800',
                                    'task' =>
                                        'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-200 dark:border-green-800',
                                    'lead' =>
                                        'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-200 dark:border-purple-800',
                                    'invoice' =>
                                        'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:border-amber-800',
                                ];
                                $chipClass =
                                    $chipColors[strtolower($related)] ??
                                    'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800/40 dark:text-slate-300 dark:border-slate-700';
                            @endphp

                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                {{-- Subject (primary) --}}
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1 h-2 w-2 rounded-full bg-blue-500/80"></span>
                                        <div class="min-w-0">
                                            <a @if (Route::has('tenant.emails.show')) href="{{ route('tenant.emails.show', ['tenant' => $tenantParam, 'email' => $id]) }}"
                    @else
                      href="#" @endif
                                                class="text-slate-900 dark:text-slate-100 font-medium hover:underline line-clamp-1"
                                                title="{{ $subject }}">{{ $subject }}</a>
                                            @if ($preview = $get($email, 'preview'))
                                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">
                                                    {{ $preview }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Recipient --}}
                                <td class="px-4 py-3 align-top">
                                    <div class="text-slate-700 dark:text-slate-200">{{ $recipient }}</div>
                                    @if ($name = $get($email, 'recipient_name'))
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $name }}</div>
                                    @endif
                                </td>

                                {{-- Related --}}
                                <td class="px-4 py-3 align-top">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 border rounded-md text-[11px] {{ $chipClass }}">
                                        <i class="fa-regular fa-tag text-[10px]"></i>
                                        {{ ucfirst($related) }}@if ($relatedId)
                                            #{{ $relatedId }}
                                        @endif
                                    </span>
                                </td>

                                {{-- Sent --}}
                                <td class="px-4 py-3 align-top">
                                    <span class="text-slate-700 dark:text-slate-200">{{ $sentAtFmt }}</span>
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($tenantParam && Route::has('tenant.emails.edit'))
                                            <a href="{{ route('tenant.emails.edit', ['tenant' => $tenantParam, 'email' => $id]) }}"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800"
                                                title="Edit">
                                                <i class="fa-regular fa-pen-to-square"></i><span
                                                    class="hidden sm:inline">Edit</span>
                                            </a>
                                        @endif

                                        @php $destroyUrl = $tenantParam && Route::has('tenant.emails.destroy') ? route('tenant.emails.destroy', ['tenant' => $tenantParam, 'email' => $id]) : ($tenantParam ? url("$tenantParam/emails/$id") : url("/emails/$id")); @endphp

                                        <form method="POST" action="{{ $destroyUrl }}"
                                            onsubmit="return confirm('Delete this email?');">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20"
                                                title="Delete">
                                                <i class="fa-regular fa-trash-can"></i><span
                                                    class="hidden sm:inline">Delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12">
                                        <div class="text-center text-slate-500 dark:text-slate-400">
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
                    <div class="px-4 py-3 border-t border-slate-100/80 dark:border-slate-800">
                        {{ $emails->links() }}
                    </div>
                @endif
            </div>

        </div>
    @endsection
