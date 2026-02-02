<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeadInboxNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public Tenant $tenant
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenantKey = $this->tenant->getRouteKey();
        $routeName = $this->tenant->workspace_type === 'trades'
            ? 'tenant.trades.leads.show'
            : 'tenant.leads.show';
        $leadUrl = route($routeName, ['tenant' => $tenantKey, 'lead' => $this->lead->id]);

        return (new MailMessage())
            ->subject('New lead received')
            ->greeting('New lead received')
            ->line($this->lead->name ?? 'New lead')
            ->line($this->lead->email ? "Email: {$this->lead->email}" : 'Email: —')
            ->line($this->lead->phone ? "Phone: {$this->lead->phone}" : 'Phone: —')
            ->action('View lead', $leadUrl);
    }
}
