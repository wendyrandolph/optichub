<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function seen(Request $request)
    {
        $user = Auth::guard('client')->user();
        if (! $user) {
            abort(403);
        }

        $user->forceFill(['portal_last_seen_at' => now()])->save();

        return response()->json(['ok' => true]);
    }
}
