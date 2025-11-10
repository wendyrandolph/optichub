<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
  /**
   * Display a listing of the resource (all admins).
   */
  public function index(): View
  {
    // Policy check to ensure only authorized users can view the list
    $this->authorize('viewAny', Admin::class);

    // Uses Eloquent model to fetch all admins
    $admins = Admin::all();

    return view('admins.index', compact('admins'));
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create(): View
  {
    $this->authorize('create', Admin::class);

    return view('admins.create');
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param StoreAdminRequest $request Handles validation and authorization
   */
  public function store(StoreAdminRequest $request): RedirectResponse
  {
    // Validation and CSRF verification are handled by StoreAdminRequest

    // Hash the password before creating the record
    $data = $request->validated();
    $data['password'] = bcrypt($data['password']);

    Admin::create($data);

    return redirect()->route('admins.index')
      ->with('success', 'Admin added successfully.');
  }

  /**
   * Show the form for editing the specified resource.
   * Uses Route Model Binding to fetch the Admin instance.
   */
  public function edit(Admin $admin): View
  {
    // Policy check to ensure the user can update this specific admin
    $this->authorize('update', $admin);

    return view('admins.edit', compact('admin'));
  }

  /**
   * Update the specified resource in storage.
   *
   * @param UpdateAdminRequest $request Handles validation and authorization
   * @param Admin $admin The admin instance fetched via Route Model Binding
   */
  public function update(UpdateAdminRequest $request, Admin $admin): RedirectResponse
  {
    // Validation and CSRF verification are handled by UpdateAdminRequest

    $data = $request->validated();

    // Only hash the password if it was provided
    if (isset($data['password'])) {
      $data['password'] = bcrypt($data['password']);
    }

    $admin->update($data);

    return redirect()->route('admins.index')
      ->with('success', 'Admin updated successfully.');
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param Admin $admin The admin instance fetched via Route Model Binding
   */
  public function destroy(Admin $admin): RedirectResponse
  {
    // Policy check to ensure the user can delete this specific admin
    $this->authorize('delete', $admin);

    $admin->delete();

    return redirect()->route('admins.index')
      ->with('success', 'Admin deleted successfully.');
  }
}
