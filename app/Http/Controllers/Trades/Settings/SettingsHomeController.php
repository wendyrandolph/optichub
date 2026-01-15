<?php

namespace App\Http\Controllers\Trades\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant;

class SettingsHomeController extends Controller
{
    public function index(Tenant $tenant)
    {
        return view('trades.settings.index', ['tenant' => $tenant]);
    }
}
