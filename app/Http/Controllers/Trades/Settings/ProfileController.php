<?php

namespace App\Http\Controllers\Trades\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index(Tenant $tenant)
    {
        return view('trades.settings.profile', [
            'tenant' => $tenant,
            'logoUrl' => $tenant->logo_path ? asset('storage/' . $tenant->logo_path) : null,
        ]);
    }

    public function update(ProfileUpdateRequest $request, Tenant $tenant)
    {
        $data = $request->validated();
        $updates = Arr::only($data, [
            'name',
            'website',
            'phone',
            'support_email',
            'primary_color',
            'secondary_color',
            'accent_color',
            'brand_tagline',
            'invoice_footer',
            'team_member_colors',
        ]);

        if (array_key_exists('team_member_colors', $updates)) {
            $colors = collect($updates['team_member_colors'] ?? [])
                ->map(fn($color) => strtoupper(trim((string) $color)))
                ->filter()
                ->map(function ($color) {
                    return str_starts_with($color, '#') ? $color : '#' . $color;
                })
                ->unique()
                ->values()
                ->all();

            $updates['team_member_colors'] = $colors ?: null;
        }

        if ($request->hasFile('logo')) {
            if ($tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
                Storage::disk('public')->delete($tenant->logo_path);
            }

            $path = $request->file('logo')->store('tenant-logos', 'public');
            $updates['logo_path'] = $path;
        }

        if (!empty($updates)) {
            $tenant->update($updates);
        }

        return redirect()
            ->route('tenant.trades.settings.profile', ['tenant' => $tenant->id])
            ->with('success', 'Profile updated.');
    }
}
