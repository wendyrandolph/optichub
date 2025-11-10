@extends('layouts.app')
@section('title', 'Activity')

@section('content')
    @php($u = auth()->user())

    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-semibold mb-2">
            Activity • {{ ucfirst($relatedType) }}:
            {{ $entity->name ?? ($entity->title ?? '#' . $entity->id) }}
        </h1>
        <a href="{{ route('admin.activity.index') }}" class="text-indigo-600 hover:underline mb-4 inline-block">← Back</a>

        @if ($activity->isEmpty())
            <p class="text-gray-600">No activity yet for this record.</p>
        @else
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-gray-600">
                            <th class="py-2 px-3">When</th>
                            <th class="py-2 px-3">Action</th>
                            <th class="py-2 px-3">By</th>
                            <th class="py-2 px-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activity as $a)
                            @php
                                $actor = $a->user?->first_name
                                    ? "{$a->user->first_name} {$a->user->last_name}"
                                    : $a->user?->username ?? 'System';
                            @endphp
                            <tr class="border-b">
                                <td class="py-2 px-3 whitespace-nowrap">{{ $a->created_at?->format('M j, Y g:ia') }}</td>
                                <td class="py-2 px-3">{{ $a->action }}</td>
                                <td class="py-2 px-3">{{ $actor }}</td>
                                <td class="py-2 px-3 text-gray-600">{{ $a->description ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $activity->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
