<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use App\Models\Tenant;
use App\Models\TradeAppointment;
use App\Models\TradeAppointmentReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\TradeAppointmentReminderMail;

class SendTradeAppointmentReminders extends Command
{
    protected $signature = 'trades:send-appointment-reminders';
    protected $description = 'Send appointment reminder emails for trades tenants.';

    public function handle(): int
    {
        $now = now();

        Tenant::query()
            ->where('workspace_type', 'trades')
            ->where('reminders_enabled', true)
            ->chunkById(50, function ($tenants) use ($now) {
                foreach ($tenants as $tenant) {
                    $offsets = $tenant->reminder_offsets ?: [1440];
                    $offsets = collect($offsets)
                        ->map(fn($v) => (int) $v)
                        ->filter(fn($v) => $v > 0)
                        ->unique()
                        ->values()
                        ->all();

                    if (empty($offsets)) {
                        $offsets = [1440];
                    }

                    $appointments = TradeAppointment::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('start_at', '>', $now)
                        ->where('start_at', '<', $now->copy()->addDays(7))
                        ->with(['job.client', 'job.company', 'job.serviceLocation'])
                        ->get();

                    foreach ($appointments as $appointment) {
                        foreach ($offsets as $offsetMinutes) {
                            $target = $appointment->start_at->copy()->subMinutes($offsetMinutes);
                            if ($now->lt($target)) {
                                continue;
                            }
                            if ($now->gte($appointment->start_at)) {
                                continue;
                            }

                            $already = TradeAppointmentReminder::query()
                                ->where('appointment_id', $appointment->id)
                                ->where('offset_minutes', $offsetMinutes)
                                ->exists();

                            if ($already) {
                                continue;
                            }

                            $client = $appointment->job?->client;
                            $toEmail = $client?->email;
                            if (!$toEmail) {
                                TradeAppointmentReminder::create([
                                    'tenant_id' => $tenant->id,
                                    'appointment_id' => $appointment->id,
                                    'offset_minutes' => $offsetMinutes,
                                    'sent_at' => $now,
                                ]);
                                continue;
                            }

                            Mail::to($toEmail)->send(new TradeAppointmentReminderMail($tenant, $appointment, $offsetMinutes));

                            TradeAppointmentReminder::create([
                                'tenant_id' => $tenant->id,
                                'appointment_id' => $appointment->id,
                                'offset_minutes' => $offsetMinutes,
                                'sent_at' => $now,
                            ]);

                            EmailLog::create([
                                'tenant_id' => $tenant->id,
                                'provider' => 'system',
                                'direction' => 'outbound',
                                'from_email' => config('mail.from.address'),
                                'to_emails' => [$toEmail],
                                'subject' => 'Upcoming appointment reminder',
                                'snippet' => 'Appointment reminder sent.',
                                'sent_at' => $now,
                                'contact_id' => $client?->id,
                                'company_id' => $appointment->job?->company_id,
                                'status' => 'sent',
                            ]);
                        }
                    }
                }
            });

        return Command::SUCCESS;
    }
}
