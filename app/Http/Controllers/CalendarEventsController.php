<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\Service;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarEventsController extends Controller
{
    public function index(Request $request, $tenant): JsonResponse
    {
        $tenantId = $tenant instanceof \App\Models\Tenant ? $tenant->getKey() : (int) $tenant;

        $start = $request->input('start');
        $end = $request->input('end');
        $startAt = $start ? Carbon::parse($start) : now()->startOfMonth()->subWeek();
        $endAt = $end ? Carbon::parse($end) : now()->endOfMonth()->addWeek();

        $types = (array) $request->input('types', ['task', 'opportunity', 'invoice', 'service']);
        $memberId = $request->input('member_id');
        $projectId = $request->input('project_id');

        $events = collect();

        // Tasks
        if (in_array('task', $types, true)) {
            $tasks = Task::where('tenant_id', $tenantId)
                ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                ->where(function ($q) use ($startAt, $endAt) {
                    $q->whereBetween('due_date', [$startAt->toDateString(), $endAt->toDateString()]);
                })
                ->get();

            foreach ($tasks as $task) {
                $startDate = $task->due_date ? Carbon::parse($task->due_date)->setTime(9, 0) : null;
                if (! $startDate) {
                    continue;
                }
                $events->push([
                    'id' => 'task:' . $task->id,
                    'type' => 'task',
                    'title' => $task->title ?? 'Task',
                    'start' => $startDate->toIso8601String(),
                    'end' => null,
                    'allDay' => true,
                    'status' => $task->status ?? '',
                    'colorKey' => 'task',
                    // Force the show route explicitly (avoid accidental edit route resolution)
                    'url' => route('tenant.tasks.show', ['tenant' => $tenantId, 'task' => $task->id]),
                ]);
            }
        }

        // Opportunities
        if (in_array('opportunity', $types, true) && class_exists(Opportunity::class)) {
            $opps = Opportunity::where('tenant_id', $tenantId)
                ->whereNotNull('next_followup_at')
                ->whereBetween('next_followup_at', [$startAt, $endAt])
                ->when($memberId, fn($q) => $q->where('owner_id', $memberId))
                ->get();
            foreach ($opps as $opp) {
                $events->push([
                    'id' => 'opp:' . $opp->id,
                    'type' => 'opportunity',
                    'title' => 'Follow-up: ' . ($opp->title ?? 'Opportunity'),
                    'start' => optional($opp->next_followup_at)->toIso8601String(),
                    'end' => null,
                    'allDay' => false,
                    'status' => $opp->stage ?? '',
                    'colorKey' => 'opportunity',
                    'url' => route('tenant.opportunities.show', ['tenant' => $tenantId, 'opportunity' => $opp->id]),
                ]);
            }
        }

        // Invoices
        if (in_array('invoice', $types, true)) {
            $invoices = Invoice::where('tenant_id', $tenantId)
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$startAt->toDateString(), $endAt->toDateString()])
                ->with('client')
                ->get();
            foreach ($invoices as $inv) {
                $events->push([
                    'id' => 'invoice:' . $inv->id,
                    'type' => 'invoice',
                    'title' => 'Invoice due: ' . ($inv->invoice_number ?? ('INV-' . $inv->id)) . ' • ' . ($inv->client->firstName ?? ''),
                    'start' => Carbon::parse($inv->due_date)->toDateString(),
                    'end' => null,
                    'allDay' => true,
                    'status' => $inv->status ?? '',
                    'colorKey' => 'invoice',
                    'url' => route('tenant.invoices.show', ['tenant' => $tenantId, 'invoice' => $inv->id]),
                ]);
            }
        }

        // Services
        if (in_array('service', $types, true) && class_exists(Service::class)) {
            $services = Service::where('tenant_id', $tenantId)
                ->whereNotNull('renewal_date')
                ->whereBetween('renewal_date', [$startAt->toDateString(), $endAt->toDateString()])
                ->get();
            foreach ($services as $svc) {
                $events->push([
                    'id' => 'service:' . $svc->id,
                    'type' => 'service',
                    'title' => ucfirst($svc->type ?? 'Service') . ' renewal: ' . ($svc->name ?? 'Service'),
                    'start' => Carbon::parse($svc->renewal_date)->toDateString(),
                    'end' => null,
                    'allDay' => true,
                    'status' => $svc->status ?? '',
                    'colorKey' => 'service',
                    'url' => route('services.show', ['service' => $svc->id]),
                ]);
            }
        }

        return response()->json($events->values());
    }
}
