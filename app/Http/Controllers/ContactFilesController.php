<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContactFile;
use App\Models\Tenant;
use App\Models\ContactActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContactFilesController extends Controller
{
    protected array $categories = ['Contract/SOW', 'Billing', 'Access', 'Brand', 'Requirements', 'Other'];

    public function store(Request $request, Tenant $tenant, Client $contact)
    {
        $this->authorize('update', $contact);

        $data = $request->validate([
            'file' => 'required|file|max:25600', // ~25MB
            'category' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        $category = $data['category'];
        if (!in_array($category, $this->categories)) {
            return back()->with('error_message', 'Invalid category selected.');
        }

        $file = $data['file'];
        $disk = config('filesystems.default', 'private');
        $ext = $file->getClientOriginalExtension();
        $uuid = Str::uuid()->toString();
        $path = "tenants/{$tenant->id}/contacts/{$contact->id}/files/{$uuid}" . ($ext ? ".{$ext}" : '');

        $file->storeAs($path, null, ['disk' => $disk]);

        ContactFile::create([
            'tenant_id' => $tenant->id,
            'contact_id' => $contact->id,
            'uploaded_by' => Auth::id(),
            'category' => $category,
            'description' => $data['description'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'stored_path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        ContactActivity::create([
            'tenant_id' => $tenant->id,
            'contact_id' => $contact->id,
            'actor_id' => Auth::id(),
            'type' => 'file.uploaded',
            'meta' => [
                'category' => $category,
                'name' => $file->getClientOriginalName(),
            ],
            'happened_at' => now(),
        ]);

        return back()->with('success_message', 'File uploaded.');
    }

    public function destroy(Tenant $tenant, Client $contact, ContactFile $file)
    {
        $this->authorize('update', $contact);
        abort_unless($file->contact_id === $contact->id && $file->tenant_id === $tenant->id, 404);

        if ($file->path) {
            Storage::disk($file->disk ?? 'private')->delete($file->path);
        }
        $file->delete();

        return back()->with('success_message', 'File removed.');
    }

    public function download(Tenant $tenant, ContactFile $file)
    {
        $this->authorize('view', $file->contact);
        abort_unless($file->tenant_id === $tenant->id, 404);

        $disk = $file->disk ?? 'private';
        abort_unless(Storage::disk($disk)->exists($file->path ?? $file->stored_path), 404);

        $streamPath = $file->path ?? $file->stored_path;
        return Storage::disk($disk)->download($streamPath, $file->original_name);
    }
}
