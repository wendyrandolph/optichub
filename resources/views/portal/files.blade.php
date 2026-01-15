@extends('layouts.portal')

@section('title', 'Files')

@section('content')
    <div class="oh-page space-y-6">
        <div>
            <p class="text-[11px] uppercase tracking-wider text-text-subtle">Portal</p>
            <h1 class="text-2xl font-semibold text-text-base">Files</h1>
            <p class="text-sm text-text-subtle">Documents shared with you.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($files as $file)
            <a href="{{ route('portal.files.download', $file) }}"
                class="group rounded-xl border border-border-default bg-surface-card p-4 shadow-sm hover:border-[rgb(var(--brand-primary))] hover:shadow-md transition">
                <div class="flex items-start gap-3">
                    <div class="h-10 w-10 flex items-center justify-center rounded-lg bg-surface-muted text-text-subtle">
                        {{-- file type icon --}}
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium text-text-base line-clamp-2">
                            {{ $file->original_name }}
                        </div>
                        <div class="mt-1 text-xs text-text-subtle">
                            {{ optional($file->project)->project_name ?? 'General' }}
                        </div>
                        <div class="mt-2 text-[11px] text-text-subtle flex items-center justify-between">
                            <span>{{ $file->created_at->format('M j, Y') }}</span>
                            <span>{{ readable_filesize($file->size) }}</span>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <p class="text-sm text-text-subtle">No files have been shared with you yet.</p>
        @endforelse
        </div>
    </div>
@endsection
