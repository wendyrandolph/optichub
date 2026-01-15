<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TenantCompanyController extends Controller
{
    /**
     * Display a listing of the tenant companies for a given tenant.
     * Stub implementation to satisfy routing; extend with real logic as needed.
     */
    public function index(Request $request, $tenantId)
    {
        // TODO: implement platform-owner view of tenant companies
        return redirect()->route('admin.tenants.show', ['tenant' => $tenantId]);
    }
}
