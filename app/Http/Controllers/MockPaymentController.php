<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MockPaymentController extends Controller
{
    public function show(Tenant $tenant, Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);
        $this->guardProviderMode();

        return redirect()
            ->route('tenant.invoices.show', ['tenant' => $tenant->id, 'invoice' => $invoice->id])
            ->with('status', 'Mock payment endpoint is ready. Simulation will be available in Phase 4.');
    }

    public function simulate(Tenant $tenant, Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);
        $this->guardProviderMode();

        abort_unless($invoice->tenant_id === $tenant->id, 404);

        DB::transaction(function () use ($tenant, $invoice) {
            $total = (float) ($invoice->total_amount ?? $invoice->total ?? 0);
            $paid = (float) InvoicePayment::where('tenant_id', $tenant->id)
                ->where('invoice_id', $invoice->id)
                ->sum('amount');
            $balance = max($total - $paid, 0);

            if ($balance <= 0.0001) {
                return;
            }

            InvoicePayment::create([
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'amount' => $balance,
                'method' => 'mock',
                'reference' => 'mock-' . now()->format('Ymd-His'),
                'paid_at' => now(),
            ]);

            $invoice->balance_due = 0;
            $invoice->status = 'paid';
            $invoice->save();
        });

        return redirect()
            ->route('tenant.invoices.show', ['tenant' => $tenant->id, 'invoice' => $invoice->id])
            ->with('status', 'Mock payment recorded.');
    }

    private function guardProviderMode(): void
    {
        $actor = Auth::guard('admin')->user() ?? Auth::user();

        if (!$actor || !method_exists($actor, 'isProviderAdmin') || !$actor->isProviderAdmin()) {
            abort(403, 'Mock payments are restricted to provider admins.');
        }

        if (!config('provider.enable_mock_payments')) {
            abort(404);
        }
    }
}
