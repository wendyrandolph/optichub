@php
    $year = now()->year;
    $appName = config('app.name', 'Optic Hub');
    $version = config('app.version');
    $tenantRoute = request()->route('tenant');
    $tenantName = optional($tenantRoute)->name ?? (auth()->user()->tenant->name ?? null);
    $supportMail = config('mail.from.address', 'support@example.com');
@endphp

@php
    use App\Models\Tenant;

    $rt = request()->route('tenant') ?? null;
    $tenantId =
        $rt instanceof Tenant ? $rt->getKey() : (is_numeric($rt) ? (int) $rt : auth()->user()->tenant_id ?? null);
@endphp


<footer
    class="mt-12 border-t border-gray-200/70 dark:border-gray-800/70
                bg-white/70 dark:bg-gray-900/60 backdrop-blur supports-[backdrop-filter]:backdrop-blur
                text-gray-600 dark:text-gray-300">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid gap-8 md:grid-cols-3">

            {{-- Brand / Copy --}}
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-blue-600"></span>
                    <span class="text-xl font-extrabold select-none">
                        <span class="text-blue-900">Optic</span><span class="text-green-600"> Hub</span>
                    </span>
                </div>
                <p class="mt-3 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Thoughtful tools that remove tech stress and help service businesses move forward with clarity.
                </p>

                @if ($tenantName)
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Signed in to: <span
                            class="font-medium text-gray-700 dark:text-gray-200">{{ $tenantName }}</span>
                    </p>
                @endif
            </div>

            {{-- Quick Links --}}
            <nav class="grid grid-cols-2 gap-6 text-sm">
                <div>
                    <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Product</h4>

                    <ul>
                        <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>

                        @if ($tenantId)
                            <li><a href="{{ route('tenant.projects.index', ['tenant' => $tenantId]) }}">Projects</a>
                            </li>
                            <li><a href="{{ route('tenant.tasks.index', ['tenant' => $tenantId]) }}">Tasks</a></li>
                            <li><a href="{{ route('tenant.leads.index', ['tenant' => $tenantId]) }}">Leads</a></li>
                            <li><a
                                    href="{{ route('tenant.organizations.index', ['tenant' => $tenantId]) }}">Organizations</a>
                            </li>
                        @else
                            {{-- no tenant on this page — disable or hide --}}
                            <li class="text-gray-400">Projects</li>
                            <li class="text-gray-400">Tasks</li>
                            <li class="text-gray-400">Leads</li>
                            <li class="text-gray-400">Organizations</li>
                        @endif
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Company</h4>
                    <ul class="space-y-2">
                        @if ($tenantId)
                            <li><a class="hover:text-blue-700 dark:hover:text-blue-400"
                                    href="{{ route('tenant.settings.index', ['tenant' => $tenantId]) }}">Settings</a>
                            </li>
                        @endif
                        <li><a class="hover:text-blue-700 dark:hover:text-blue-400"
                                href="mailto:{{ $supportMail }}">Support</a></li>
                        <li><a class="hover:text-blue-700 dark:hover:text-blue-400"
                                href="{{ route('privacy') }}">Privacy</a></li>
                        <li><a class="hover:text-blue-700 dark:hover:text-blue-400"
                                href="{{ route('terms') }}">Terms</a></li>

                    </ul>
                </div>
            </nav>

            {{-- CTA / Newsletter (optional) --}}
            <div>
                <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Stay in the loop</h4>
                <form class="flex gap-2" method="POST" action="{{ route('newsletter.subscribe') }}">
                    @csrf
                    <input name="email" type="email" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800
                        text-gray-700 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500
                        focus:ring-blue-500 focus:border-blue-500"
                        placeholder="you@company.com">
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-white text-sm
                         hover:bg-blue-700 transition">
                        Subscribe
                    </button>
                </form>

                {{-- Small social row (optional) --}}
                <div class="mt-3 flex items-center gap-3 text-gray-400">
                    <a href="https://www.linkedin.com/company/causey-web-solutions/"
                        class="hover:text-gray-600 dark:hover:text-gray-200" target="__blank"><i
                            class="fa-brands fa-linkedin"></i></a>
                    <a href="https://github.com/optic-hub" class="hover:text-gray-600 dark:hover:text-gray-200"><i
                            class="fa-brands fa-github"></i></a>
                </div>
            </div>

        </div>

        {{-- Divider --}}
        <hr class="my-8 border-gray-200/70 dark:border-gray-800/70">

        {{-- Bottom row --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 text-xs">
            <p class="text-gray-500 dark:text-gray-400">
                © {{ $year }} {{ $appName }}. All rights reserved.
            </p>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                @if ($version)
                    <span class="text-gray-400">Version {{ $version }}</span>
                @endif
                <a href="{{ route('status') }}"
                    class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700 dark:hover:text-gray-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Status
                </a>
                <a href="{{ route('changelog') }}"
                    class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-200">Changelog</a>
                <a href="{{ route('security') }}"
                    class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-200">Security</a>
            </div>
        </div>
    </div>
</footer>
