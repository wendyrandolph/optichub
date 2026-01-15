@extends('layouts.app')

@section('title', 'Edit Email')

@section('content')
    @php
        $tenantId = $tenantId ?? (request()->route('tenant') ?? optional(auth()->user())->tenant_id);
        $tenantId = $tenantId instanceof \App\Models\Tenant ? $tenantId->id : (int) $tenantId;
        $id = data_get($email, 'id');
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex items-start justify-between gap-3">
            <div class="space-y-1">
                <a href="{{ route('tenant.emails.index', ['tenant' => $tenantId]) }}"
                    class="oh-link-underline inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                    <i class="fa-solid fa-arrow-left mr-2 text-[10px]"></i>
                    Back to Email Log
                </a>
                <h1 class="text-2xl font-semibold text-text-base">Edit Email</h1>
                <p class="text-sm text-text-subtle">Update the logged email details.</p>
            </div>
            <a href="{{ route('tenant.emails.show', ['tenant' => $tenantId, 'email' => $id]) }}" class="oh-btn oh-btn--secondary">View</a>
        </header>

        <form method="POST" action="{{ route('tenant.emails.update', ['tenant' => $tenantId, 'email' => $id]) }}"
            class="oh-card p-6 border border-border-default space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="oh-field">
                    <label class="oh-label">Subject</label>
                    <input type="text" name="subject" value="{{ old('subject', $email->subject) }}" class="oh-input" required>
                </div>
                <div class="oh-field">
                    <label class="oh-label">Recipient email</label>
                    <input type="email" name="recipient_email" value="{{ old('recipient_email', $email->recipient_email) }}" class="oh-input" required>
                </div>
                <div class="oh-field">
                    <label class="oh-label">Recipient name</label>
                    <input type="text" name="recipient_name" value="{{ old('recipient_name', $email->recipient_name) }}" class="oh-input">
                </div>
                <div class="oh-field">
                    <label class="oh-label">Related</label>
                    <div class="text-sm text-text-subtle">
                        {{ $email->related_type ? ucfirst($email->related_type) . ($email->related_id ? ' #' . $email->related_id : '') : '—' }}
                    </div>
                </div>
            </div>

            <div class="oh-field">
                <label class="oh-label">Body</label>
                <textarea name="body" class="oh-textarea" rows="8">{{ old('body', $email->body) }}</textarea>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('tenant.emails.index', ['tenant' => $tenantId]) }}" class="oh-btn oh-btn--secondary">Cancel</a>
                <button type="submit" class="oh-btn oh-btn--primary">Save</button>
            </div>
        </form>
    </div>
@endsection
