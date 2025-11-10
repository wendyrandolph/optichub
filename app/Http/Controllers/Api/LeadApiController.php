<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Lead;

class LeadApiController extends Controller
{
  public function index(Request $request)
  {
    $tenantId = app('api.tenant_id');
    $query = Lead::where('tenant_id', $tenantId)->latest();

    if ($q = $request->query('q')) {
      $query->where(function ($q2) use ($q) {
        $q2->where('name', 'like', "%$q%")
          ->orWhere('email', 'like', "%$q%")
          ->orWhere('phone', 'like', "%$q%");
      });
    }

    return response()->json($query->paginate(25));
  }

  public function show(int $id)
  {
    $tenantId = app('api.tenant_id');
    $lead = Lead::where('tenant_id', $tenantId)->findOrFail($id);
    return response()->json($lead);
  }

  public function store(Request $request)
  {
    $tenantId = app('api.tenant_id');
    $data = $request->validate([
      'name'   => ['required', 'string', 'max:255'],
      'email'  => ['nullable', 'email', 'max:255'],
      'phone'  => ['nullable', 'string', 'max:50'],
      'status' => ['nullable', 'string', 'max:32'],
      'notes'  => ['nullable', 'string'],
      'owner_id' => ['nullable', 'integer'],
      'source'   => ['nullable', 'string', 'max:64'],
    ]);
    $data['tenant_id'] = $tenantId;

    $lead = Lead::create($data);
    return response()->json($lead, 201);
  }

  public function update(Request $request, int $id)
  {
    $tenantId = app('api.tenant_id');
    $lead = Lead::where('tenant_id', $tenantId)->findOrFail($id);

    $data = $request->validate([
      'name'   => ['sometimes', 'string', 'max:255'],
      'email'  => ['sometimes', 'nullable', 'email', 'max:255'],
      'phone'  => ['sometimes', 'nullable', 'string', 'max:50'],
      'status' => ['sometimes', 'nullable', 'string', 'max:32'],
      'notes'  => ['sometimes', 'nullable', 'string'],
      'owner_id' => ['sometimes', 'nullable', 'integer'],
      'source'   => ['sometimes', 'nullable', 'string', 'max:64'],
    ]);

    $lead->update($data);
    return response()->json($lead);
  }

  public function destroy(int $id)
  {
    $tenantId = app('api.tenant_id');
    $lead = Lead::where('tenant_id', $tenantId)->findOrFail($id);
    $lead->delete();

    return response()->json(['deleted' => true]);
  }
}
