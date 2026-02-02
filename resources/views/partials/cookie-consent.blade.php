<div data-cookie-consent hidden
    class="fixed inset-x-0 bottom-4 z-[9999] px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl">
        <div
            class="oh-card rounded-2xl border border-border-default shadow-lg bg-[rgb(var(--ui-surface))] p-4 sm:p-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Cookies</p>
                <p class="text-sm text-text-subtle">
                    We use cookies to keep you signed in and make Renlo work as expected.
                    <a href="{{ route('privacy') }}" class="text-brand-primary hover:text-brand-secondary">Privacy
                        Policy</a>.
                </p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <button type="button" data-cookie-accept class="oh-btn oh-btn--primary text-sm w-full sm:w-auto">
                    Accept
                </button>
                <button type="button" data-cookie-manage class="oh-btn text-sm w-full sm:w-auto">
                    Manage
                </button>
                <button type="button" data-cookie-reject class="oh-btn text-sm w-full sm:w-auto">
                    Reject non-essential
                </button>
            </div>
        </div>
    </div>
</div>

<div data-cookie-modal hidden class="fixed inset-0 z-[10000] flex items-center justify-center px-4">
    <div data-cookie-overlay class="absolute inset-0 bg-black/30"></div>
    <div role="dialog" aria-modal="true" aria-labelledby="cookie-modal-title"
        class="relative w-full max-w-lg">
        <div class="oh-card rounded-2xl border border-border-default shadow-lg bg-[rgb(var(--ui-surface))] p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Cookie settings</p>
                    <h2 id="cookie-modal-title" class="text-lg font-semibold text-text-base mt-1">Your preferences</h2>
                    <p class="text-sm text-text-subtle mt-1">
                        Choose which optional cookies you allow. Essential cookies are required for login and security.
                    </p>
                </div>
                <button type="button" data-cookie-close class="oh-btn text-sm">
                    Close
                </button>
            </div>

            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-text-base">Essential</p>
                        <p class="text-xs text-text-subtle">Required for sign-in and security.</p>
                    </div>
                    <span class="oh-pill oh-pill--muted text-[11px]">Always on</span>
                </div>

                <label class="flex items-center justify-between gap-3 text-sm">
                    <div>
                        <p class="font-semibold text-text-base">Analytics</p>
                        <p class="text-xs text-text-subtle">Helps us improve performance and usage.</p>
                    </div>
                    <input type="checkbox" data-cookie-analytics class="h-4 w-4">
                </label>

                <label class="flex items-center justify-between gap-3 text-sm">
                    <div>
                        <p class="font-semibold text-text-base">Marketing</p>
                        <p class="text-xs text-text-subtle">Used for relevant product updates and offers.</p>
                    </div>
                    <input type="checkbox" data-cookie-marketing class="h-4 w-4">
                </label>
            </div>

            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" data-cookie-save class="oh-btn oh-btn--primary text-sm">
                    Save preferences
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const consentKey = 'renlo-cookie-consent';
        const prefsKey = 'renlo-cookie-prefs';
        const banner = document.querySelector('[data-cookie-consent]');
        const modal = document.querySelector('[data-cookie-modal]');
        if (!banner || !modal) return;

        // Always start with modal hidden.
        modal.setAttribute('hidden', 'hidden');
        modal.classList.add('hidden');

        const overlay = modal.querySelector('[data-cookie-overlay]');
        const acceptBtn = banner.querySelector('[data-cookie-accept]');
        const rejectBtn = banner.querySelector('[data-cookie-reject]');
        const manageBtn = banner.querySelector('[data-cookie-manage]');
        const closeBtn = modal.querySelector('[data-cookie-close]');
        const saveBtn = modal.querySelector('[data-cookie-save]');
        const analyticsToggle = modal.querySelector('[data-cookie-analytics]');
        const marketingToggle = modal.querySelector('[data-cookie-marketing]');

        const setCookie = (name, value, days = 365) => {
            const date = new Date();
            date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
            document.cookie = `${name}=${encodeURIComponent(value)}; expires=${date.toUTCString()}; path=/; samesite=lax`;
        };

        const savePrefs = (prefs, consentValue) => {
            try {
                localStorage.setItem(consentKey, consentValue);
                localStorage.setItem(prefsKey, JSON.stringify(prefs));
            } catch (e) {}
            setCookie(consentKey, consentValue);
            setCookie(prefsKey, JSON.stringify(prefs));
            banner.setAttribute('hidden', 'hidden');
            modal.setAttribute('hidden', 'hidden');
            modal.classList.add('hidden');
        };

        const loadPrefs = () => {
            try {
                const stored = localStorage.getItem(prefsKey);
                if (stored) return JSON.parse(stored);
            } catch (e) {}
            return {
                essential: true,
                analytics: false,
                marketing: false
            };
        };

        const getCookie = (name) => {
            const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return match ? decodeURIComponent(match[2]) : null;
        };

        const consent = (() => {
            try {
                const stored = localStorage.getItem(consentKey);
                if (stored) return stored;
            } catch (e) {}
            return getCookie(consentKey);
        })();

        if (!consent) {
            banner.removeAttribute('hidden');
        }

        acceptBtn.addEventListener('click', () => {
            savePrefs({
                essential: true,
                analytics: true,
                marketing: true
            }, 'accepted');
        });

        rejectBtn.addEventListener('click', () => {
            savePrefs({
                essential: true,
                analytics: false,
                marketing: false
            }, 'rejected');
        });

        const openModal = () => {
            const prefs = loadPrefs();
            analyticsToggle.checked = !!prefs.analytics;
            marketingToggle.checked = !!prefs.marketing;
            modal.removeAttribute('hidden');
            modal.classList.remove('hidden');
            const focusable = modal.querySelectorAll('button, [href], input, [tabindex]:not([tabindex="-1"])');
            if (focusable.length) {
                focusable[0].focus();
            }
        };

        const closeModal = () => {
            modal.setAttribute('hidden', 'hidden');
            modal.classList.add('hidden');
        };

        if (manageBtn) manageBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (overlay) overlay.addEventListener('click', closeModal);

        if (saveBtn) saveBtn.addEventListener('click', () => {
            savePrefs({
                essential: true,
                analytics: analyticsToggle.checked,
                marketing: marketingToggle.checked
            }, 'custom');
        });

        document.querySelectorAll('[data-cookie-settings]').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                openModal();
            });
        });

        document.addEventListener('keydown', (event) => {
            if (!modal.hasAttribute('hidden') && event.key === 'Escape') {
                closeModal();
            }
        });
    })();
</script>
