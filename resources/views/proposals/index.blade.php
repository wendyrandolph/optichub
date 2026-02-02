@extends('layouts.app')

@section('title', 'Proposals')

@section('content')
    @php
        $tp = request()->route('tenant') ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;
        $proposals = $proposals ?? collect();
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Sales</p>
                <h1 class="text-2xl font-semibold text-text-base">Proposals</h1>
                <p class="text-sm text-text-subtle mt-1">Draft, send, and track client proposals.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tenant.proposal-templates.index', ['tenant' => $tenantId]) }}" class="oh-btn">Templates</a>
                <a href="{{ route('tenant.proposals.create', ['tenant' => $tenantId]) }}" class="oh-btn oh-btn--primary">
                    <i class="fa-solid fa-plus mr-2 text-[12px]"></i>
                    New Proposal
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('tenant.proposals.index', ['tenant' => $tenantId]) }}" class="oh-card border border-border-default/70 rounded-2xl bg-surface-card/90 p-4 space-y-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex-1">
                    <label class="sr-only" for="proposal-search">Search proposals</label>
                    <input id="proposal-search" name="q" value="{{ request('q') }}" class="oh-input h-10 w-full"
                        placeholder="Search proposal titles, clients, or projects...">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="oh-btn oh-btn--primary h-10">Apply</button>
                    @if (request('q') || request('status'))
                        <a href="{{ route('tenant.proposals.index', ['tenant' => $tenantId]) }}" class="oh-btn h-10">Reset</a>
                    @endif
                </div>
            </div>
            <div class="space-y-2">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Status filter</p>
                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $filterPills = [
                            '' => ['label' => 'All', 'class' => 'oh-pill'],
                            'draft' => ['label' => 'Draft', 'class' => 'oh-pill oh-pill--muted'],
                            'sent' => ['label' => 'Sent', 'class' => 'oh-pill oh-pill--info'],
                            'viewed' => ['label' => 'Viewed', 'class' => 'oh-pill oh-pill--accent'],
                            'accepted' => ['label' => 'Approved', 'class' => 'oh-pill oh-pill--success'],
                            'declined' => ['label' => 'Declined', 'class' => 'oh-pill oh-pill--danger'],
                        ];
                        $currentStatus = request('status', '');
                    @endphp
                    @foreach ($filterPills as $value => $pill)
                        <label class="{{ $currentStatus === $value ? $pill['class'] : 'oh-pill' }} hover:bg-surface-accent/60 hover:text-text-base">
                            <input type="radio" name="status" value="{{ $value }}" class="sr-only" @checked(request('status', '') === $value) data-pill-submit>
                            {{ $pill['label'] }}
                        </label>
                    @endforeach
                </div>
            </div>
        </form>

        <section class="space-y-4 overflow-visible">
            @forelse ($proposals as $proposal)
                @php
                    $status = strtolower((string) ($proposal->status ?? 'draft'));
                    $statusLabel = match ($status) {
                        'accepted', 'approved' => 'Approved',
                        'viewed' => 'Viewed',
                        'sent' => 'Sent',
                        'rejected', 'declined' => 'Declined',
                        default => 'Draft',
                    };
                    $statusPill = match ($status) {
                        'sent' => 'oh-pill oh-pill--info',
                        'accepted', 'approved' => 'oh-pill oh-pill--success',
                        'viewed' => 'oh-pill oh-pill--accent',
                        'rejected', 'declined' => 'oh-pill oh-pill--danger',
                        'draft' => 'oh-pill oh-pill--muted',
                        default => 'oh-pill',
                    };
                    $clientName = $proposal->recipientName();
                    $projectName = $proposal->project?->project_name ?? '—';
                @endphp
                <article class="oh-card border border-border-default/70 rounded-2xl bg-surface-card/90 p-4 md:p-5 relative overflow-visible" style="overflow: visible;" data-menu-root>
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="flex-1">
                            <a href="{{ route('tenant.proposals.show', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}" class="block">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base font-semibold text-text-base">{{ $proposal->title }}</h2>
                                    <span class="{{ $statusPill }}">{{ $statusLabel }}</span>
                                </div>
                                <div class="mt-1 text-sm text-text-subtle">
                                    <span>{{ $clientName }}</span>
                                    <span class="mx-1 text-text-subtle/70">·</span>
                                    <span>{{ $projectName }}</span>
                                </div>
                                @if ($proposal->sent_at)
                                    <div class="mt-2 text-xs text-text-subtle">Sent {{ $proposal->sent_at->format('M j, Y') }}</div>
                                @endif
                            </a>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('tenant.proposals.show', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                                class="oh-btn hidden md:inline-flex">View</a>
                            <button
                                type="button"
                                class="oh-btn oh-btn--ghost"
                                data-menu-trigger
                                data-proposal-id="{{ $proposal->id }}"
                                data-view-url="{{ route('tenant.proposals.show', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                                data-edit-url="{{ route('tenant.proposals.edit', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                                data-duplicate-url="{{ route('tenant.proposals.duplicate', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                                data-send-url="{{ route('tenant.proposals.send', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                                data-copy-url="{{ $proposal->unique_share_token ? (route('proposal.public.show', $proposal->unique_share_token) . ($status === 'draft' ? '?preview=1' : '')) : '' }}"
                                data-copy-label="{{ $status === 'draft' ? 'Copy internal preview link' : 'Copy public link' }}"
                                data-status="{{ $status }}"
                                aria-haspopup="menu"
                                aria-expanded="false">
                                ⋯
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                @if (request('q') || request('status'))
                    <div class="oh-card border border-border-default/70 rounded-2xl bg-surface-card/90 p-6 text-center">
                        <p class="text-sm text-text-subtle">No proposals match this filter yet.</p>
                        <a href="{{ route('tenant.proposals.index', ['tenant' => $tenantId]) }}" class="oh-btn mt-4">Clear filters</a>
                    </div>
                @else
                    <div class="oh-card border border-border-default/70 rounded-2xl bg-surface-card/90 p-6 text-center">
                        <p class="text-sm text-text-subtle">No proposals yet. Create your first proposal to get started.</p>
                        <a href="{{ route('tenant.proposals.create', ['tenant' => $tenantId]) }}" class="oh-btn oh-btn--primary mt-4">
                            Create your first proposal
                        </a>
                    </div>
                @endif
            @endforelse
        </section>
    </div>

    <div id="proposal-menu-portal"></div>

    @push('scripts')
        <script>
            (function () {
                const portal = document.getElementById('proposal-menu-portal');
                if (!portal) return;

                let openMenu = null;
                let openTrigger = null;
                let backdrop = null;

                function closeMenu(options = {}) {
                    const { returnFocus = true } = options;
                    if (openMenu) {
                        openMenu.remove();
                        openMenu = null;
                    }
                    if (backdrop) {
                        backdrop.remove();
                        backdrop = null;
                    }
                    if (openTrigger) {
                        openTrigger.setAttribute('aria-expanded', 'false');
                        openTrigger.textContent = '⋯';
                        if (returnFocus) {
                            openTrigger.focus();
                        }
                        openTrigger = null;
                    }
                }

                function clamp(value, min, max) {
                    return Math.min(Math.max(value, min), max);
                }

                function positionMenu(menu, triggerRect) {
                    const menuRect = menu.getBoundingClientRect();
                    let top = triggerRect.bottom + 8;
                    let left = triggerRect.left - menuRect.width - 8;

                    if (left < 8) {
                        left = triggerRect.right - menuRect.width;
                    }
                    if (left + menuRect.width > window.innerWidth - 8) {
                        left = window.innerWidth - menuRect.width - 8;
                    }
                    if (top + menuRect.height > window.innerHeight - 8) {
                        top = triggerRect.top - menuRect.height - 8;
                    }

                    top = clamp(top, 8, window.innerHeight - menuRect.height - 8);
                    left = clamp(left, 8, window.innerWidth - menuRect.width - 8);

                    menu.style.top = `${top}px`;
                    menu.style.left = `${left}px`;
                }

                function buildMenu(trigger) {
                    const status = trigger.dataset.status || 'draft';
                    const viewUrl = trigger.dataset.viewUrl;
                    const editUrl = trigger.dataset.editUrl;
                    const sendUrl = trigger.dataset.sendUrl;
                    const duplicateUrl = trigger.dataset.duplicateUrl;
                    const copyUrl = trigger.dataset.copyUrl;
                    const copyLabel = trigger.dataset.copyLabel || 'Copy public link';

                    const menu = document.createElement('div');
                    menu.className = 'fixed z-[var(--z-dropdown)] w-48 oh-card border border-[rgb(var(--ui-border))] rounded-2xl bg-[rgb(var(--ui-surface))] text-[rgb(var(--ui-text))] p-2 shadow-lg space-y-1';
                    menu.setAttribute('role', 'menu');
                    menu.setAttribute('tabindex', '-1');

                    menu.innerHTML = `
                        <a href="${viewUrl}" class="oh-btn w-full justify-start" role="menuitem" data-menu-item>View</a>
                        ${status === 'draft' ? `
                        <form method="POST" action="${sendUrl}">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <button type="submit" class="oh-btn w-full justify-start" role="menuitem" data-menu-item>Send</button>
                        </form>` : ''}
                        <a href="${editUrl}" class="oh-btn w-full justify-start" role="menuitem" data-menu-item>Edit</a>
                        <form method="POST" action="${duplicateUrl}">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <button type="submit" class="oh-btn w-full justify-start" role="menuitem" data-menu-item>Duplicate</button>
                        </form>
                        ${copyUrl ? `<button type="button" class="oh-btn w-full justify-start" role="menuitem" data-menu-item data-copy-link data-url="${copyUrl}" data-copy-label="${copyLabel}">${copyLabel}</button>` : ''}
                    `;

                    menu.addEventListener('click', function (event) {
                        const copyBtn = event.target.closest('[data-copy-link]');
                        if (copyBtn) {
                            const url = copyBtn.getAttribute('data-url');
                            if (url) {
                                navigator.clipboard.writeText(url).then(() => {
                                    const originalLabel = copyBtn.getAttribute('data-copy-label') || copyBtn.textContent;
                                    menu.querySelectorAll('[data-menu-item]').forEach((item) => {
                                        item.classList.remove('oh-btn--primary');
                                    });
                                    copyBtn.classList.add('oh-btn--primary');
                                    copyBtn.textContent = 'Link copied';
                                    setTimeout(() => {
                                        copyBtn.textContent = originalLabel;
                                    }, 1500);
                                });
                            }
                            event.stopPropagation();
                            return;
                        }
                        event.stopPropagation();
                    });

                    return menu;
                }

                function openMenuFor(trigger) {
                    if (openTrigger === trigger) {
                        closeMenu();
                        return;
                    }
                    if (openTrigger && openTrigger !== trigger) {
                        closeMenu({ returnFocus: false });
                    } else {
                        closeMenu();
                    }

                    openTrigger = trigger;
                    openTrigger.setAttribute('aria-expanded', 'true');
                    openTrigger.textContent = '×';

                    openMenu = buildMenu(trigger);
                    portal.appendChild(openMenu);

                    const rect = trigger.getBoundingClientRect();
                    positionMenu(openMenu, rect);

                    const firstItem = openMenu.querySelector('[role="menuitem"]');
                    if (firstItem) {
                        firstItem.focus();
                    }
                }

                document.addEventListener('click', function (event) {
                    const trigger = event.target.closest('[data-menu-trigger]');
                    if (!trigger) {
                        if (openMenu) {
                            closeMenu();
                        }
                        return;
                    }
                    event.stopPropagation();
                    openMenuFor(trigger);
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeMenu();
                    }
                });

                window.addEventListener('resize', function () {
                    if (openMenu && openTrigger) {
                        positionMenu(openMenu, openTrigger.getBoundingClientRect());
                    }
                });

                // Global copy handler not needed because the menu stops propagation.

                document.addEventListener('click', function (event) {
                    const menuItem = event.target.closest('[data-menu-item]');
                    if (!menuItem) return;
                    const menu = menuItem.closest('[role="menu"]');
                    if (!menu) return;
                    menu.querySelectorAll('[data-menu-item]').forEach((item) => {
                        item.classList.remove('oh-btn--primary');
                    });
                    menuItem.classList.add('oh-btn--primary');
                });
            })();
        </script>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('change', function (event) {
                const input = event.target.closest('[data-pill-submit]');
                if (!input) return;
                const form = input.closest('form');
                if (form) {
                    form.submit();
                }
            });
        </script>
    @endpush
@endsection
