<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendTrackedEmail;
use App\Mail\TestEmailMailable;
use App\Models\OutboundEmail;
use App\Models\ProviderEmailSetting;
use App\Services\MailConfigService;
use Illuminate\Http\Request;

class EmailSettingsController extends Controller
{
    public function index()
    {
        $admin = auth('admin')->user();
        abort_unless($admin && (method_exists($admin, 'isProviderAdmin') && $admin->isProviderAdmin()), 403);

        $settings = ProviderEmailSetting::query()->latest()->first();

        return view('admin.settings-email', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $admin = auth('admin')->user();
        abort_unless($admin && (method_exists($admin, 'isProviderAdmin') && $admin->isProviderAdmin()), 403);

        $data = $request->validate([
            'mailer' => ['nullable', 'string'],
            'host' => ['nullable', 'string'],
            'port' => ['nullable', 'integer'],
            'username' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
            'encryption' => ['nullable', 'string'],
            'from_address' => ['nullable', 'email'],
            'from_name' => ['nullable', 'string'],
            'reply_to' => ['nullable', 'email'],
        ]);

        $settings = ProviderEmailSetting::query()->latest()->first() ?? new ProviderEmailSetting();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $settings->fill($data);
        $settings->save();

        return redirect()
            ->route('admin.settings.email')
            ->with('success', 'Email settings updated.');
    }

    public function test(Request $request, MailConfigService $mailConfigService)
    {
        $admin = auth('admin')->user();
        abort_unless($admin && (method_exists($admin, 'isProviderAdmin') && $admin->isProviderAdmin()), 403);

        $data = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $mailConfigService->applyProviderSettings();

        $outbound = OutboundEmail::create([
            'tenant_id' => null,
            'user_id' => $admin->id,
            'to_email' => $data['test_email'],
            'subject' => 'Renlo email test',
            'mailable_type' => TestEmailMailable::class,
            'related_type' => null,
            'related_id' => null,
            'status' => 'queued',
        ]);

        SendTrackedEmail::dispatch(
            $outbound->id,
            TestEmailMailable::class,
            [],
            $data['test_email']
        );

        return redirect()
            ->route('admin.settings.email')
            ->with('success', 'Test email queued.');
    }
}
