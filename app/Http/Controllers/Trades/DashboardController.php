<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\AppointmentAssignment;
use App\Models\ServiceLocation;
use App\Models\Tenant;
use App\Models\TradeAppointment;
use App\Models\TradeJob;
use App\Models\TradeQuote;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Tenant $tenant)
    {
        $todayStart = Carbon::today();
        $todayEnd = Carbon::tomorrow();
        $now = Carbon::now();

        $todayAppointments = TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('start_at', [$todayStart, $todayEnd])
            ->with(['job.client', 'job.company', 'job.serviceLocation', 'assignments.user'])
            ->orderBy('start_at')
            ->limit(10)
            ->get();

        $techsOnSite = AppointmentAssignment::query()
            ->where('tenant_id', $tenant->id)
            ->where('presence_status', 'on_site')
            ->with(['user', 'appointment.job'])
            ->orderByDesc('on_site_at')
            ->limit(20)
            ->get();

        $unscheduledJobs = TradeJob::query()
            ->where('tenant_id', $tenant->id)
            ->whereDoesntHave('appointments', fn($q) => $q->where('start_at', '>=', $now))
            ->with(['client'])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        $pendingQuotes = TradeQuote::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['sent'])
            ->with(['client'])
            ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END, expires_at ASC')
            ->limit(10)
            ->get();

        $counts = [
            'jobs_open' => TradeJob::where('tenant_id', $tenant->id)->where('status', '!=', 'closed')->count(),
            'jobs_unscheduled' => TradeJob::where('tenant_id', $tenant->id)
                ->whereDoesntHave('appointments', fn($q) => $q->where('start_at', '>=', $now))
                ->count(),
            'appts_today' => TradeAppointment::where('tenant_id', $tenant->id)
                ->whereBetween('start_at', [$todayStart, $todayEnd])
                ->count(),
            'techs_on_site' => AppointmentAssignment::where('tenant_id', $tenant->id)
                ->where('presence_status', 'on_site')
                ->count(),
            'quotes_pending' => TradeQuote::where('tenant_id', $tenant->id)->whereIn('status', ['sent'])->count(),
            'quotes_expiring' => TradeQuote::where('tenant_id', $tenant->id)
                ->whereIn('status', ['sent'])
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [$todayStart, $todayStart->copy()->addDays(3)])
                ->count(),
            'locations' => ServiceLocation::where('tenant_id', $tenant->id)->count(),
        ];

        $appointmentsThisWeek = TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('start_at', [$todayStart->copy()->startOfWeek(), $todayStart->copy()->endOfWeek()])
            ->count();

        $activeTechsToday = AppointmentAssignment::query()
            ->where('tenant_id', $tenant->id)
            ->where('presence_status', 'on_site')
            ->distinct('user_id')
            ->count('user_id');
        /* dd([
            'web' => auth('web')->check(),
            'admin' => auth('admin')->check(),
            'web_user' => auth('web')->user()?->email,
            'admin_user' => auth('admin')->user()?->email,
        ]);
        */
        return view('trades.dashboard', compact(
            'tenant',
            'counts',
            'todayAppointments',
            'techsOnSite',
            'unscheduledJobs',
            'pendingQuotes',
            'appointmentsThisWeek',
            'activeTechsToday'
        ));
    }
}
