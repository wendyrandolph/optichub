<?php

namespace App\Services;

use App\Models\AutomationRule;
use App\Models\Notification;
use App\Models\Opportunity;
use App\Models\OpportunityActivity;
use Carbon\Carbon;
use App\Models\AutomationRun;

class OpportunityAutomation
{
    /**
     * Run automation rules for a given trigger.
     *
     * @param string $trigger
     * @param Opportunity $opportunity
     * @param array $context optional extra data (e.g., ['from' => 'qualified', 'to' => 'won'])
     * @param bool $dryRun only log run without executing actions
     */
    public function run(string $trigger, Opportunity $opportunity, array $context = [], bool $dryRun = false): void
    {
        $rules = AutomationRule::where('tenant_id', $opportunity->tenant_id)
            ->where('trigger', $trigger)
            ->where('active', true)
            ->get();

        foreach ($rules as $rule) {
            $start = microtime(true);
            $runPayload = ['trigger' => $trigger, 'context' => $context, 'actions' => []];
            $status = 'success';
            $error = null;
            $actions = $rule->actions ?? [];

            try {
                foreach ($actions as $action) {
                    $result = $this->executeAction($rule->id, $action, $opportunity, $context, $dryRun);
                    $runPayload['actions'][] = $result;
                }
            } catch (\Throwable $e) {
                $status = 'failed';
                $error = $e->getMessage();
            }

            AutomationRun::create([
                'tenant_id' => $opportunity->tenant_id,
                'rule_id' => $rule->id,
                'opportunity_id' => $opportunity->id,
                'status' => $dryRun ? 'dry_run' : $status,
                'error' => $error,
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                'payload' => $runPayload,
                'run_key' => $this->makeRunKey($rule->id, $trigger, $opportunity->id, $context),
            ]);
        }
    }

    protected function executeAction(int $ruleId, array $action, Opportunity $opp, array $context, bool $dryRun = false): array
    {
        $type = $action['type'] ?? '';
        $payload = $action['payload'] ?? [];
        $runKey = $this->makeRunKey($ruleId, $type, $opp->id, $payload);

        // skip if already executed successfully (idempotent)
        if (!$dryRun && AutomationRun::where('run_key', $runKey)->where('status', 'success')->exists()) {
            return ['type' => $type, 'status' => 'skipped'];
        }

        switch ($type) {
            case 'add_activity_note':
                $body = $payload['body'] ?? 'Automation note';
                if (!$dryRun) {
                    OpportunityActivity::create([
                        'tenant_id' => $opp->tenant_id,
                        'opportunity_id' => $opp->id,
                        'user_id' => $payload['user_id'] ?? null,
                        'type' => 'note',
                        'body' => $body,
                        'meta' => array_merge($context, ['rule_id' => $ruleId]),
                    ]);
                }
                return ['type' => $type, 'status' => $dryRun ? 'dry_run' : 'success'];

            case 'assign_owner':
                if (!empty($payload['owner_id'])) {
                    if (!$dryRun) {
                        $opp->owner_id = $payload['owner_id'];
                        $opp->saveQuietly();
                    }
                }
                return ['type' => $type, 'status' => $dryRun ? 'dry_run' : 'success'];

            case 'set_followup_date':
                if (!empty($payload['next_followup_at'])) {
                    if (!$dryRun) {
                        $opp->next_followup_at = Carbon::parse($payload['next_followup_at']);
                        $opp->saveQuietly();
                    }
                }
                return ['type' => $type, 'status' => $dryRun ? 'dry_run' : 'success'];

            case 'create_reminder':
                // simple internal reminder via Notification table
                if (!empty($opp->owner_id) && !$dryRun) {
                    Notification::create([
                        'tenant_id' => $opp->tenant_id,
                        'user_id' => $opp->owner_id,
                        'type' => 'opportunity.reminder',
                        'data' => [
                            'opportunity_id' => $opp->id,
                            'rule_id' => $ruleId,
                            'message' => $payload['message'] ?? 'Opportunity reminder',
                        ],
                    ]);
                }
                return ['type' => $type, 'status' => $dryRun ? 'dry_run' : 'success'];

            default:
                return ['type' => $type, 'status' => 'unknown_action'];
        }
    }

    protected function makeRunKey(int $ruleId, string $kind, int $oppId, array $data): string
    {
        return sha1($ruleId . ':' . $kind . ':' . $oppId . ':' . json_encode($data));
    }
}
