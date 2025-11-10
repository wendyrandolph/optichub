<?php

// app/Http/Controllers/TenantMailSettingController.php
namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantMailSettingController extends Controller
{
  public function edit(Tenant $tenant)
  {
    $ms = $tenant->mailSetting()->firstOrCreate([
      'tenant_id' => $tenant->id,
    ], [
      'provider' => 'smtp',
      'inbound_localpart' => 'inbox',
      'inbound_domain' => 'mail.optichub.app',
      'inbound_token' => bin2hex(random_bytes(8)),
      'auto_bcc_outbound' => true,
    ]);

    return view('tenant.mail-settings', ['tenant' => $tenant, 'settings' => $ms]);
  }

  public function update(Request $request, Tenant $tenant)
  {
    $data = $request->validate([
      'provider'          => 'required|string|in:smtp',
      'smtp_host'         => 'nullable|string',
      'smtp_port'         => 'nullable|integer',
      'smtp_tls'          => 'nullable|boolean',
      'smtp_user'         => 'nullable|string',
      'smtp_password'     => 'nullable|string',
      'from_name'         => 'nullable|string',
      'from_email'        => 'nullable|email',
      'inbound_localpart' => 'nullable|string',
      'inbound_domain'    => 'nullable|string',
      'auto_bcc_outbound' => 'nullable|boolean',
    ]);

    $tenant->mailSetting()->update($data);

    return back()->with('success', 'Mail settings updated.');
  }
}
