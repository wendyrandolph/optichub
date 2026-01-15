<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\AppointmentAssignment;
use App\Models\TechShift;
use App\Models\TradeAppointment;
use App\Models\TradeAppointmentNote;
use App\Models\TradeAppointmentPhoto;
use App\Models\TradeJobTimer;
use App\Models\TradePtoBalance;
use App\Models\TradePtoRequest;
use App\Models\TradePtoType;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FieldController extends Controller
{
    public function today(Request $request, Tenant $tenant)
    {
        $user = $this->ensureTechUser($tenant);

        $tz = $tenant->timezone ?? config('app.timezone');
        $start = Carbon::now($tz)->startOfDay()->timezone('UTC');
        $end = Carbon::now($tz)->endOfDay()->timezone('UTC');
        $tab = $request->query('tab', 'jobs');

        $appointments = TradeAppointment::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('start_at', [$start, $end])
            ->whereHas('assignments', fn($q) => $q->where('user_id', $user->id))
            ->with(['job.client', 'job.company', 'job.serviceLocation', 'assignments.user'])
            ->withCount('assignments')
            ->orderBy('start_at')
            ->get();

        $openShift = $this->openShift($tenant->id, $user->id);
        $openTimer = $this->openTimer($tenant->id, $user->id);
        $shiftHistory = TechShift::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->orderByDesc('clock_in_at')
            ->limit(8)
            ->get();

        $ptoTypes = TradePtoType::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $ptoBalances = TradePtoBalance::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('trade_pto_type_id');

        $ptoRequests = TradePtoRequest::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->with('type')
            ->latest('requested_at')
            ->limit(5)
            ->get();

        return view('trades.field.today', [
            'tenant' => $tenant,
            'user' => $user,
            'appointments' => $appointments,
            'openShift' => $openShift,
            'openTimer' => $openTimer,
            'shiftHistory' => $shiftHistory,
            'ptoTypes' => $ptoTypes,
            'ptoBalances' => $ptoBalances,
            'ptoRequests' => $ptoRequests,
            'timezone' => $tz,
            'tab' => $tab,
        ]);
    }

    public function time(Tenant $tenant)
    {
        $user = $this->ensureTechUser($tenant);
        $tz = $tenant->timezone ?? config('app.timezone');

        $shiftHistory = TechShift::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->orderByDesc('clock_in_at')
            ->limit(10)
            ->get();

        return view('trades.field.time', [
            'tenant' => $tenant,
            'user' => $user,
            'openShift' => $this->openShift($tenant->id, $user->id),
            'openTimer' => $this->openTimer($tenant->id, $user->id),
            'shiftHistory' => $shiftHistory,
            'timezone' => $tz,
        ]);
    }

    public function pto(Tenant $tenant)
    {
        $user = $this->ensureTechUser($tenant);

        $ptoTypes = TradePtoType::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $ptoBalances = TradePtoBalance::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('trade_pto_type_id');

        $ptoRequests = TradePtoRequest::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->with('type')
            ->latest('requested_at')
            ->limit(5)
            ->get();

        return view('trades.field.pto', [
            'tenant' => $tenant,
            'user' => $user,
            'ptoTypes' => $ptoTypes,
            'ptoBalances' => $ptoBalances,
            'ptoRequests' => $ptoRequests,
        ]);
    }

    public function show(Tenant $tenant, TradeAppointment $appointment)
    {
        $user = $this->ensureTechUser($tenant);
        $this->abortIfWrongTenant($tenant, $appointment);

        $appointment->load([
            'job.client',
            'job.company',
            'job.serviceLocation',
            'assignments.user',
            'fieldNotes' => fn($q) => $q->latest(),
            'fieldNotes.user',
            'fieldPhotos' => fn($q) => $q->latest(),
            'fieldPhotos.user',
        ]);

        $assignment = $appointment->assignments->firstWhere('user_id', $user->id);
        if (!$assignment) {
            abort(403);
        }

        $openShift = $this->openShift($tenant->id, $user->id);
        $openTimer = $this->openTimer($tenant->id, $user->id);

        return view('trades.field.show', [
            'tenant' => $tenant,
            'user' => $user,
            'appointment' => $appointment,
            'assignment' => $assignment,
            'openShift' => $openShift,
            'openTimer' => $openTimer,
        ]);
    }

    public function storeNote(Request $request, Tenant $tenant, TradeAppointment $appointment)
    {
        $user = $this->ensureTechUser($tenant);
        $this->abortIfWrongTenant($tenant, $appointment);

        $appointment->load('assignments');
        $assignment = $appointment->assignments->firstWhere('user_id', $user->id);
        if (!$assignment) {
            abort(403);
        }

        $data = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        TradeAppointmentNote::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointment->id,
            'user_id' => $user->id,
            'note' => $data['note'],
        ]);

        return redirect()
            ->route('tenant.trades.field.show', ['tenant' => $tenant->id, 'appointment' => $appointment->id])
            ->with('success_message', 'Note saved.');
    }

    public function storePhoto(Request $request, Tenant $tenant, TradeAppointment $appointment)
    {
        $user = $this->ensureTechUser($tenant);
        $this->abortIfWrongTenant($tenant, $appointment);

        $appointment->load('assignments');
        $assignment = $appointment->assignments->firstWhere('user_id', $user->id);
        if (!$assignment) {
            abort(403);
        }

        $data = $request->validate([
            'photos' => 'required',
            'photos.*' => 'file|max:10240',
        ]);

        $disk = config('filesystems.default', 'private');
        foreach ($data['photos'] as $photo) {
            $ext = $photo->getClientOriginalExtension();
            $uuid = Str::uuid()->toString();
            $path = "tenants/{$tenant->id}/appointments/{$appointment->id}/photos/{$uuid}" . ($ext ? ".{$ext}" : '');

            $photo->storeAs($path, null, ['disk' => $disk]);

            TradeAppointmentPhoto::create([
                'tenant_id' => $tenant->id,
                'appointment_id' => $appointment->id,
                'user_id' => $user->id,
                'original_name' => $photo->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'mime' => $photo->getClientMimeType(),
                'size' => $photo->getSize(),
            ]);
        }

        return redirect()
            ->route('tenant.trades.field.show', ['tenant' => $tenant->id, 'appointment' => $appointment->id])
            ->with('success_message', 'Photos uploaded.');
    }

    public function clockIn(Request $request, Tenant $tenant)
    {
        $user = $this->ensureTechUser($tenant);

        $openShift = $this->openShift($tenant->id, $user->id);
        if ($openShift) {
            return redirect()
                ->route('tenant.trades.field.time', ['tenant' => $tenant->id])
                ->with('error_message', 'You are already clocked in.');
        }

        TechShift::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'clock_in_at' => now(),
        ]);

        return redirect()
            ->route('tenant.trades.field.time', ['tenant' => $tenant->id])
            ->with('success_message', 'Clocked in.');
    }

    public function clockOut(Request $request, Tenant $tenant)
    {
        $user = $this->ensureTechUser($tenant);

        $openShift = $this->openShift($tenant->id, $user->id);
        if (!$openShift) {
            return redirect()
                ->route('tenant.trades.field.time', ['tenant' => $tenant->id])
                ->with('error_message', 'You are not clocked in.');
        }

        $openTimer = $this->openTimer($tenant->id, $user->id);
        if ($openTimer) {
            $this->stopTimerAndPresence($openTimer, $user->id);
        }

        $openShift->clock_out_at = now();
        $openShift->save();

        return redirect()
            ->route('tenant.trades.field.time', ['tenant' => $tenant->id])
            ->with('success_message', 'Clocked out.');
    }

    public function startJob(Request $request, Tenant $tenant, TradeAppointment $appointment)
    {
        $user = $this->ensureTechUser($tenant);
        $this->abortIfWrongTenant($tenant, $appointment);
        $tz = $tenant->timezone ?? config('app.timezone');
        $startAt = $appointment->start_at?->copy()->timezone($tz);
        if (!$startAt || !$startAt->isSameDay(Carbon::now($tz))) {
            return redirect()
                ->route('tenant.trades.field.show', ['tenant' => $tenant->id, 'appointment' => $appointment->id])
                ->with('error_message', 'You can only start jobs scheduled for today.');
        }

        $appointment->load('assignments');
        $assignment = $appointment->assignments->firstWhere('user_id', $user->id);
        if (!$assignment) {
            abort(403);
        }

        $openShift = $this->openShift($tenant->id, $user->id);
        if (!$openShift) {
            return redirect()
                ->route('tenant.trades.field.show', ['tenant' => $tenant->id, 'appointment' => $appointment->id])
                ->with('error_message', 'Clock in before starting a job.');
        }

        $openTimer = $this->openTimer($tenant->id, $user->id);
        if ($openTimer) {
            return redirect()
                ->route('tenant.trades.field.show', ['tenant' => $tenant->id, 'appointment' => $appointment->id])
                ->with('error_message', 'You already have an active job timer.');
        }

        TradeJobTimer::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointment->id,
            'trade_job_id' => $appointment->trade_job_id,
            'user_id' => $user->id,
            'started_at' => now(),
        ]);

        $assignment->presence_status = 'on_site';
        if (!$assignment->on_site_at) {
            $assignment->on_site_at = now();
        }
        $assignment->save();

        return redirect()
            ->route('tenant.trades.field.show', ['tenant' => $tenant->id, 'appointment' => $appointment->id])
            ->with('success_message', 'Job timer started.');
    }

    public function stopJob(Request $request, Tenant $tenant, TradeAppointment $appointment)
    {
        $user = $this->ensureTechUser($tenant);
        $this->abortIfWrongTenant($tenant, $appointment);

        $timer = TradeJobTimer::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('appointment_id', $appointment->id)
            ->whereNull('ended_at')
            ->first();

        if (!$timer) {
            return redirect()
                ->route('tenant.trades.field.show', ['tenant' => $tenant->id, 'appointment' => $appointment->id])
                ->with('error_message', 'No active timer found for this job.');
        }

        $this->stopTimerAndPresence($timer, $user->id);

        return redirect()
            ->route('tenant.trades.field.show', ['tenant' => $tenant->id, 'appointment' => $appointment->id])
            ->with('success_message', 'Job timer stopped.');
    }

    protected function ensureTechUser(Tenant $tenant)
    {
        if (auth('admin')->check()) {
            abort(403);
        }

        $user = auth()->user();
        if (!$user || (int) $user->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $role = Str::of($user->role ?? '')->lower()->trim()->toString();
        $allowed = ['admin', 'dispatcher', 'tech', 'lead tech', 'lead-tech', 'lead_tech'];
        if (!in_array($role, $allowed, true)) {
            abort(403);
        }

        return $user;
    }

    protected function abortIfWrongTenant(Tenant $tenant, TradeAppointment $appointment): void
    {
        if ((int) $appointment->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }

    protected function openShift(int $tenantId, int $userId): ?TechShift
    {
        return TechShift::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('clock_out_at')
            ->latest('clock_in_at')
            ->first();
    }

    protected function openTimer(int $tenantId, int $userId): ?TradeJobTimer
    {
        return TradeJobTimer::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
    }

    protected function stopTimerAndPresence(TradeJobTimer $timer, int $userId): void
    {
        $timer->ended_at = now();
        $timer->save();

        $assignment = AppointmentAssignment::query()
            ->where('tenant_id', $timer->tenant_id)
            ->where('appointment_id', $timer->appointment_id)
            ->where('user_id', $userId)
            ->first();

        if ($assignment) {
            $assignment->presence_status = 'done';
            if (!$assignment->done_at) {
                $assignment->done_at = now();
            }
            $assignment->save();
        }

        $appointment = TradeAppointment::query()
            ->where('tenant_id', $timer->tenant_id)
            ->with('assignments')
            ->find($timer->appointment_id);

        if (!$appointment) {
            return;
        }

        $hasOpenTimers = TradeJobTimer::query()
            ->where('tenant_id', $timer->tenant_id)
            ->where('appointment_id', $timer->appointment_id)
            ->whereNull('ended_at')
            ->exists();

        $allDone = $appointment->assignments
            ->every(fn($assignment) => $assignment->presence_status === 'done');

        if (!$hasOpenTimers && $allDone && $appointment->status !== 'completed') {
            $appointment->status = 'completed';
            $appointment->updated_at = now();
            $appointment->save();
        }
    }
}
