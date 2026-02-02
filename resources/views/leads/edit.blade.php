@extends('layouts.app')
@section('title', 'Edit Lead')

@section('content')
    @php $tenantParam = $tenant->getKey(); @endphp

    <div class="oh-page max-w-4xl">
        <header class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-text-subtle">Leads</div>
                <h1 class="text-2xl font-semibold text-text-base mt-1">Edit Lead</h1>
                <p class="text-sm text-text-subtle mt-1">Update lead details and ownership.</p>
            </div>
            <a href="{{ route('tenant.leads.index', ['tenant' => $tenantParam]) }}"
                class="oh-btn inline-flex items-center gap-2">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                View All Leads
            </a>
        </header>

        <form method="POST" action="{{ route('tenant.leads.update', ['tenant' => $tenantParam, 'lead' => $lead]) }}"
            class="oh-card p-6 space-y-5">
            @csrf
            @method('PUT')

            {{-- same grid as create, but values from $lead --}}
            {{-- name --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-text-base">Lead Name *</label>
                    <input name="name" type="text" required value="{{ old('name', $lead->name) }}"
                        class="oh-input mt-1 w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-base">Email</label>
                    <input name="email" type="email" value="{{ old('email', $lead->email) }}"
                        class="oh-input mt-1 w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-base">Phone</label>
                    <input name="phone" type="text" value="{{ old('phone', $lead->phone) }}"
                        class="oh-input mt-1 w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-base">Company</label>
                    <input name="company" type="text" value="{{ old('company', $lead->company) }}"
                        class="oh-input mt-1 w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-base">Status</label>
                    <select name="status" class="oh-select mt-1 w-full">
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" @selected(old('status', $lead->status) === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-base">Priority</label>
                    <select name="priority" class="oh-select mt-1 w-full">
                        @foreach ($priorities as $p)
                            <option value="{{ $p }}" @selected(old('priority', $lead->priority ?? 'normal') === $p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-base">Source</label>
                    <select name="source" class="oh-select mt-1 w-full">
                        <option value="">—</option>
                        @foreach ($sources as $src)
                            <option value="{{ $src }}" @selected(old('source', $lead->source) === $src)>{{ ucfirst($src) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-base">Owner</label>
                    <select name="owner_user_id" class="oh-select mt-1 w-full">
                        <option value="">Unassigned</option>
                        @foreach ($owners as $o)
                            @php
                                $display =
                                    $o->username ?? trim(($o->first_name ?? '') . ' ' . ($o->last_name ?? '')) ?:
                                    $o->email;
                            @endphp
                            <option value="{{ $o->id }}" @selected((string) old('owner_user_id', $lead->owner_user_id) === (string) $o->id)>{{ $display }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-text-base">Company (optional)</label>
                    <select name="company_id" class="oh-select mt-1 w-full">
                        <option value="">None</option>
                        @foreach ($companies ?? [] as $c)
                            <option value="{{ $c->id }}" @selected((string) old('company_id', $lead->company_id) === (string) $c->id)>{{ $c->company_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-text-subtle mt-1">Link when this lead already belongs to a company.</p>
                    <div class="mt-3 space-y-2">
                        <button type="button" id="toggleQuickCompany" class="text-xs text-brand-primary hover:underline">
                            Quick add company
                        </button>
                        <div id="quickCompanyForm" class="hidden space-y-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <input type="text" id="qcName" placeholder="Company name" class="oh-input">
                                <input type="text" id="qcWebsite" placeholder="Website (optional)" class="oh-input">
                                <input type="text" id="qcPhone" placeholder="Phone (optional)"
                                    class="oh-input md:col-span-2">
                            </div>
                            <button type="button" id="saveQuickCompany" class="oh-btn oh-btn--primary text-xs">Save &amp;
                                select</button>
                            <div id="qcMessage" class="text-xs text-rose-600 hidden"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-text-base">Message</label>
                <textarea name="message" rows="4" class="oh-input mt-1 w-full">{{ old('message', $lead->message) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-text-base">Internal notes</label>
                <textarea name="notes" rows="4" class="oh-input mt-1 w-full">{{ old('notes', $lead->notes) }}</textarea>
            </div>

            <div class="flex items-center gap-3 justify-end">
                <a href="{{ route('tenant.leads.show', ['tenant' => $tenantParam, 'lead' => $lead]) }}"
                    class="oh-btn">Cancel</a>
                <button type="submit" class="oh-btn oh-btn--primary">
                    Update Lead
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const toggleBtn = document.getElementById('toggleQuickCompany');
            const form = document.getElementById('quickCompanyForm');
            const saveBtn = document.getElementById('saveQuickCompany');
            const msg = document.getElementById('qcMessage');
            const companySelect = document.querySelector('select[name="company_id"]');

            function toggle() {
                form.classList.toggle('hidden');
            }

            async function save() {
                msg.classList.add('hidden');
                const name = document.getElementById('qcName').value.trim();
                const website = document.getElementById('qcWebsite').value.trim();
                const phone = document.getElementById('qcPhone').value.trim();
                if (!name) {
                    msg.textContent = 'Company name is required.';
                    msg.classList.remove('hidden');
                    return;
                }
                try {
                    const res = await fetch(
                        "{{ route('tenant.companies.quick-store', ['tenant' => $tenantParam]) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                company_name: name,
                                website,
                                phone
                            })
                        });
                    if (!res.ok) throw new Error('Failed to save company.');
                    const data = await res.json();
                    const opt = document.createElement('option');
                    opt.value = data.id;
                    opt.textContent = data.company_name;
                    opt.selected = true;
                    companySelect.appendChild(opt);
                    msg.classList.add('hidden');
                    form.classList.add('hidden');
                } catch (e) {
                    msg.textContent = e.message;
                    msg.classList.remove('hidden');
                }
            }

            toggleBtn?.addEventListener('click', toggle);
            saveBtn?.addEventListener('click', save);
        })();
    </script>
@endpush
