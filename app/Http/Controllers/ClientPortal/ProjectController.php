<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('client')->user();
        if (! $user || ! $user->contact_id) {
            abort(403);
        }

        $client = Client::where('tenant_id', $user->tenant_id)
            ->where('id', $user->contact_id)
            ->firstOrFail();

        $companyId = $client->client_company_id;

        $projects = Project::query()
            ->where('tenant_id', $user->tenant_id)
            ->where(function ($q) use ($client, $companyId) {
                $q->where('contact_id', $client->id);
                if ($companyId) {
                    $q->orWhere('client_company_id', $companyId)
                        ->orWhereIn('contact_id', function ($sub) use ($companyId) {
                            $sub->select('id')
                                ->from('contacts')
                                ->where('client_company_id', $companyId);
                        });
                }
            })
            ->withCount(['tasks'])
            ->latest('updated_at')
            ->paginate(12);

        return view('portal.projects.index', [
            'projects' => $projects,
        ]);
    }
}
