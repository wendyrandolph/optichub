<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClientSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:client');
    }

    public function edit()
    {
        $client  = Auth::guard('client')->user();
        $contact = $client->contact; // from User::contact() you mentioned

        return view('portal.settings.edit', [
            'client'  => $client,
            'contact' => $contact,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $client  = Auth::guard('client')->user();
        $contact = $client->contact;

        $data = $request->validate([
            'first_name'  => ['required', 'string', 'max:255'],
            'last_name'   => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('contacts', 'email')->ignore($contact->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $contact->update([
            'firstName'  => $data['first_name'],
            'lastName'   => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        return redirect()
            ->route('portal.settings.edit')
            ->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $client = Auth::guard('client')->user();

        $data = $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $client->password)) {
            return back()
                ->withErrors(['current_password' => 'The current password is incorrect.'])
                ->withInput();
        }

        $client->password = Hash::make($data['password']);
        $client->save();

        return redirect()
            ->route('portal.settings.edit')
            ->with('status', 'Password updated.');
    }
}
