<?php

namespace App\Services\Trades;

use App\Models\TechShift;
use App\Models\TradePtoBalance;
use App\Models\TradePtoLedger;
use App\Models\TradePtoRequest;
use Carbon\Carbon;

class TradePtoService
{
    public function approveRequest(TradePtoRequest $request, int $approverId): void
    {
        if ($request->status !== 'pending') {
            return;
        }

        $request->status = 'approved';
        $request->approved_by = $approverId;
        $request->approved_at = now();
        $request->save();

        $balance = TradePtoBalance::firstOrCreate(
            [
                'tenant_id' => $request->tenant_id,
                'user_id' => $request->user_id,
                'trade_pto_type_id' => $request->trade_pto_type_id,
            ],
            [
                'balance_hours' => 0,
                'last_accrued_at' => now(),
            ],
        );

        $balance->balance_hours = (float) $balance->balance_hours - (float) $request->hours_requested;
        $balance->save();

        TradePtoLedger::create([
            'tenant_id' => $request->tenant_id,
            'user_id' => $request->user_id,
            'trade_pto_type_id' => $request->trade_pto_type_id,
            'hours' => -1 * (float) $request->hours_requested,
            'reason' => 'request',
            'trade_pto_request_id' => $request->id,
            'note' => 'PTO approved',
        ]);

        $this->createPtoShifts($request);
    }

    public function denyRequest(TradePtoRequest $request, int $deniedBy): void
    {
        if ($request->status !== 'pending') {
            return;
        }

        $request->status = 'denied';
        $request->denied_by = $deniedBy;
        $request->denied_at = now();
        $request->save();
    }

    protected function createPtoShifts(TradePtoRequest $request): void
    {
        $start = Carbon::parse($request->start_date)->startOfDay();
        $end = Carbon::parse($request->end_date)->startOfDay();
        $days = max(1, $start->diffInDays($end) + 1);
        $hoursPerDay = (float) $request->hours_requested / $days;

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $clockIn = $day->copy()->setTime(9, 0);
            $clockOut = $clockIn->copy()->addMinutes((int) round($hoursPerDay * 60));

            TechShift::create([
                'tenant_id' => $request->tenant_id,
                'user_id' => $request->user_id,
                'shift_type' => 'pto',
                'trade_pto_request_id' => $request->id,
                'clock_in_at' => $clockIn,
                'clock_out_at' => $clockOut,
                'notes' => 'PTO',
            ]);
        }
    }
}
