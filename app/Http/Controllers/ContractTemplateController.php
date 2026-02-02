<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ContractTemplateController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $templates = ContractTemplate::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('contracts-templates-index', compact('tenant', 'templates'));
    }

    public function create(Tenant $tenant): View
    {
        return view('contracts-templates-create', compact('tenant'));
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source_type' => ['required', 'in:upload,builder'],
            'contract_file' => ['nullable', 'file', 'max:10240'],
            'builder_json' => ['nullable'],
            'disclaimer_mode' => ['nullable', 'in:show,hide'],
        ]);

        $filePath = null;
        if ($validated['source_type'] === 'upload' && $request->hasFile('contract_file')) {
            $filePath = $request->file('contract_file')->store('contract_templates', 'public');
        }

        ContractTemplate::create([
            'tenant_id' => $tenant->id,
            'title' => $validated['title'],
            'source_type' => $validated['source_type'],
            'file_path' => $filePath,
            'builder_json' => $validated['source_type'] === 'builder' ? ($validated['builder_json'] ?? []) : null,
            'disclaimer_mode' => $validated['disclaimer_mode'] ?? ($validated['source_type'] === 'builder' ? 'show' : 'hide'),
        ]);

        $routePrefix = $this->routePrefix();
        return redirect()
            ->route($routePrefix . 'index', ['tenant' => $tenant->id])
            ->with('success_message', 'Contract template created.');
    }

    public function edit(Tenant $tenant, ContractTemplate $template): View
    {
        $this->abortIfWrongTenant($tenant, $template);
        return view('contracts-templates-edit', compact('tenant', 'template'));
    }

    public function update(Request $request, Tenant $tenant, ContractTemplate $template): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $template);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source_type' => ['required', 'in:upload,builder'],
            'contract_file' => ['nullable', 'file', 'max:10240'],
            'builder_json' => ['nullable'],
            'disclaimer_mode' => ['nullable', 'in:show,hide'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $filePath = $template->file_path;
        if ($validated['source_type'] === 'upload' && $request->hasFile('contract_file')) {
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('contract_file')->store('contract_templates', 'public');
        }

        $template->update([
            'title' => $validated['title'],
            'source_type' => $validated['source_type'],
            'file_path' => $validated['source_type'] === 'upload' ? $filePath : null,
            'builder_json' => $validated['source_type'] === 'builder' ? ($validated['builder_json'] ?? []) : null,
            'disclaimer_mode' => $validated['disclaimer_mode'] ?? ($validated['source_type'] === 'builder' ? 'show' : 'hide'),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $routePrefix = $this->routePrefix();
        return redirect()
            ->route($routePrefix . 'index', ['tenant' => $tenant->id])
            ->with('success_message', 'Contract template updated.');
    }

    public function destroy(Tenant $tenant, ContractTemplate $template): RedirectResponse
    {
        $this->abortIfWrongTenant($tenant, $template);
        if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
            Storage::disk('public')->delete($template->file_path);
        }
        $template->delete();

        $routePrefix = $this->routePrefix();
        return redirect()
            ->route($routePrefix . 'index', ['tenant' => $tenant->id])
            ->with('success_message', 'Contract template deleted.');
    }

    protected function abortIfWrongTenant(Tenant $tenant, ContractTemplate $template): void
    {
        if ((int) $template->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }

    protected function routePrefix(): string
    {
        return request()->routeIs('admin.tenants.contracts.templates.*')
            ? 'admin.tenants.contracts.templates.'
            : 'tenant.contracts.templates.';
    }
}
