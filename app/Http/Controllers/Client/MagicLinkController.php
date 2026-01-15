<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MagicLinkController extends Controller
{
    public function show(Request $request)
    {
        return view('client.magic-link'); // or whatever view you intend
    }
}
