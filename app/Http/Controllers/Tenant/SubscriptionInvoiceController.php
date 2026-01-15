<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubscriptionInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(): View
    {
        $tenant = Auth::user()?->tenant;

        // Stub data for now; replace with real subscription invoices when available
        $invoices = collect();

        return view('admin.settings.subscription-invoices', compact('tenant', 'invoices'));
    }
}
