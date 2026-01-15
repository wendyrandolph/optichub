<?php

namespace App\Http\Controllers;

use App\Models\Upload; // adjust if your model name is different
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class ClientFileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:client');
    }

    public function index(Request $request)
    {
        $client    = Auth::guard('client')->user();
        $contactId = $client->contact_id;
        $tenantId = $client->tenant_id;

        // If uploads are tied directly to contact_id:
        $query = Upload::query()
            ->with('project')
            ->where('contact_id', $contactId)
            ->where('tenant_id', $tenantId)
            ->where('client_visible', true);

        // If instead they’re tied to Client and Client belongsTo Contact,
        // you could do something like:
        // $query->whereHas('client', fn ($q) => $q->where('contact_id', $contactId));

        if ($search = $request->input('q')) {
            $query->where('original_name', 'like', '%' . $search . '%');
        }

        if ($projectId = $request->input('project_id')) {
            $query->where('project_id', $projectId);
        }

        $files = $query->latest()->paginate(12);

        return view('portal.files', [
            'files' => $files,
        ]);
    }

    public function download(Upload $file)
    {
        $client    = Auth::guard('client')->user();
        Gate::authorize('portal-view-file', $file);

        $disk = $file->disk ?? 'public';
        $path = $file->path; // adjust if you store it as 'filepath' or similar

        if (! Storage::disk($disk)->exists($path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return Storage::disk($disk)->download($path, $file->original_name);
    }
}
