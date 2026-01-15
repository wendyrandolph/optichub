<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use App\Models\InvoicePayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use DateTimeInterface;

/**
 * Eloquent Model for the 'invoices' table.
 * Enforces multi-tenancy using HasTenantScope and includes complex financial reporting methods.
 */
class Invoice extends Model
{
    use HasFactory, HasTenantScope;

    protected $table = 'invoices';

    // Laravel uses created_at and updated_at by default.
    public $timestamps = true;

    protected $fillable = [
        'contact_id',
        'project_id',
        'trade_job_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'status',
        'sent_at',
        'updated_after_sent',
        'notes',
        'subtotal',
        'tax_rate',
        'tax_total',
        'discount_type',
        'discount_value',
        'total',
        'currency',
        'stripe_link',
        'tenant_id' // Will be automatically set by the HasTenantScope trait
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'issue_date'   => 'date',
        'due_date'     => 'date',
        'subtotal'     => 'decimal:2',
        'tax_total'    => 'decimal:2',
        'tax_rate'     => 'decimal:2',
        'discount_value' => 'decimal:2',
        'total'        => 'decimal:2',
        'total_amount' => 'decimal:2',
        'balance_due'  => 'decimal:2',
        'tax_breakdown' => 'array',
        'sent_at'      => 'datetime',
        'updated_after_sent' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function (self $invoice) {
            if ($invoice->sent_at && $invoice->isDirty()) {
                $invoice->updated_after_sent = true;
            }
        });
    }


    // --- Relationships ---
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * An invoice belongs to a Client.
     */
    public function client()
    {
        // Your Client model is an alias to contacts table
        return $this->belongsTo(\App\Models\Client::class, 'contact_id');
    }
    public function lineItems()
    {
        return $this->hasMany(InvoiceLineItem::class, 'invoice_id')->orderBy('position');
    }

    /**
     * An invoice belongs to an optional Project.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tradeJob()
    {
        return $this->belongsTo(TradeJob::class, 'trade_job_id');
    }

    /**
     * An invoice has many Invoice Items (for calculating total).
     */
    public function items()
    {
        // Backwards-compat alias
        return $this->lineItems();
    }

    /**
     * An invoice has many Invoice Payments.
     */
    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    // --- Core CRUD & Retrieval Refactors ---

    /**
     * Replaces the procedural getAll() method, including the client name join.
     */
    public static function getAllWithClientName(?int $limit = null, ?int $offset = null): Collection
    {
        // Select all invoice columns (i.*) and the concatenated client name.
        $query = static::select('invoices.*')
            ->selectRaw("CONCAT(clients.firstName, ' ', clients.lastName) AS client_name")
            ->join('clients', 'invoices.contact_id', '=', 'clients.id')
            ->orderBy('invoices.issue_date', 'DESC');

        if ($limit !== null) {
            $query->limit($limit);
        }
        if ($offset !== null) {
            $query->offset($offset);
        }

        // The HasTenantScope is automatically applied here via the static::query()
        return $query->get();
    }

    public function getAmountPaidAttribute()
    {
        return (float) $this->total_amount - (float) $this->balance_due;
    }

    public function getIsPaidAttribute()
    {
        return $this->balance_due <= 0 && $this->status === 'paid';
    }

    public function getStatusLabelAttribute()
    {
        // simple example, tweak as you like
        return ucfirst($this->status);
    }

    public function getWasEditedAfterSendAttribute(): bool
    {
        if (!$this->sent_at) {
            return false;
        }

        return (bool)$this->updated_after_sent || ($this->updated_at && $this->updated_at->gt($this->sent_at));
    }
    /**
     * Retrieves a single invoice by ID, automatically tenant-scoped.
     * Replaces getById().
     */
    public static function getInvoiceById(int $id): ?self
    {
        // Eloquent's find() implicitly adds the tenant scope.
        return static::with('client')
            ->select('invoices.*')
            ->selectRaw("CONCAT(clients.firstName, ' ', clients.lastName) AS client_name")
            ->join('clients', 'invoices.contact_id', '=', 'clients.id')
            ->find($id);
    }

    /**
     * Retrieves the calculated total for an invoice, replacing getTotalById().
     */
    public function getTotalAmount(): float
    {
        // Use the items relationship to calculate SUM(quantity * unit_price)
        $total = $this->items()
            ->selectRaw('SUM(quantity * unit_price) as total')
            ->value('total');

        return (float) $total;
    }

    /**
     * Retrieves invoices by client ID, automatically tenant-scoped.
     * Replaces getByClientId().
     */
    public static function getByClientId(int $clientId): Collection
    {
        return static::where('contact_id', $clientId)
            ->with('client') // Eager load client name (if needed for display)
            ->orderBy('issue_date', 'DESC')
            ->get();
    }

    // --- Financial Reporting Refactors (Leveraging DB::raw and Scopes) ---

    /**
     * Helper to find a suitable column for invoice amount (mimics procedural logic).
     */
    private function firstExistingAmountColumn(): string
    {
        // In a perfect Eloquent setup, you would have a 'total_amount' column.
        // For compatibility with the legacy logic, we assume 'total_amount' exists,
        // or fall back to calculating the total if it doesn't.
        $candidates = ['total_amount', 'total', 'amount', 'grand_total', 'subtotal'];

        foreach ($candidates as $candidate) {
            if (DB::getSchemaBuilder()->hasColumn('invoices', $candidate)) {
                return $candidate;
            }
        }
        // Fallback or throw an error if no amount column is found.
        return 'total_amount';
    }

    /**
     * Generates a snapshot of due invoices, replacing dueSnapshot().
     */
    public static function dueSnapshot(\DateTimeInterface $now, bool $overdueOnly = false): array
    {
        $statusValues = $overdueOnly ? ['Overdue'] : ['Sent', 'Overdue'];
        $amountCol    = (new self)->firstExistingAmountColumn();
        $date         = \Carbon\Carbon::parse($now)->endOfDay()->toDateTimeString();

        $paymentsSub = DB::table('invoice_payments')
            ->selectRaw('invoice_id, COALESCE(SUM(amount),0) AS paid')
            ->groupBy('invoice_id');

        $result = static::query()                          // keeps HasTenantScope
            ->from('invoices')                             // NO alias
            ->leftJoinSub($paymentsSub, 'p', 'p.invoice_id', '=', 'invoices.id')
            ->whereIn('invoices.status', $statusValues)
            ->where('invoices.due_date', '<=', $date)
            ->selectRaw("
            COUNT(*) AS cnt,
            COALESCE(SUM(GREATEST(invoices.`{$amountCol}` - COALESCE(p.paid, 0), 0)), 0) AS total
        ")
            ->first();

        return [
            'count'     => (int) ($result->cnt ?? 0),
            'total'     => (float) ($result->total ?? 0),
            'hasAmount' => $amountCol !== 'total_amount',
        ];
    }
    public function getAmountResolvedAttribute()
    {
        if ($this->total_amount !== null) {
            return $this->total_amount;
        }

        // fallback to calculated total if items exist
        try {
            return $this->getTotalAmount();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculates payment sums between dates, replacing paymentsSumBetween().
     */
    public static function paymentsSumBetween(\DateTimeInterface $from, \DateTimeInterface $to): float
    {
        $sum = InvoicePayment::query()                     // (make sure this model also uses HasTenantScope)
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id') // NO alias
            ->whereBetween('invoice_payments.payment_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(invoice_payments.amount), 0) AS s')
            ->value('s');

        return (float) $sum;
    }


    /**
     * Calculates the aging buckets, replacing agingBuckets().
     */
    public static function agingBuckets(): array
    {
        $amountCol = (new self)->firstExistingAmountColumn();

        // payments subquery: total paid per invoice
        $paymentsSub = DB::table('invoice_payments')
            ->selectRaw('invoice_id, COALESCE(SUM(amount),0) AS paid')
            ->groupBy('invoice_id');

        // inner subquery: one row per invoice with its bucket + outstanding balance
        $subQuery = static::query() // keeps HasTenantScope applied
            ->from('invoices')      // no alias, so scope uses invoices.tenant_id
            ->leftJoinSub($paymentsSub, 'p', 'p.invoice_id', '=', 'invoices.id')
            ->whereIn('invoices.status', ['Sent', 'Overdue'])
            ->where('invoices.due_date', '<', now())
            ->selectRaw("
            CASE
                WHEN DATEDIFF(CURDATE(), invoices.due_date) <= 30 THEN '0-30'
                WHEN DATEDIFF(CURDATE(), invoices.due_date) <= 60 THEN '31-60'
                WHEN DATEDIFF(CURDATE(), invoices.due_date) <= 90 THEN '61-90'
                ELSE '90+'
            END AS bucket
        ")
            ->selectRaw("
            GREATEST(invoices.`{$amountCol}` - COALESCE(p.paid, 0), 0) AS outstanding
        ");

        // outer query: group the subquery
        $results = DB::query()
            ->fromSub($subQuery, 'x')                 // <-- carries all bindings automatically
            ->selectRaw('bucket, SUM(outstanding) AS v')
            ->groupBy('bucket')
            ->pluck('v', 'bucket')
            ->all();

        // ensure all buckets exist
        $buckets = array_merge(['0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0], $results);

        return [
            'buckets'   => $buckets,
            'hasAmount' => $amountCol !== 'total_amount',
        ];
    }

    /**
     * Forecast total invoice amount due between two dates for the given tenant.
     */
    public static function forecastDueBetween(DateTimeInterface $from, DateTimeInterface $to, int $tenantId): float
    {
        return (float) static::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$from, $to])
            ->sum('total_amount');
    }



    /**
     * If subtotal is missing, fall back to total - tax_total.
     */
    public function getSubtotalResolvedAttribute(): float
    {
        if ($this->subtotal !== null) {
            return (float) $this->subtotal;
        }

        return (float) $this->total_amount - (float) $this->tax_total;
    }

    /**
     * Tax breakdown always returns an array (never null) for safe foreach.
     */
    public function getTaxBreakdownResolvedAttribute(): array
    {
        if (is_array($this->tax_breakdown)) {
            return $this->tax_breakdown;
        }

        return [];
    }
}

// NOTE: For this model to function, you must ensure the following models
// (Client, Project, InvoiceItem, InvoicePayment) also exist and use the HasTenantScope trait.
