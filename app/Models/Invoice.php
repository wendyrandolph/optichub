<?php

namespace App\Models;

use App\Traits\HasTenantScope;
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
        'client_id',
        'project_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'status',
        'notes',
        'stripe_link',
        'tenant_id' // Will be automatically set by the HasTenantScope trait
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    // --- Relationships ---

    /**
     * An invoice belongs to a Client.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * An invoice belongs to an optional Project.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * An invoice has many Invoice Items (for calculating total).
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
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
            ->join('clients', 'invoices.client_id', '=', 'clients.id')
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
            ->join('clients', 'invoices.client_id', '=', 'clients.id')
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
        return static::where('client_id', $clientId)
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
}

// NOTE: For this model to function, you must ensure the following models
// (Client, Project, InvoiceItem, InvoicePayment) also exist and use the HasTenantScope trait.
