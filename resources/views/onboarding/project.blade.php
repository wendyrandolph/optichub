@extends('layouts.app')

@section('title', 'Create your first project')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('onboarding._steps', ['current' => 5])

        <div class="rounded-xl bg-surface-card/80 border border-border-default/60 p-6 shadow-sm">
            @if (session('status'))
                <div class="mb-3 rounded-lg border border-[rgb(var(--ui-border))] bg-[rgb(var(--ui-surface-muted))] text-[rgb(var(--ui-text))] px-3 py-2 text-sm">
                    {{ session('status') }}
                </div>
            @endif
            <h1 class="text-2xl font-semibold text-text-base mb-1">Create your first project</h1>
            <p class="text-sm text-text-subtle mb-4">
                Projects are linked to a client contact. Choose who this project is for.
            </p>

            <form method="POST" action="{{ route('onboarding.project.store') }}" class="space-y-4">
                @csrf

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Project name</span>
                    <input type="text" name="project_name" class="oh-input" value="{{ old('project_name') }}"
                        placeholder="Website redesign for Smith & Co.">
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Client contact (required)</span>
                    <select name="contact_id" class="oh-input" required>
                        @foreach ($contacts as $contact)
                            <option value="{{ $contact->id }}"
                                @selected(old('contact_id', $contacts->count() === 1 ? $contact->id : null) == $contact->id)>
                                {{ $contact->full_name ?? ($contact->firstName . ' ' . $contact->lastName) }}
                                @if ($contact->company_name) — {{ $contact->company_name }} @endif
                            </option>
                        @endforeach
                    </select>
                    <span class="text-xs text-text-subtle">This keeps files, invoices, and updates attached to the right client.</span>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Short description (optional)</span>
                    <textarea name="description" rows="3" class="oh-input">{{ old('description') }}</textarea>
                </label>

                <div class="flex justify-between items-center pt-4 border-t border-border-default/60 mt-4">
                    <button type="submit"
                        class="btn btn--primary text-sm bg-[rgb(var(--ui-primary))] hover:bg-[rgb(var(--ui-primary-hover))] text-white border border-transparent">
                        Save & continue
                    </button>
                    <a href="{{ route('onboarding.team') }}" class="text-sm text-text-subtle hover:text-text-base">
                        Skip for now
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
