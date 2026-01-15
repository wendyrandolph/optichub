@extends('layouts.app')

@section('title', 'Settings — Profile & Branding')

@section('content')
    @php
        use App\Models\Tenant;

        $org = $org ?? [
            'name' => '',
            'website' => '',
            'phone' => '',
            'support_email' => '',
            'primary_color' => '',
            'secondary_color' => '',
            'accent_color' => '',
            'logo_url' => '',
            'brand_tagline' => '',
            'invoice_footer' => '',
        ];

        $routeTenant = request()->route('tenant');
        $tenantId = match (true) {
            $routeTenant instanceof Tenant => $routeTenant->getKey(),
            is_numeric($routeTenant) => (int) $routeTenant,
            default => auth()->user()->tenant_id ?? null,
        };

        $primarySeed = $org['primary_color'] ?? ($tenant?->brandColorHex('primary') ?? '#2E5D95');
        $secondarySeed = $org['secondary_color'] ?? ($tenant?->brandColorHex('secondary') ?? '#679CD5');
        $accentSeed = $org['accent_color'] ?? ($tenant?->brandColorHex('accent') ?? '#10B981');

        $primaryColorVal = old('primary_color', $primarySeed) ?: $primarySeed;
        $secondaryColorVal = old('secondary_color', $secondarySeed) ?: $secondarySeed;
        $accentColorVal = old('accent_color', $accentSeed) ?: $accentSeed;

        $workspaceNameVal = old('name', $org['name'] ?? '');
        $websiteVal = old('website', $org['website'] ?? '');
        $taglineVal = old('brand_tagline', $org['brand_tagline'] ?? '');
        $invoiceFooterVal = old('invoice_footer', $org['invoice_footer'] ?? '');

        $defaultUsesPhases = (bool) old('default_uses_phases', $tenant->default_uses_phases ?? false);

        $phaseTemplate = $phaseTemplate ?? collect();
        $phaseNames = old('phases', $phaseTemplate->pluck('name')->toArray());
        $phaseNames = array_pad($phaseNames, 5, '');

        $initials = collect(explode(' ', (string) ($org['name'] ?? '')))
            ->filter()
            ->map(fn($part) => mb_substr($part, 0, 1))
            ->join('');
        $initials = $initials ?: 'OH';
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
                    <h1 class="text-2xl font-semibold text-text-base">Profile &amp; Branding</h1>
                    <p class="text-sm text-text-subtle mt-1">Update your organization details and how Renlo appears to your team.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ $tenantId ? route('tenant.settings.index', ['tenant' => $tenantId]) : '#' }}" class="oh-btn">Cancel</a>
                    <button type="submit" form="brandingForm" class="oh-btn oh-btn--primary">Save changes</button>
                </div>
            </div>
            <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap">
                <a href="#org" class="oh-btn">Organization</a>
                <a href="#branding" class="oh-btn">Branding</a>
                <a href="#phases" class="oh-btn">Phases</a>
            </div>
        </div>

        {{-- Alerts --}}
        <div class="space-y-3">
            @if (session('flash_success'))
                <div class="oh-card border border-emerald-200 bg-emerald-50 text-emerald-800 p-3 text-sm">
                    {{ session('flash_success') }}
                </div>
            @endif
            @if ($errors->has('general'))
                <div class="oh-card border border-rose-200 bg-rose-50 text-rose-800 p-3 text-sm">
                    {{ $errors->first('general') }}
                </div>
            @endif
        </div>

        {{-- Current workspace summary --}}
        <div class="oh-card border border-border-default/70 shadow-card overflow-hidden">
            <div class="px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <p class="text-xs font-semibold tracking-wide text-text-subtle uppercase">Current workspace</p>
                    </div>
                    <div class="mt-2">
                        <div class="text-lg font-semibold text-text-base truncate">
                            {{ $org['name'] ?: 'Workspace name not set' }}
                        </div>
                        <div class="mt-1 text-sm text-text-subtle space-y-0.5">
                            @if (!empty($org['website']))
                                <div class="truncate">
                                    <a href="{{ $org['website'] }}" target="_blank" class="text-[rgb(var(--brand-primary))] hover:underline">
                                        {{ $org['website'] }}
                                    </a>
                                </div>
                            @endif
                            @if (!empty($org['phone']))
                                <div>{{ $org['phone'] }}</div>
                            @endif
                            @if (!empty($org['support_email']))
                                <div>Support: <a href="mailto:{{ $org['support_email'] }}" class="hover:underline">{{ $org['support_email'] }}</a></div>
                            @endif
                            <div class="text-xs text-text-subtle mt-2">
                                Tenant ID: <span class="font-medium text-text-base">{{ $tenantId }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if (!empty($org['logo_url']))
                        <div class="p-2">
                            <img src="{{ $org['logo_url'] }}" alt="Workspace logo"
                                class="h-12 w-auto max-w-[180px] object-contain">
                        </div>
                    @else
                        <div class="h-12 w-12 rounded-xl grid place-items-center font-semibold text-white"
                            style="background: linear-gradient(135deg, {{ $primaryColorVal }}, {{ $secondaryColorVal }});">
                            {{ $initials }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="h-1.5 w-full" style="background: linear-gradient(90deg, {{ $primaryColorVal }}, {{ $accentColorVal }}, {{ $secondaryColorVal }});">
            </div>
        </div>

        <form id="brandingForm" method="POST" action="{{ route('tenant.settings.profile.update', ['tenant' => $tenantId]) }}"
            enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] gap-6 items-start">
                {{-- LEFT COLUMN --}}
                <div class="space-y-6">
                    {{-- Organization --}}
                    <div id="org" class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-4">
                        <div class="space-y-1">
                            <h2 class="text-sm font-semibold text-text-base">Organization</h2>
                            <p class="text-sm text-text-subtle">Basic details your team sees across the workspace.</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Workspace name</span>
                                <input name="name" value="{{ $workspaceNameVal }}" class="oh-input h-10" required>
                                @error('name')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Website</span>
                                <input name="website" value="{{ $websiteVal }}" class="oh-input h-10">
                                @error('website')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Phone</span>
                                <input name="phone" value="{{ old('phone', $org['phone'] ?? '') }}" class="oh-input h-10">
                                @error('phone')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Support email</span>
                                <input name="support_email" type="email" value="{{ old('support_email', $org['support_email'] ?? '') }}"
                                    class="oh-input h-10">
                                @error('support_email')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                        </div>
                    </div>

                    {{-- Branding --}}
                    <div id="branding" class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-4">
                        <div class="space-y-1">
                            <h2 class="text-sm font-semibold text-text-base">Branding</h2>
                            <p class="text-sm text-text-subtle">Logo, colors, and messaging used across the app and invoices.</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm text-text-subtle">Logo</label>
                            <div class="flex items-center gap-3">
                                <input id="brandingLogo" type="file" name="logo"
                                    accept=".png,.svg,image/png,image/svg+xml" class="sr-only">
                                <label for="brandingLogo"
                                    class="inline-flex items-center rounded-md border border-[rgb(var(--border))] bg-[rgb(var(--surface))] px-3 py-2 text-sm font-medium text-[rgb(var(--text))] shadow-sm hover:bg-[rgb(var(--surface-muted))] focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgba(var(--ui-primary),0.35)] cursor-pointer">
                                    Choose file
                                </label>
                                <span id="brandingLogoFilename" class="text-sm text-text-subtle">
                                    {{ !empty($org['logo_url']) ? 'Current logo selected' : 'No file chosen' }}
                                </span>
                                @if (!empty($org['logo_url']))
                                    <div class="p-2">
                                        <img src="{{ $org['logo_url'] }}" alt="Logo" class="h-10 object-contain">
                                    </div>
                                @endif
                            </div>
                            <div class="text-xs text-text-subtle">
                                Recommended size: 512×512px (square). Minimum: 200×200px. Formats: PNG or SVG. Max file size: 1MB.
                            </div>
                            @error('logo')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="oh-card border border-border-default/60 p-3 space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="h-8 w-8 rounded-full border border-border-default/70" style="background: {{ $primaryColorVal }}"></span>
                                    <div>
                                        <div class="text-sm font-semibold text-text-base">Primary</div>
                                        <div class="text-xs text-text-subtle">{{ $primaryColorVal }}</div>
                                    </div>
                                </div>
                                <input name="primary_color" value="{{ $primaryColorVal }}" class="oh-input h-10">
                                @error('primary_color')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </div>
                            <div class="oh-card border border-border-default/60 p-3 space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="h-8 w-8 rounded-full border border-border-default/70" style="background: {{ $secondaryColorVal }}"></span>
                                    <div>
                                        <div class="text-sm font-semibold text-text-base">Secondary</div>
                                        <div class="text-xs text-text-subtle">{{ $secondaryColorVal }}</div>
                                    </div>
                                </div>
                                <input name="secondary_color" value="{{ $secondaryColorVal }}" class="oh-input h-10">
                                @error('secondary_color')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </div>
                            <div class="oh-card border border-border-default/60 p-3 space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="h-8 w-8 rounded-full border border-border-default/70" style="background: {{ $accentColorVal }}"></span>
                                    <div>
                                        <div class="text-sm font-semibold text-text-base">Accent</div>
                                        <div class="text-xs text-text-subtle">{{ $accentColorVal }}</div>
                                    </div>
                                </div>
                                <input name="accent_color" value="{{ $accentColorVal }}" class="oh-input h-10">
                                @error('accent_color')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Workspace tagline</span>
                                <input name="brand_tagline" value="{{ $taglineVal }}" class="oh-input h-10" placeholder="Short phrase under your name">
                                @error('brand_tagline')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Invoice footer</span>
                                <input name="invoice_footer" value="{{ $invoiceFooterVal }}" class="oh-input h-10" placeholder="Thanks for your business">
                                @error('invoice_footer')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                            </label>
                        </div>
                    </div>

                    {{-- Phases --}}
                    <div id="phases" class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-4">
                        <div class="space-y-1">
                            <h2 class="text-sm font-semibold text-text-base">Phases</h2>
                            <p class="text-sm text-text-subtle">Default phases for new projects.</p>
                        </div>

                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="default_uses_phases" value="1" @checked($defaultUsesPhases)
                                class="rounded border-border-default text-brand-primary">
                            <span class="text-text-base">Use phases by default on new projects</span>
                        </label>

                        <label class="grid gap-1 text-sm md:max-w-sm">
                            <span class="text-text-subtle">Phase template</span>
                            <select name="phase_template_id" class="oh-select h-10">
                                <option value="">(None)</option>
                                @foreach ($phaseTemplate ?? [] as $template)
                                    <option value="{{ $template->id }}" @selected(old('phase_template_id') == $template->id)>
                                        {{ $template->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('phase_template_id')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach ($phaseNames as $idx => $pname)
                                <label class="grid gap-1 text-sm">
                                    <span class="text-text-subtle">Phase {{ $idx + 1 }}</span>
                                    <input type="text" name="phases[]" value="{{ $pname }}" class="oh-input h-10">
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN --}}
                <div class="space-y-4">
                    <div class="oh-card border border-border-default/60 rounded-2xl p-4 space-y-3 sticky top-4">
                        <p class="text-xs font-semibold tracking-wide text-text-subtle uppercase">Preview</p>
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-xl grid place-items-center font-semibold text-white"
                                style="background: linear-gradient(135deg, {{ $primaryColorVal }}, {{ $secondaryColorVal }});">
                                {{ mb_substr($workspaceNameVal ?: 'O', 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-text-base truncate">
                                    {{ $workspaceNameVal ?: 'Your workspace name' }}
                                </div>
                                <div class="text-xs text-text-subtle truncate">
                                    {{ $websiteVal ? parse_url($websiteVal, PHP_URL_HOST) ?? $websiteVal : 'yourdomain.com' }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <span class="oh-pill oh-pill--info" style="background: {{ $primaryColorVal }}22; color: {{ $primaryColorVal }};">Primary</span>
                            <span class="oh-pill" style="background: {{ $secondaryColorVal }}22; color: {{ $secondaryColorVal }};">Secondary</span>
                            <span class="oh-pill oh-pill--warning" style="background: {{ $accentColorVal }}22; color: {{ $accentColorVal }};">Accent</span>
                        </div>
                    </div>

                    <div class="oh-card border border-border-default/60 rounded-2xl p-4 space-y-2">
                        <p class="text-xs font-semibold tracking-wide text-text-subtle uppercase">Tips</p>
                        <ul class="text-sm text-text-subtle space-y-1.5 list-disc list-inside">
                            <li>Ensure contrast on text over brand colors.</li>
                            <li>Logo works best on light backgrounds, PNG preferred.</li>
                            <li>Invoice footer shows on client invoices.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('brandingLogo');
            const label = document.getElementById('brandingLogoFilename');
            if (!input || !label) return;

            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                label.textContent = file ? file.name : 'No file chosen';
            });
        });
    </script>
@endpush
