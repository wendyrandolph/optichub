<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantSubscriptionController extends Controller
{
    public function subscriptionsIndex(Request $request): View
    {
        $tenants = Tenant::query()
            ->with('currentSubscription')
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = [
            'total' => $tenants->total(),
            'active' => $tenants->getCollection()
                ->filter(fn($t) => strtolower($t->currentSubscription?->status ?? $t->subscription_status ?? '') === 'active')
                ->count(),
            'trialing' => $tenants->getCollection()
                ->filter(fn($t) => strtolower($t->currentSubscription?->status ?? $t->subscription_status ?? '') === 'trialing')
                ->count(),
        ];

        return view('admin.tenants.subscriptions-index', [
            'tenants' => $tenants,
            'stats' => $stats,
        ]);
    }

    public function subscriptionsShow(Tenant $tenant): View
    {
        $subscription = $tenant->currentSubscription;

        return view('admin.tenants.subscriptions-show', [
            'tenant' => $tenant,
            'subscription' => $subscription,
        ]);
    }

    public function usageIndex(Request $request): View
    {
        $tenants = Tenant::query()
            ->withCount(['users', 'projects', 'clients', 'invoices'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.tenants.usage-index', [
            'tenants' => $tenants,
        ]);
    }

    public function usageShow(Tenant $tenant): View
    {
        $tenant->loadCount(['users', 'projects', 'clients', 'invoices']);

        return view('admin.tenants.usage-show', [
            'tenant' => $tenant,
        ]);
    }

    public function loginsIndex(Request $request): View
    {
        $tenants = Tenant::query()
            ->with(['users' => function ($q) {
                $q->select('id', 'tenant_id', 'updated_at');
            }])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.tenants.logins-index', [
            'tenants' => $tenants,
        ]);
    }

    public function loginsShow(Tenant $tenant): View
    {
        $tenant->load(['users' => function ($q) {
            $q->select('id', 'tenant_id', 'updated_at')
                ->orderByDesc('updated_at');
        }]);

        return view('admin.tenants.logins-show', [
            'tenant' => $tenant,
        ]);
    }

    public function invoicesIndex(Request $request): View
    {
        $subscriptions = Subscription::withAnyTenant()
            ->with('tenant')
            ->orderByDesc('current_period_end')
            ->paginate(20);

        return view('admin.tenants.invoices-index', [
            'subscriptions' => $subscriptions,
        ]);
    }

    public function invoicesShow(Tenant $tenant): View
    {
        $subscription = $tenant->currentSubscription;

        return view('admin.tenants.invoices-show', [
            'tenant' => $tenant,
            'subscription' => $subscription,
        ]);
    }
}
