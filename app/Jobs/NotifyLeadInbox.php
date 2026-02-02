<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\Tenant;
use App\Models\TenantLeadSetting;
use App\Notifications\LeadInboxNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyLeadInbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $leadId
    ) {
    }

    public function handle(): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        if (!$tenant) {
            return;
        }

        $lead = Lead::query()
            ->where('tenant_id', $tenant->id)
            ->find($this->leadId);
        if (!$lead) {
            return;
        }

        $settings = TenantLeadSetting::query()->where('tenant_id', $tenant->id)->first();
        $recipients = $settings?->notify_email ?? $tenant->lead_notification_recipients ?? [];
        if (is_string($recipients)) {
            $recipients = array_filter(array_map('trim', preg_split('/[,\n]+/', $recipients)));
        }
        if (!is_array($recipients) || empty($recipients)) {
            return;
        }

        Notification::route('mail', $recipients)
            ->notify(new LeadInboxNotification($lead, $tenant));
    }
}
