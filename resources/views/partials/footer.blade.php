@php
    use App\Models\Tenant;

    $year = now()->year;
    $startYear = 2026;
    $yearRange = $startYear . ' - ' . $year;
    $appName = config('app.name', 'Renlo');
    $version = config('app.version');

    // Guard-first identity
    $isAdminGuard = auth('admin')->check();
    $isClientGuard = auth('client')->check();
    $isWebGuard = auth()->check();

    $currentUser = auth('admin')->user() ?? (auth('client')->user() ?? auth()->user());

    // Tenant context (only meaningful for tenant/web area)
    $rt = request()->route('tenant');
    $tenantId = $rt instanceof Tenant ? $rt->getKey() : (is_numeric($rt) ? (int) $rt : $currentUser->tenant_id ?? null);

    $tenantName = null;
    if ($rt instanceof Tenant) {
        $tenantName = $rt->name ?? null;
    } elseif ($currentUser?->tenant) {
        $tenantName = $currentUser->tenant->name ?? null;
    }

    $supportMail = config('mail.from.address', 'support@renlo.com');

    $userName = null;
    if ($currentUser) {
        $full = trim(($currentUser->first_name ?? '') . ' ' . ($currentUser->last_name ?? ''));
        $userName = $full !== '' ? $full : $currentUser->name ?? null;
    }

    // Helper for safe route links
    $safeRoute = function (string $name, array $params = []) {
        return \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : null;
    };
@endphp

<footer
    class="mt-10 border-t border-[rgb(var(--ui-border))/0.65] bg-[rgb(var(--ui-surface))] text-[rgb(var(--ui-text-subtle))]">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid gap-8 lg:grid-cols-3">

            {{-- Brand --}}
            <div>
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/renlo-black-logo.svg') }}" alt="Renlo"
                        class="h-9 w-auto block dark:hidden">
                    <img src="{{ asset('images/renlo-white-logo.svg') }}" alt="Renlo"
                        class="h-9 w-auto hidden dark:block">
                </div>

                <p class="mt-3 text-sm leading-6">
                    Renlo helps service businesses stay organized — projects, tasks, clients, and billing — without the
                    tech headache.
                </p>

                @if ($tenantName || $userName)
                    <div class="mt-3 text-xs leading-5">
                        @if ($userName)
                            Signed in as <span
                                class="font-medium text-[rgb(var(--ui-text))]">{{ $userName }}</span><br>
                        @endif
                        @if ($tenantName)
                            Workspace: <span class="font-medium text-[rgb(var(--ui-text))]">{{ $tenantName }}</span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Links --}}
            <div class="grid grid-cols-2 gap-8 text-sm text-[rgb(var(--ui-text))]">
                <div>
                    <h4 class="font-semibold mb-3">Product</h4>
                    <ul class="space-y-2 text-[rgb(var(--ui-text-subtle))]">
                        @if ($isAdminGuard)
                            @php $href = $safeRoute('admin.dashboard'); @endphp
                            @if ($href)
                                <li><a class="hover:text-[rgb(var(--ui-primary))]" href="{{ $href }}">Admin
                                        Dashboard</a></li>
                            @endif

                            @php $tenantsHref = $safeRoute('admin.tenants.index'); @endphp
                            @if ($tenantsHref && ($currentUser?->isPlatformOwner() ?? false))
                                <li><a class="hover:text-[rgb(var(--ui-primary))]" href="{{ $tenantsHref }}">Renlo
                                        Tenants</a></li>
                            @endif
                        @endif

                        @if ($isWebGuard && $tenantId)
                            @php $dash = $safeRoute('dashboards.index', ['tenant' => $tenantId]); @endphp
                            @if ($dash)
                                <li><a class="hover:text-[rgb(var(--ui-primary))]"
                                        href="{{ $dash }}">Dashboard</a></li>
                            @endif

                            @php $projects = $safeRoute('projects.index', ['tenant' => $tenantId]); @endphp
                            @if ($projects)
                                <li><a class="hover:text-[rgb(var(--ui-primary))]"
                                        href="{{ $projects }}">Projects</a></li>
                            @endif

                            @php $tasks = $safeRoute('tasks.index', ['tenant' => $tenantId]); @endphp
                            @if ($tasks)
                                <li><a class="hover:text-[rgb(var(--ui-primary))]" href="{{ $tasks }}">Tasks</a>
                                </li>
                            @endif

                            @php $companies = $safeRoute('companies.index', ['tenant' => $tenantId]); @endphp
                            @if ($companies)
                                <li><a class="hover:text-[rgb(var(--ui-primary))]" href="{{ $companies }}">Client
                                        Companies</a></li>
                            @endif
                        @endif

                        @if ($isClientGuard)
                            @php $portal = $safeRoute('portal.dashboard'); @endphp
                            @if ($portal)
                                <li><a class="hover:text-[rgb(var(--ui-primary))]" href="{{ $portal }}">Client
                                        Portal</a></li>
                            @endif
                        @endif
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold mb-3">Company</h4>
                    <ul class="space-y-2 text-[rgb(var(--ui-text-subtle))]">
                        @php $terms = $safeRoute('terms'); @endphp
                        @php $privacy = $safeRoute('privacy'); @endphp

                        <li>
                            <a class="hover:text-[rgb(var(--ui-primary))]"
                                href="mailto:{{ $supportMail }}">Support</a>
                        </li>

                        @if ($privacy)
                            <li><a class="hover:text-[rgb(var(--ui-primary))]" href="{{ $privacy }}">Privacy</a>
                            </li>
                        @endif

                        @if ($terms)
                            <li><a class="hover:text-[rgb(var(--ui-primary))]" href="{{ $terms }}">Terms</a>
                            </li>
                        @endif
                    </ul>

                    <div class="mt-4 flex items-center gap-3 text-[rgb(var(--ui-text-subtle))]">
                        <a href="https://www.linkedin.com/company/causey-web-solutions/"
                            class="hover:text-[rgb(var(--ui-text))]" target="_blank" rel="noopener">
                            <i class="fa-brands fa-linkedin"></i>
                        </a>
                        <a href="https://github.com/renlo" class="hover:text-[rgb(var(--ui-text))]" target="_blank"
                            rel="noopener">
                            <i class="fa-brands fa-github"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Small CTA --}}
            <div class="lg:text-right">
                <h4 class="font-semibold text-[rgb(var(--ui-text))]">Need help?</h4>
                <p class="mt-2 text-sm leading-6">
                    Email us and we’ll get you unstuck fast.
                </p>
                <a href="mailto:{{ $supportMail }}"
                    class="inline-flex mt-3 items-center justify-center rounded-lg px-4 py-2
                          bg-[rgb(var(--ui-primary))] text-white text-sm font-semibold
                          hover:bg-[rgb(var(--ui-primary-hover))] transition">
                    Contact Support
                </a>
            </div>
        </div>

        <hr class="my-8 border-[rgb(var(--ui-border))/0.65]">

        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 text-xs">
            <p>© {{ $yearRange }} {{ $appName }}. All rights reserved.</p>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                @if ($version)
                    <span>Version {{ $version }}</span>
                @endif

                @php $status = $safeRoute('status'); @endphp
                @if ($status)
                    <a href="{{ $status }}"
                        class="hover:text-[rgb(var(--ui-text))] inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-[rgb(var(--ui-success))]"></span>
                        Status
                    </a>
                @endif

                <a href="#" data-cookie-settings class="hover:text-[rgb(var(--ui-text))]">Cookie settings</a>
            </div>
        </div>
    </div>
</footer>
