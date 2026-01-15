<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClientCompany;
use App\Models\Contact;
use App\Models\TradeJob;
use App\Models\TradeQuote;
use App\Models\TradeQuoteAcceptance;
use App\Models\TradeQuoteItem;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QuoteController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        $this->authorize('viewAny', TradeQuote::class);

        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));
        $from = $this->parseDate($request->query('from'));
        $to = $this->parseDate($request->query('to'), true);
        $amountMin = $request->query('amount_min');
        $amountMax = $request->query('amount_max');
        $siteVisitOnly = (bool) $request->query('site_visit');
        $now = now();
        $hasLastViewed = Schema::hasColumn('trade_quotes', 'last_viewed_at');

        $query = TradeQuote::query()
            ->where('tenant_id', $tenant->id)
            ->with(['client', 'company', 'job.serviceLocation']);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhereHas('client', function ($client) use ($search) {
                        $client->where('firstName', 'like', '%' . $search . '%')
                            ->orWhere('lastName', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($status !== '' && $status !== 'all') {
            if ($status === 'draft') {
                $query->whereIn('status', ['draft', 'ready_to_send']);
            } elseif ($status === 'sent') {
                $query->where('status', 'sent')
                    ->when($hasLastViewed, function ($builder) {
                        $builder->whereNull('last_viewed_at');
                    })
                    ->where(function ($builder) use ($now) {
                        $builder->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', $now);
                    });
            } elseif ($status === 'viewed') {
                $query->where('status', 'sent')
                    ->when($hasLastViewed, function ($builder) {
                        $builder->whereNotNull('last_viewed_at');
                    })
                    ->where(function ($builder) use ($now) {
                        $builder->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', $now);
                    });
            } elseif ($status === 'expired') {
                $query->where('status', '!=', 'accepted')
                    ->where('status', '!=', 'archived')
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<', $now);
            } else {
                $query->where('status', $status);
            }
        }

        if ($siteVisitOnly) {
            $query->where('status', 'needs_site_visit');
        }

        if ($amountMin !== null && $amountMin !== '') {
            $query->where('total', '>=', (float) $amountMin);
        }

        if ($amountMax !== null && $amountMax !== '') {
            $query->where('total', '<=', (float) $amountMax);
        }

        if ($from || $to) {
            $dateExpr = DB::raw('COALESCE(sent_at, created_at)');
            if ($from && $to) {
                $query->whereBetween($dateExpr, [$from, $to]);
            } elseif ($from) {
                $query->where($dateExpr, '>=', $from);
            } else {
                $query->where($dateExpr, '<=', $to);
            }
        }

        $rangeFrom = $from ?: now()->startOfMonth();
        $rangeTo = $to ?: now()->endOfMonth();

        $sentQuery = TradeQuote::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('sent_at')
            ->whereBetween('sent_at', [$rangeFrom, $rangeTo]);

        $sentCount = (clone $sentQuery)->count();
        $sentAmount = (clone $sentQuery)->sum('total');

        $acceptedQuery = TradeQuoteAcceptance::query()
            ->join('trade_quotes', 'trade_quotes.id', '=', 'trade_quote_acceptances.trade_quote_id')
            ->where('trade_quotes.tenant_id', $tenant->id)
            ->whereBetween('trade_quote_acceptances.accepted_at', [$rangeFrom, $rangeTo]);

        $acceptedCount = (clone $acceptedQuery)->count();

        $acceptedAmount = TradeQuote::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'accepted')
            ->whereHas('acceptance', function ($builder) use ($rangeFrom, $rangeTo) {
                $builder->whereBetween('accepted_at', [$rangeFrom, $rangeTo]);
            })
            ->sum('total');

        $acceptanceRate = $sentCount > 0 ? round(($acceptedCount / $sentCount) * 100, 1) : null;

        $responseSeconds = TradeQuoteAcceptance::query()
            ->select('trade_quote_acceptances.accepted_at', 'trade_quotes.sent_at')
            ->join('trade_quotes', 'trade_quotes.id', '=', 'trade_quote_acceptances.trade_quote_id')
            ->where('trade_quotes.tenant_id', $tenant->id)
            ->whereBetween('trade_quote_acceptances.accepted_at', [$rangeFrom, $rangeTo])
            ->whereNotNull('trade_quotes.sent_at')
            ->get()
            ->map(function ($row) {
                return Carbon::parse($row->sent_at)->diffInSeconds(Carbon::parse($row->accepted_at));
            })
            ->filter()
            ->values();

        $avgSeconds = $responseSeconds->isNotEmpty()
            ? (int) round($responseSeconds->average())
            : null;

        $quotes = $query
            ->orderByRaw(
                "CASE
                    WHEN status = 'needs_site_visit' THEN 0
                    WHEN status IN ('draft','ready_to_send') THEN 1
                    WHEN status = 'sent' THEN 2
                    WHEN status = 'accepted' THEN 3
                    WHEN status != 'accepted' AND expires_at IS NOT NULL AND expires_at < ? THEN 4
                    ELSE 5
                END ASC",
                [$now]
            )
            ->orderByRaw('COALESCE(sent_at, created_at) DESC')
            ->paginate(20)
            ->withQueryString();

        return view('trades.quotes.index', [
            'tenant' => $tenant,
            'quotes' => $quotes,
            'activeStatus' => $status,
            'search' => $search,
            'filters' => [
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
                'amount_min' => $amountMin,
                'amount_max' => $amountMax,
                'site_visit' => $siteVisitOnly ? '1' : null,
            ],
            'kpis' => [
                'sent' => $sentCount,
                'accepted' => $acceptedCount,
                'acceptance_rate' => $acceptanceRate,
                'avg_accept_seconds' => $avgSeconds,
                'sent_amount' => $sentAmount,
                'accepted_amount' => $acceptedAmount,
                'range_from' => $rangeFrom,
                'range_to' => $rangeTo,
            ],
        ]);
    }

    private function parseDate(?string $value, bool $endOfDay = false): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            $date = Carbon::parse($value);
            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function create(Tenant $tenant)
    {
        $this->authorize('create', TradeQuote::class);

        return view('trades.quotes.create', [
            'tenant' => $tenant,
            'clients' => Contact::where('tenant_id', $tenant->id)->orderBy('firstName')->get(),
            'companies' => ClientCompany::where('tenant_id', $tenant->id)->orderBy('company_name')->get(),
            'jobs' => TradeJob::where('tenant_id', $tenant->id)->orderBy('summary')->get(),
            'itemRows' => $this->itemRowsFromOld(),
        ]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $this->authorize('create', TradeQuote::class);

        $data = $this->validateQuote($request, $tenant);
        [$items, $subtotal, $taxTotal, $discountTotal, $total] = $this->buildItems(
            $data['items'] ?? [],
            (float) ($data['tax_rate'] ?? 0),
            (string) ($data['discount_type'] ?? 'none'),
            (float) ($data['discount_value'] ?? 0),
        );

        $quote = TradeQuote::create([
            'tenant_id' => $tenant->id,
            'client_id' => $data['client_id'],
            'company_id' => $data['company_id'] ?? null,
            'trade_job_id' => $data['trade_job_id'] ?? null,
            'title' => $data['title'],
            'notes' => $data['notes'] ?? null,
            'status' => 'draft',
            'subtotal' => $subtotal,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'tax_total' => $taxTotal,
            'discount_type' => $data['discount_type'] ?? 'none',
            'discount_value' => $data['discount_value'] ?? 0,
            'discount_total' => $discountTotal,
            'total' => $total,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        foreach ($items as $item) {
            $quote->items()->create($item);
        }

        return redirect()
            ->route('tenant.trades.quotes.show', ['tenant' => $tenant->id, 'quote' => $quote->id])
            ->with('success_message', 'Quote created.');
    }

    public function show(Tenant $tenant, TradeQuote $quote)
    {
        $this->authorize('view', $quote);
        $this->abortIfWrongTenant($tenant, $quote);

        $quote->load(['client', 'company', 'job', 'items', 'acceptance']);

        return view('trades.quotes.show', [
            'tenant' => $tenant,
            'quote' => $quote,
            'publicLink' => session('quote_link'),
        ]);
    }

    public function edit(Tenant $tenant, TradeQuote $quote)
    {
        $this->authorize('update', $quote);
        $this->abortIfWrongTenant($tenant, $quote);

        if ($quote->isLocked()) {
            return redirect()
                ->route('tenant.trades.quotes.show', ['tenant' => $tenant->id, 'quote' => $quote->id])
                ->with('error_message', 'Quote has been sent and cannot be edited. Duplicate to revise.');
        }

        $quote->load('items');

        return view('trades.quotes.edit', [
            'tenant' => $tenant,
            'quote' => $quote,
            'clients' => Contact::where('tenant_id', $tenant->id)->orderBy('firstName')->get(),
            'companies' => ClientCompany::where('tenant_id', $tenant->id)->orderBy('company_name')->get(),
            'jobs' => TradeJob::where('tenant_id', $tenant->id)->orderBy('summary')->get(),
            'itemRows' => $this->itemRowsFromQuote($quote),
        ]);
    }

    public function update(Request $request, Tenant $tenant, TradeQuote $quote)
    {
        $this->authorize('update', $quote);
        $this->abortIfWrongTenant($tenant, $quote);

        if ($quote->isLocked()) {
            return redirect()
                ->route('tenant.trades.quotes.show', ['tenant' => $tenant->id, 'quote' => $quote->id])
                ->with('error_message', 'Quote has been sent and cannot be edited. Duplicate to revise.');
        }

        $data = $this->validateQuote($request, $tenant, $quote);
        [$items, $subtotal, $taxTotal, $discountTotal, $total] = $this->buildItems(
            $data['items'] ?? [],
            (float) ($data['tax_rate'] ?? 0),
            (string) ($data['discount_type'] ?? 'none'),
            (float) ($data['discount_value'] ?? 0),
        );

        $quote->update([
            'client_id' => $data['client_id'],
            'company_id' => $data['company_id'] ?? null,
            'trade_job_id' => $data['trade_job_id'] ?? null,
            'title' => $data['title'],
            'notes' => $data['notes'] ?? null,
            'subtotal' => $subtotal,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'tax_total' => $taxTotal,
            'discount_type' => $data['discount_type'] ?? 'none',
            'discount_value' => $data['discount_value'] ?? 0,
            'discount_total' => $discountTotal,
            'total' => $total,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $quote->items()->delete();
        foreach ($items as $item) {
            $quote->items()->create($item);
        }

        return redirect()
            ->route('tenant.trades.quotes.show', ['tenant' => $tenant->id, 'quote' => $quote->id])
            ->with('success_message', 'Quote updated.');
    }

    public function destroy(Tenant $tenant, TradeQuote $quote)
    {
        $this->authorize('delete', $quote);
        $this->abortIfWrongTenant($tenant, $quote);

        if ($quote->isLocked()) {
            return redirect()
                ->route('tenant.trades.quotes.show', ['tenant' => $tenant->id, 'quote' => $quote->id])
                ->with('error_message', 'Sent quotes cannot be deleted.');
        }

        $quote->delete();

        return redirect()
            ->route('tenant.trades.quotes.index', ['tenant' => $tenant->id])
            ->with('success_message', 'Quote deleted.');
    }

    public function archive(Tenant $tenant, TradeQuote $quote)
    {
        $this->authorize('update', $quote);
        $this->abortIfWrongTenant($tenant, $quote);

        if ($quote->status === 'archived') {
            return redirect()
                ->route('tenant.trades.quotes.show', ['tenant' => $tenant->id, 'quote' => $quote->id])
                ->with('success_message', 'Quote is already archived.');
        }

        $quote->status = 'archived';
        $quote->save();

        return redirect()
            ->route('tenant.trades.quotes.show', ['tenant' => $tenant->id, 'quote' => $quote->id])
            ->with('success_message', 'Quote archived.');
    }

    public function send(Tenant $tenant, TradeQuote $quote)
    {
        $this->authorize('update', $quote);
        $this->abortIfWrongTenant($tenant, $quote);

        if ($quote->status === 'archived') {
            return redirect()
                ->route('tenant.trades.quotes.show', ['tenant' => $tenant->id, 'quote' => $quote->id])
                ->with('error_message', 'Archived quotes cannot be sent.');
        }

        if ($quote->status === 'accepted') {
            return redirect()
                ->route('tenant.trades.quotes.show', ['tenant' => $tenant->id, 'quote' => $quote->id])
                ->with('error_message', 'Quote is already accepted.');
        }

        if (!$quote->token_hash) {
            $token = Str::random(64);
            $quote->token_hash = hash('sha256', $token);
            $quote->sent_at = now();
            $quote->status = 'sent';
            $quote->save();

            $link = route('public.trade-quotes.show', ['token' => $token]);

            return redirect()
                ->route('tenant.trades.quotes.show', ['tenant' => $tenant->id, 'quote' => $quote->id])
                ->with('success_message', 'Quote sent.')
                ->with('quote_link', $link);
        }

        if ($quote->status === 'draft') {
            $quote->status = 'sent';
            $quote->sent_at = $quote->sent_at ?? now();
            $quote->save();
        }

        return redirect()
            ->route('tenant.trades.quotes.show', ['tenant' => $tenant->id, 'quote' => $quote->id])
            ->with('success_message', 'Quote sent.');
    }

    public function duplicate(Tenant $tenant, TradeQuote $quote)
    {
        $this->authorize('view', $quote);
        $this->abortIfWrongTenant($tenant, $quote);

        $quote->load('items');

        $copy = TradeQuote::create([
            'tenant_id' => $tenant->id,
            'client_id' => $quote->client_id,
            'company_id' => $quote->company_id,
            'trade_job_id' => $quote->trade_job_id,
            'title' => $quote->title . ' (Copy)',
            'notes' => $quote->notes,
            'status' => 'draft',
            'subtotal' => $quote->subtotal,
            'tax_rate' => $quote->tax_rate,
            'tax_total' => $quote->tax_total,
            'discount_type' => $quote->discount_type,
            'discount_value' => $quote->discount_value,
            'discount_total' => $quote->discount_total,
            'total' => $quote->total,
            'expires_at' => $quote->expires_at,
        ]);

        foreach ($quote->items as $item) {
            $copy->items()->create([
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ]);
        }

        return redirect()
            ->route('tenant.trades.quotes.edit', ['tenant' => $tenant->id, 'quote' => $copy->id])
            ->with('success_message', 'Quote duplicated. You can edit this draft.');
    }

    protected function validateQuote(Request $request, Tenant $tenant, ?TradeQuote $quote = null): array
    {
        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                Rule::exists('contacts', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'company_id' => [
                'nullable',
                Rule::exists('client_companies', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'trade_job_id' => [
                'nullable',
                Rule::exists('trade_jobs', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', Rule::in(['none', 'percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $items = $request->input('items', []);
            if (!is_array($items)) {
                return;
            }

            foreach ($items as $index => $item) {
                $description = trim((string) ($item['description'] ?? ''));
                $quantity = (float) ($item['quantity'] ?? 0);
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                if ($description === '' && ($unitPrice > 0 || $quantity > 1)) {
                    $validator->errors()->add("items.{$index}.description", 'Add a description for this line item.');
                }
            }
        });

        return $validator->validate();
    }

    protected function buildItems(array $items, float $taxRate, string $discountType, float $discountValue): array
    {
        $normalized = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $description = trim((string) ($item['description'] ?? ''));
            if ($description === '') {
                continue;
            }

            $quantity = (float) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $lineTotal = round($quantity * $unitPrice, 2);
            $subtotal += $lineTotal;

            $normalized[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        $taxTotal = $taxRate > 0 ? $subtotal * ($taxRate / 100) : 0.0;
        $discountTotal = 0.0;
        if ($discountType === 'percent') {
            $discountTotal = ($subtotal + $taxTotal) * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $discountTotal = $discountValue;
        }
        $total = max(0, $subtotal + $taxTotal - $discountTotal);

        return [
            $normalized,
            round($subtotal, 2),
            round($taxTotal, 2),
            round($discountTotal, 2),
            round($total, 2),
        ];
    }

    protected function itemRowsFromOld(): array
    {
        $rows = old('items', []);
        if (!is_array($rows) || empty($rows)) {
            $rows = array_fill(0, 5, ['description' => '', 'quantity' => 1, 'unit_price' => 0]);
        }

        return array_slice($rows, 0, 8);
    }

    protected function itemRowsFromQuote(TradeQuote $quote): array
    {
        $rows = old('items');
        if (is_array($rows) && !empty($rows)) {
            return array_slice($rows, 0, 8);
        }

        $rows = $quote->items->map(function (TradeQuoteItem $item) {
            return [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ];
        })->toArray();

        if (count($rows) < 5) {
            $rows = array_pad($rows, 5, ['description' => '', 'quantity' => 1, 'unit_price' => 0]);
        }

        return array_slice($rows, 0, 8);
    }

    protected function abortIfWrongTenant(Tenant $tenant, TradeQuote $quote): void
    {
        if ((int) $quote->tenant_id !== (int) $tenant->id) {
            abort(404);
        }
    }
}
