<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TradePtoBalance;
use App\Models\TradePtoLedger;
use App\Models\TradePtoType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AccrueTradePto extends Command
{
    protected $signature = 'trades:accrue-pto';
    protected $description = 'Accrue PTO balances for trades tenants.';

    public function handle(): int
    {
        $tenants = Tenant::where('workspace_type', 'trades')->get();

        foreach ($tenants as $tenant) {
            $types = TradePtoType::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->get();

            if ($types->isEmpty()) {
                continue;
            }

            $users = User::where('tenant_id', $tenant->id)
                ->whereIn('role', ['admin', 'dispatcher', 'tech', 'lead_tech', 'lead tech', 'lead-tech'])
                ->get();

            foreach ($users as $user) {
                foreach ($types as $type) {
                    $balance = TradePtoBalance::firstOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'user_id' => $user->id,
                            'trade_pto_type_id' => $type->id,
                        ],
                        [
                            'balance_hours' => 0,
                            'last_accrued_at' => now(),
                        ],
                    );

                    $from = $balance->last_accrued_at ? Carbon::parse($balance->last_accrued_at) : now();
                    $now = now();

                    $rate = (float) $type->accrual_rate_hours;
                    $tenureMonths = null;
                    if (!empty($type->tenure_months) && $type->tenure_accrual_rate_hours !== null) {
                        $startDate = $user->hired_at ?? $user->created_at;
                        if ($startDate) {
                            $tenureMonths = Carbon::parse($startDate)->diffInMonths($now);
                            if ($tenureMonths >= (int) $type->tenure_months) {
                                $rate = (float) $type->tenure_accrual_rate_hours;
                            }
                        }
                    }

                    if ($type->accrual_period === 'pay_period') {
                        $periods = intdiv($from->diffInDays($now), 14);
                        if ($periods <= 0) {
                            continue;
                        }
                        $addHours = $periods * $rate;
                        $balance->balance_hours += $addHours;
                        $balance->last_accrued_at = $from->copy()->addDays($periods * 14);
                    } else {
                        $periods = $from->diffInMonths($now);
                        if ($periods <= 0) {
                            continue;
                        }
                        $addHours = $periods * $rate;
                        $balance->balance_hours += $addHours;
                        $balance->last_accrued_at = $from->copy()->addMonths($periods);
                    }

                    $balance->save();

                    TradePtoLedger::create([
                        'tenant_id' => $tenant->id,
                        'user_id' => $user->id,
                        'trade_pto_type_id' => $type->id,
                        'hours' => $addHours,
                        'reason' => 'accrual',
                        'note' => 'Auto accrual',
                    ]);
                }
            }
        }

        return Command::SUCCESS;
    }
}
