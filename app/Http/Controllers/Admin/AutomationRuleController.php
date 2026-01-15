<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\Opportunity;
use App\Services\OpportunityAutomation;
use Illuminate\Http\Request;

class AutomationRuleController extends Controller
{
    public function index()
    {
        $rules = AutomationRule::with(['runs' => function ($q) {
            $q->latest()->limit(1);
        }])->orderBy('name')->get();

        return view('admin.automation_rules.index', [
            'rules' => $rules,
        ]);
    }

    public function update(Request $request, AutomationRule $rule)
    {
        $rule->update([
            'active' => $request->boolean('active'),
        ]);

        return back()->with('success', 'Rule updated.');
    }

    public function test(Request $request, AutomationRule $rule)
    {
        $data = $request->validate([
            'opportunity_id' => ['required', 'exists:opportunities,id'],
            'dry_run' => ['sometimes', 'boolean'],
        ]);

        $opp = Opportunity::findOrFail($data['opportunity_id']);
        $auto = app(OpportunityAutomation::class);
        $auto->run($rule->trigger, $opp, ['test_run' => true], $request->boolean('dry_run', true));

        return back()->with('success', 'Test run queued.');
    }
}
