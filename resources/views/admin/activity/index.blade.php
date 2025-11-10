@extends('layouts.app')
@section('title', 'Activity')

@section('content')
    @php
        $u = auth()->user();
        // ensure we always have an iterable
        $rows = isset($recentActivity) ? $recentActivity : collect();
    @endphp

    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-semibold mb-4">Recent Activity</h1>

        @if (
            (is_object($rows) && method_exists($rows, 'count') && $rows->count() === 0) ||
                (is_array($rows) && count($rows) === 0))
            <p class="text-gray-600">No activity yet.</p>
        @else
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-gray-600">
                            <th class="py-2 px-3">When</th>
                            <th class="py-2 px-3">Action</th>
                            <th class="py-2 px-3">Subject</th>
                            <th class="py-2 px-3">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $a)
                            @php
                                $subjectType = class_basename($a->related_type ?? '');
                                $subjectName =
                                    $a->related->name ?? ($a->related->title ?? '#' . ($a->related_id ?? '—'));
                                $actor = optional($a->user)->first_name
                                    ? trim(($a->user->first_name ?? '') . ' ' . ($a->user->last_name ?? ''))
                                    : $a->user->username ?? 'System';
                                $typeSlug = strtolower($subjectType);
                                $created = optional($a->created_at)->diffForHumans() ?? '—';
                            @endphp
                            <tr class="border-b">
                                <td class="py-2 px-3 whitespace-nowrap">{{ $created }}</td>
                                <td class="py-2 px-3">
                                    <span class="font-medium text-gray-800">{{ $a->action ?? 'event' }}</span>
                                    @if (!empty($a->description))
                                        <span class="ml-2 text-gray-400">— {{ $a->description }}</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    <span class="text-gray-700">{{ $subjectType ?: 'Record' }}</span>:
                                    @if (in_array($typeSlug, ['project', 'client', 'task']) &&
                                            \Illuminate\Support\Facades\Route::has('admin.activity.related'))
                                        <a class="text-indigo-600 hover:underline"
                                            href="{{ route('admin.activity.related', ['type' => $typeSlug, 'id' => $a->related_id]) }}">
                                            {{ $subjectName }}
                                        </a>
                                    @else
                                        <span class="text-gray-900">{{ $subjectName }}</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">{{ $actor }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (is_object($rows) && method_exists($rows, 'links'))
                <div class="mt-4">
                    {{ $rows->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
