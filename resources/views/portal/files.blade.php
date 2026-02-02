@extends('layouts.portal')

@section('title', 'Files')

@section('content')
    @php
        $totalFiles = $files->count();
        $recentFiles = $files
            ->filter(fn($file) => $file->created_at && $file->created_at->gte(now()->subDays(30)))
            ->count();

        $fileMeta = function ($name) {
            $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            return match ($ext) {
                'pdf' => ['icon' => 'fa-regular fa-file-pdf', 'tone' => 'bg-rose-50 text-rose-600'],
                'doc', 'docx' => ['icon' => 'fa-regular fa-file-word', 'tone' => 'bg-sky-50 text-sky-700'],
                'xls', 'xlsx', 'csv' => ['icon' => 'fa-regular fa-file-excel', 'tone' => 'bg-emerald-50 text-emerald-700'],
                'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg' => ['icon' => 'fa-regular fa-file-image', 'tone' => 'bg-amber-50 text-amber-700'],
                'zip', 'rar', '7z' => ['icon' => 'fa-regular fa-file-zipper', 'tone' => 'bg-violet-50 text-violet-700'],
                default => ['icon' => 'fa-regular fa-file', 'tone' => 'bg-surface-muted text-text-subtle'],
            };
        };
    @endphp

    <div class="oh-page space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold text-text-base">Files</h1>
                <p class="text-sm text-text-subtle">Documents and deliverables shared with you.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs text-text-subtle">
                <div class="inline-flex items-center gap-2 rounded-full border border-border-default bg-surface-card px-3 py-1.5">
                    <span class="h-2 w-2 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                    {{ $totalFiles }} total
                </div>
                <div class="inline-flex items-center gap-2 rounded-full border border-border-default bg-surface-card px-3 py-1.5">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    {{ $recentFiles }} new
                </div>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div class="oh-card p-5 sm:col-span-2 lg:col-span-1">
                <div class="flex items-start gap-3">
                    <div class="h-10 w-10 rounded-lg bg-[rgba(var(--brand-primary),0.12)] text-[rgb(var(--brand-primary))] flex items-center justify-center">
                        <i class="fa-regular fa-folder-open text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">Your shared library</h2>
                        <p class="text-xs text-text-subtle mt-1">
                            Every file shared by your team lives here for quick access and download.
                        </p>
                    </div>
                </div>
            </div>
            <div class="oh-card p-5 sm:col-span-2 lg:col-span-2">
                <div class="flex items-start gap-3">
                    <div class="h-10 w-10 rounded-lg bg-surface-muted text-text-subtle flex items-center justify-center">
                        <i class="fa-regular fa-circle-check text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">Secure delivery</h2>
                        <p class="text-xs text-text-subtle mt-1">
                            Files are versioned and tied to your project so nothing gets lost.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($files as $file)
                @php
                    $meta = $fileMeta($file->original_name ?? '');
                @endphp
                <a href="{{ route('portal.files.download', $file) }}"
                    class="group rounded-xl border border-border-default bg-surface-card p-4 shadow-sm hover:border-[rgb(var(--brand-primary))] hover:shadow-md transition">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 flex items-center justify-center rounded-lg {{ $meta['tone'] }}">
                            <i class="{{ $meta['icon'] }} text-sm"></i>
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
                <div class="sm:col-span-2 lg:col-span-3">
                    <div class="rounded-xl border border-border-default bg-surface-card p-6 text-sm text-text-subtle">
                        No files have been shared with you yet. When something is ready, you’ll see it here.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
