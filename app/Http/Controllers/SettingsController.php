<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Tenant;
use App\Http\Requests\Settings\ApiGenerateRequest;
use App\Http\Requests\Settings\UpdateProfileRequest;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Throwable;

class SettingsController extends Controller
{
  protected ApiKey $apiKeyModel;
  protected Tenant $tenantModel;

  public function __construct(ApiKey $apiKeyModel, Tenant $tenantModel)
  {
    // Only keep auth here; apply tenant/role on the routes (cleaner & testable)
    $this->middleware('auth');
    $this->apiKeyModel = $apiKeyModel;
    $this->tenantModel = $tenantModel;
  }

  private function tenantId(): int
  {
    return (int) (Auth::user()->tenant_id ?? 0);
  }

  /** GET /{tenant}/settings */
  // app/Http/Controllers/SettingsController.php

  public function index()
  {
    \Log::info('SettingsController@index reached', ['tenant' => request()->route('tenant')]);
    return view('admin.settings.index');
  }

  public function billing()
  {
    \Log::info('SettingsController@index reached', ['tenant' => request()->route('tenant')]);
    return view('admin.settings.billing');
  }

  public function upgradeForm(): \Illuminate\View\View
  {
    \Log::info('SettingsController@index reached', ['tenant' => request()->route('tenant')]);
    $plans = [
      ['code' => 'starter', 'name' => 'Starter', 'price' => 1900, 'features' => ['Up to 3 projects', 'Email support']],
      ['code' => 'growth', 'name' => 'Growth', 'price' => 4900, 'features' => ['Unlimited projects', 'Priority support', 'API access']],
      ['code' => 'business', 'name' => 'Business', 'price' => 9900, 'features' => ['SLA support', 'Custom onboarding']],
    ];
    return view('admin.settings.billing-upgrade', compact('plans'));
  }

  public function apiIndex(): View
  {
    $tenantId    = $this->tenantId();
    $newPlainKey = session('flash_new_key');
    $keys        = $this->apiKeyModel->listActiveByTenant($tenantId);

    return view('admin.settings.api-keys', [
      'keys'        => $keys,
      'newPlainKey' => $newPlainKey,
      'apiKeyContext' => 'settings',
    ]);
  }
  public function apiGenerate(): RedirectResponse
  {
    $tenantId = $this->tenantId();

    // create a new key and get its plain value once
    [, $plain] = ApiKey::issue($tenantId, 'Settings generated', auth()->id());

    return redirect()
      ->route('tenant.settings.api.index', ['tenant' => $tenantId])
      ->with('flash_success', 'New API key generated.')
      ->with('flash_new_key', $plain); // show once in the UI
  }

  /** POST /{tenant}/settings/api/{keyId}/revoke */
  public function apiRevoke(string $keyId): RedirectResponse
  {
    $tenantId = $this->tenantId();

    ApiKey::revokeKey($tenantId, $keyId);

    return redirect()
      ->route('tenant.settings.api.index', ['tenant' => $tenantId])
      ->with('flash_success', 'API key revoked.');
  }


  public function profileForm(): View
  {
    $tenantId     = $this->tenantId();
    $tenant = $this->tenantModel->findOrFail($tenantId) ?? [];
    return view('admin.settings.profile', compact('tenant'));
  }
}
