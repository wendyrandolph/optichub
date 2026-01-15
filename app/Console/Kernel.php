<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ExpireTrials;
use App\Console\Commands\FlagOverdueOpportunities;
use App\Jobs\ProcessInboundMailbox;
use App\Jobs\GenerateRecurringInvoices;
use App\Jobs\SendInvoiceReminders;
use App\Console\Commands\SendTradeAppointmentReminders;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        ExpireTrials::class,
        FlagOverdueOpportunities::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Inbound mail processing (keep existing cadence)
        $schedule->job(new ProcessInboundMailbox())->everyTwoMinutes();

        // Trials expiry daily
        $schedule->command('optic:expire-trials')->dailyAt('00:30');

        // Flag overdue opportunity follow-ups daily
        $schedule->command('opportunities:flag-overdue')->dailyAt('07:00');

        // Recurring invoices (hourly)
        $schedule->job(new GenerateRecurringInvoices())->hourly();

        // Invoice reminders (daily)
        $schedule->job(new SendInvoiceReminders())->dailyAt('08:00');

        // Trades appointment reminders (every 5 minutes)
        $schedule->command('trades:send-appointment-reminders')->everyFiveMinutes();

        // Trades PTO accruals (daily)
        $schedule->command('trades:accrue-pto')->dailyAt('01:30');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
