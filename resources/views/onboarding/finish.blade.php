@extends('layouts.app')

@section('title', 'You’re all set')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  @include('onboarding._steps', ['current' => 7])

  <div class="rounded-xl bg-surface-card/80 border border-border-default/60 p-6 shadow-sm text-center">
    <h1 class="text-2xl font-semibold text-text-base mb-2">You’re all set 🎉</h1>
    <p class="text-sm text-text-subtle mb-4">
      Your workspace is ready. You can keep adding clients, projects, and tasks as you go.
    </p>

    <a href="{{ route('admin.dashboard') }}" class="btn btn--primary">
      Go to your dashboard
    </a>
  </div>
</div>
@endsection