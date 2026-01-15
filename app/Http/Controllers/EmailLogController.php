<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\UserMailAccount;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailLogController extends Controller
{
    public function index(Request $request, $tenant): View
    {
        $tenantId = $tenant instanceof \App\Models\Tenant ? $tenant->getKey() : (int) $tenant;
        $user = Auth::user();
        $gmailEnabled = (bool) config('services.google.enable_sync');
        $envReady = !empty(config('services.google.client_id')) && !empty(config('services.google.client_secret')) && !empty(config('services.google.redirect'));

        $isTenantAdmin = $user && $user->tenant_id == $tenantId && in_array(strtolower($user->role), ['admin', 'owner', 'platform owner', 'super_admin', 'superadmin']);

        $viewMode = $isTenantAdmin && $request->get('scope') === 'team' ? 'team' : 'mine';

        $query = EmailLog::query()->where('tenant_id', $tenantId);
        if ($viewMode === 'mine') {
            $query->where('user_id', $user->id ?? 0);
        } else {
            if ($request->filled('member')) {
                $query->where('user_id', (int) $request->get('member'));
            }
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', '%' . $search . '%')
                    ->orWhere('from_email', 'like', '%' . $search . '%')
                    ->orWhere('to_emails', 'like', '%' . $search . '%')
                    ->orWhere('cc_emails', 'like', '%' . $search . '%');
            });
        }

        if ($dir = $request->get('direction')) {
            $query->where('direction', $dir);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($request->boolean('needs_review')) {
            $query->where('needs_review', true);
        }

        $logs = $query->latest('sent_at')->paginate(20)->withQueryString();

        $accounts = UserMailAccount::where('tenant_id', $tenantId)->get();
        $currentAccount = $accounts->firstWhere('user_id', $user?->id);

        return view('emails.logs', [
            'logs' => $logs,
            'tenantId' => $tenantId,
            'viewMode' => $viewMode,
            'isTenantAdmin' => $isTenantAdmin,
            'accounts' => $accounts,
            'currentAccount' => $currentAccount,
            'gmailConfigured' => $gmailEnabled && $envReady,
            'contactOptions' => Contact::where('tenant_id', $tenantId)->orderBy('firstName')->limit(50)->get(),
        ]);
    }

    public function ignore(Request $request, $tenant, EmailLog $log)
    {
        $user = Auth::user();
        $tenantId = $tenant instanceof \App\Models\Tenant ? $tenant->getKey() : (int) $tenant;
        abort_unless($log->tenant_id === $tenantId, 403);
        if ($log->user_id !== $user->id && !in_array(strtolower($user->role), ['admin', 'owner', 'platform owner', 'super_admin', 'superadmin'])) {
            abort(403);
        }

        $log->needs_review = false;
        $log->status = 'ignored';
        $log->save();

        return back()->with('flash_success', 'Email marked as ignored.');
    }

    public function linkContact(Request $request, $tenant, EmailLog $log)
    {
        $user = Auth::user();
        $tenantId = $tenant instanceof \App\Models\Tenant ? $tenant->getKey() : (int) $tenant;
        abort_unless($log->tenant_id === $tenantId, 403);
        if ($log->user_id !== $user->id && !in_array(strtolower($user->role), ['admin', 'owner', 'platform owner', 'super_admin', 'superadmin'])) {
            abort(403);
        }

        $data = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:clients,id'],
        ]);

        $contact = Contact::where('tenant_id', $tenantId)->findOrFail($data['contact_id']);
        $log->contact_id = $contact->id;
        $log->company_id = $contact->client_company_id;
        $log->needs_review = false;
        $log->status = 'logged';
        $log->save();

        return back()->with('flash_success', 'Email linked to contact.');
    }
}
