<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicReviewController extends Controller
{
    public function show(string $token)
    {
        abort(501, 'Public review flow not implemented.');
    }

    public function message(Request $request, string $token)
    {
        abort(501, 'Public review messaging not implemented.');
    }

    public function approve(Request $request, string $token)
    {
        abort(501, 'Public review approval not implemented.');
    }
}
