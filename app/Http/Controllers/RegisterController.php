<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use App\Models\Tenant; // Import your Tenant model
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
  /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation.
    |
    */

  use RegistersUsers;

  /**
   * Where to redirect users after registration.
   *
   * This needs to be overridden to handle the tenant-scoped redirect.
   * @var string
   */
  protected $redirectTo = RouteServiceProvider::HOME;

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('guest');
  }

  /**
   * Get a validator for an incoming registration request.
   *
   * @param  array  $data
   * @return \Illuminate\Contracts\Validation\Validator
   */
  protected function validator(array $data)
  {
    return Validator::make($data, [
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
      'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);
  }

  /**
   * Create a new user instance and a new tenant after a valid registration.
   *
   * @param  array  $data
   * @return \App\Models\User
   */
  protected function create(array $data)
  {
    // We wrap the Tenant and User creation in a database transaction
    // to ensure both succeed or both fail (atomicity).
    return DB::transaction(function () use ($data) {
      // 1. Create the new Tenant record
      $tenant = Tenant::create([
        'name' => $data['name'],
        // We use the company/user name to generate a unique, URL-friendly slug
        'slug' => Str::slug($data['name']) . '-' . Str::random(5),
      ]);

      // 2. Create the User and associate them with the new Tenant
      $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'tenant_id' => $tenant->id, // Associate the user with the new tenant
        // Optional: set a role like 'admin' for the first user
        // 'role' => 'admin',
      ]);

      return $user;
    });
  }

  /**
   * Override the default `redirectPath` method to ensure the user is redirected
   * to the tenant-scoped home route (e.g., /acme-corp/home).
   *
   * @return string
   */
  public function redirectPath()
  {
    // Get the authenticated user (which was just created)
    $user = auth()->user();

    // Get the associated tenant slug
    $tenantSlug = $user->tenant->slug;

    // Use the named route 'tenant.home' we defined in routes/web.php
    return route('tenant.home', ['tenant' => $tenantSlug]);
  }
}
