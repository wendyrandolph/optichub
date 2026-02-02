<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @php
        // If you have a named route for portal login, use it. Otherwise fall back to /portal.
        $portalLoginUrl = \Illuminate\Support\Facades\Route::has('portal.login')
            ? route('portal.login')
            : url('/portal');
    @endphp

    <div class="max-w-xl mx-auto">
        <div class="text-center mb-6">
            <img src="{{ asset('images/renlo.svg') }}" alt="Renlo" class="mx-auto" style="width: 200px; height: auto;">
            <h1 class="mt-4 text-2xl font-semibold text-[rgb(var(--ui-text))]">Welcome back</h1>
            <p class="mt-2 text-xs text-[rgb(var(--ui-text-subtle))] flex flex-wrap items-center justify-center gap-2">
                <span
                    class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-[rgb(var(--ui-border))] bg-[rgb(var(--ui-surface-accent))]">
                    <svg class="h-3 w-3 text-[rgb(var(--ui-text-subtle))]" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="currentColor"
                            d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5zm-3 8V7a3 3 0 0 1 6 0v3H9zm3 3a2 2 0 0 1 1 3.732V18a1 1 0 1 1-2 0v-1.268A2 2 0 0 1 12 13z" />
                    </svg>
                </span>
                Secure sign-in · Your data stays private · Links expire automatically
            </p>
        </div>

        <div class="rounded-2xl border border-[rgb(var(--ui-border))] bg-[rgb(var(--ui-surface))] p-6 sm:p-8 shadow-sm">
            @php
                $hasLoginError = $errors->has('email');
                $errorAccent = $hasLoginError ? 'border-red-300' : 'border-[rgb(var(--ui-border))]';
            @endphp
            <div class="flex flex-wrap gap-2 rounded-xl border {{ $errorAccent }} bg-[rgb(var(--ui-surface-accent))] p-1"
                role="tablist" aria-label="Sign in type" data-has-error="{{ $hasLoginError ? '1' : '0' }}">
                <button type="button"
                    class="tab-btn flex-1 rounded-lg px-3 py-2 text-sm font-medium text-[rgb(var(--ui-text))] bg-[rgb(var(--ui-surface))] shadow-sm"
                    data-tab="team" aria-selected="true" role="tab">
                    Team / Admin
                </button>
                <button type="button"
                    class="tab-btn flex-1 rounded-lg px-3 py-2 text-sm font-medium text-[rgb(var(--ui-text-subtle))]"
                    data-tab="client" aria-selected="false" role="tab">
                    Client Portal
                </button>
            </div>

            <form id="loginForm" method="POST" action="{{ route('login.store') }}" class="mt-6">
                @csrf
                <input type="hidden" name="login_context" value="team">

                <div data-panel="team" role="tabpanel">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-[rgb(var(--ui-text))]">Team sign-in</h2>
                        <p class="mt-1 text-sm text-[rgb(var(--ui-text-subtle))] leading-6">
                            Providers and team members sign in here.
                        </p>
                    </div>
                </div>

                <div data-panel="client" class="hidden" role="tabpanel">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-[rgb(var(--ui-text))]">Client portal</h2>
                        <p class="mt-1 text-sm text-[rgb(var(--ui-text-subtle))] leading-6">
                            Sign in with your client email and password.
                        </p>
                    </div>
                </div>

                <label class="grid gap-1 text-sm">
                    <span class="text-[rgb(var(--ui-text-subtle))]">{{ __('Email') }}</span>
                    <x-text-input id="email" class="block w-full" type="email" name="email"
                        :value="old('email')" required autofocus autocomplete="email" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </label>

                <label class="mt-4 grid gap-1 text-sm">
                    <span class="text-[rgb(var(--ui-text-subtle))]">{{ __('Password') }}</span>
                    <x-text-input id="password" class="block w-full" type="password" name="password" required
                        autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </label>

                <div class="block mt-4">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox"
                            class="rounded border-[rgb(var(--ui-border))] text-[rgb(var(--ui-primary))] shadow-sm
                            focus:ring-4 focus:ring-[rgba(var(--ui-primary),0.25)] focus:ring-offset-2
                            focus:ring-offset-[rgb(var(--ui-surface))]"
                            name="remember">
                        <span class="ms-2 text-sm text-[rgb(var(--ui-text))]">{{ __('Remember me') }}</span>
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 mt-5">
                    @if (Route::has('password.request'))
                        <a class="underline text-sm text-[rgb(var(--ui-text-subtle))] hover:text-[rgb(var(--ui-text))] rounded-md
                            focus:outline-none focus:ring-4 focus:ring-[rgba(var(--ui-primary),0.25)] focus:ring-offset-2
                            focus:ring-offset-[rgb(var(--ui-surface))]"
                            href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif

                    <x-primary-button>
                        {{ __('Log in') }}
                    </x-primary-button>
                </div>

                <div class="mt-4 text-sm text-[rgb(var(--ui-text-subtle))]">
                    <a class="underline hover:text-[rgb(var(--ui-text))]" href="{{ route('marketing.home') }}">
                        Go Back
                    </a>
                </div>
            </form>

            <div data-panel="client" class="hidden mt-4 rounded-lg border border-[rgb(var(--ui-border))] bg-[rgb(var(--ui-surface-accent))] px-4 py-3" role="note">
                <p class="text-xs text-[rgb(var(--ui-text-subtle))] leading-5">
                    If you were sent a secure access link, you can use it directly from your email.
                </p>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const tabs = document.querySelectorAll('.tab-btn');
            const panels = document.querySelectorAll('[data-panel]');
            if (!tabs.length || !panels.length) return;

            const contextInput = document.querySelector('input[name="login_context"]');
            const tabList = document.querySelector('[data-has-error]');
            const hasError = tabList?.getAttribute('data-has-error') === '1';

            const syncToken = () => {
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (!meta) return;
                const token = meta.getAttribute('content');
                document.querySelectorAll('input[name="_token"]').forEach((input) => {
                    input.value = token;
                });
            };

            const setActive = (key) => {
                if (contextInput) {
                    contextInput.value = key;
                }
                tabs.forEach((btn) => {
                    const isActive = btn.dataset.tab === key;
                    btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    btn.classList.toggle('bg-[rgb(var(--ui-surface))]', isActive);
                    btn.classList.toggle('shadow-sm', isActive);
                    btn.classList.toggle('text-[rgb(var(--ui-text))]', isActive);
                    btn.classList.toggle('text-[rgb(var(--ui-text-subtle))]', !isActive);
                    if (hasError && isActive) {
                        btn.classList.add('border', 'border-red-300', 'text-red-600');
                    } else {
                        btn.classList.remove('border', 'border-red-300', 'text-red-600');
                    }
                });
                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.panel !== key);
                });
                syncToken();
            };

            tabs.forEach((btn) => {
                btn.addEventListener('click', () => setActive(btn.dataset.tab));
            });

            const initialTab = @json(session('login_context', 'team'));
            setActive(initialTab);
        })();

        (() => {
            const form = document.getElementById('loginForm');
            if (!form) return;

            let locked = false;
            form.addEventListener('submit', (e) => {
                if (locked) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                locked = true;

                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                    btn.style.cursor = 'not-allowed';
                }
            }, true);
        })();

        (() => {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (!meta) return;
            const getToken = () => meta.getAttribute('content');
            document.querySelectorAll('form[action="{{ route('login.store') }}"]').forEach((form) => {
                form.addEventListener('submit', () => {
                    const token = getToken();
                    form.querySelectorAll('input[name="_token"]').forEach((input) => {
                        input.value = token;
                    });
                });
            });
        })();
    </script>
</x-guest-layout>
