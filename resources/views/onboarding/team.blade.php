@extends('layouts.app')

@section('title', 'Invite your team')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('onboarding._steps', ['current' => 6])

        <div class="rounded-xl bg-surface-card/80 border border-border-default/60 p-6 shadow-sm">
            <h1 class="text-2xl font-semibold text-text-base mb-1">Invite your team (optional)</h1>
            <p class="text-sm text-text-subtle mb-4">
                @if ($isTrial)
                    Team invites unlock when you upgrade. Finish setup now and add teammates anytime from Team.
                @else
                    Invite staff or collaborators now, or add them later from Team.
                @endif
            </p>

            <form method="POST" action="{{ route('onboarding.team.store') }}" class="space-y-4">
                @csrf

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Team member email (optional)</span>
                    <input type="email" name="email" class="oh-input" placeholder="you@example.com">
                    <span class="text-[11px] text-text-subtle">
                        @if ($isTrial)
                            No invite will be sent during the trial. We’ll save this as a reminder.
                        @else
                            We’ll send an invite email when you continue.
                        @endif
                    </span>
                </label>

                <div class="flex justify-between items-center pt-4 border-t border-border-default/60 mt-4">
                    <button type="submit"
                        class="btn btn--primary text-sm bg-[rgb(var(--ui-primary))] hover:bg-[rgb(var(--ui-primary-hover))] text-white border border-transparent">
                        Continue
                    </button>
                    <a href="{{ route('onboarding.finish') }}" class="text-sm text-text-subtle hover:text-text-base">
                        Skip for now
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
