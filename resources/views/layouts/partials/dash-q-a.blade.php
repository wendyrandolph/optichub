@php
    $u = auth()->user();
    $tenantParam = $u?->tenant_id; // null-safe
@endphp

{{-- If not logged in or no tenant, don’t render actionable links --}}
@guest
    <div class="flex gap-2 opacity-60 pointer-events-none">
        <a class="btn btn--ghost btn--sm" href="#">New Task</a>
        <a class="btn btn--ghost btn--sm" href="#">Log Time</a>
        <a class="btn btn--ghost btn--sm" href="#">New Invoice</a>
        <a class="btn btn--ghost btn--sm" href="#">New Lead</a>
    </div>
@else
    <div class="flex gap-2">
        @php
            // Helper to safely build tenant.* routes
            $link = function (string $route, array $params = []) use ($tenantParam) {
                if (!$tenantParam || !\Illuminate\Support\Facades\Route::has($route)) {
                    return '#';
                }
                return route($route, array_merge(['tenant' => $tenantParam], $params));
            };
        @endphp

        <a class="btn btn--ghost btn--sm" href="{{ $link('tenant.tasks.create') }}">
            + New Task
        </a>

        <a class="btn btn--ghost btn--sm" href="{{ $link('tenant.projects.index', ['filter' => 'mine']) }}">
            Log Time
        </a>

        <a class="btn btn--ghost btn--sm" href="{{ $link('tenant.invoices.create') }}">
            New Invoice
        </a>

        <a class="btn btn--ghost btn--sm" href="{{ $link('tenant.leads.create') }}">
            New Lead
        </a>
    </div>
@endguest
