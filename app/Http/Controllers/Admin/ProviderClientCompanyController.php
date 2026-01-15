<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientCompany;
use Illuminate\Http\Request;

class ProviderClientCompanyController extends Controller
{
  public function index(Request $request)
  {
    $admin = auth('admin')->user();

    // For provider, we’ll assume the admin user has a tenant_id
    // representing YOUR own provider workspace.
    $tenantId = $admin->tenant_id;

    $q = trim($request->get('q', ''));

    $query = ClientCompany::query()
      ->where('tenant_id', $tenantId)
      ->withCount('contacts');

    if ($q !== '') {
      $query->where(function ($sub) use ($q) {
        $sub->where('company_name', 'like', "%{$q}%")
          ->orWhere('industry', 'like', "%{$q}%")
          ->orWhere('website', 'like', "%{$q}%");
      });
    }

    $companies = $query
      ->orderBy('company_name')
      ->paginate(20)
      ->appends(['q' => $q]);

    return view('admin.clients.index', [
      'companies' => $companies,
      'q'         => $q,
    ]);
  }

  public function create()
  {
    $company = new ClientCompany();

    return view('admin.clients.create', compact('company'));
  }

  public function store(Request $request)
  {
    $admin = auth('admin')->user();

    $data = $request->validate([
      'company_name' => 'required|string|max:255',
      'industry'     => 'nullable|string|max:255',
      'website'      => 'nullable|string|max:255',
      'phone'        => 'nullable|string|max:255',
      'address'      => 'nullable|string|max:255',
      'notes'        => 'nullable|string',
    ]);

    $data['tenant_id'] = $admin->tenant_id; // your provider tenant/workspace

    ClientCompany::create($data);

    return redirect()
      ->route('admin.clients.index')
      ->with('success', 'Client added.');
  }

  // edit/update later if you want, same idea as tenant version
}
