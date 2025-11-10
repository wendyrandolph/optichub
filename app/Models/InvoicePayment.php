<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent Model for the 'invoice_payments' table.
 * Like InvoiceItem, security is delegated to the parent Invoice via relationship checks.
 */
class InvoicePayment extends Model
{
  use HasFactory;
  // NOTE: HasTenantScope is NOT needed here. Security is delegated to the parent Invoice.

  protected $table = 'invoice_payments';

  // Assuming timestamps exist on this table for payment records, if not, set to false.
  public $timestamps = true;
  const UPDATED_AT = null; // Payments are usually only created, not updated.

  protected $fillable = [
    'invoice_id',
    'amount',
    'payment_date',
    'payment_method',
    'notes',
  ];

  /**
   * The attributes that should be cast to native types.
   */
  protected $casts = [
    'amount' => 'decimal:2',
    'payment_date' => 'datetime',
  ];

  // --- Relationships ---

  /**
   * An invoice payment belongs to an Invoice.
   */
  public function invoice(): BelongsTo
  {
    return $this->belongsTo(Invoice::class);
  }

  // --- Core CRUD & Retrieval Refactors ---

  /**
   * Retrieves all payments for a given invoice ID.
   * Replaces the procedural getByInvoiceId().
   *
   * @param int $invoiceId The ID of the invoice.
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public static function getPaymentsByInvoiceId(int $invoiceId)
  {
    // 1. Security Check: Find the parent Invoice (tenant-scoped).
    $invoice = Invoice::find($invoiceId);

    if (!$invoice) {
      // Invoice is not found or not accessible to the current tenant.
      return collect([]); // Return an empty collection
    }

    // 2. Retrieval: Use the relationship to fetch the payments securely.
    return $invoice->payments;
  }

  /**
   * Creates a new invoice payment.
   * Replaces the procedural create() method.
   *
   * @param array $data Data for the invoice payment including 'invoice_id'.
   * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the associated invoice is not accessible.
   */
  public static function createPayment(array $data): self
  {
    // 1. Security Check: Find the parent Invoice. 
    // findOrFail() ensures the invoice exists AND is visible to the current tenant.
    $invoice = Invoice::findOrFail($data['invoice_id']);

    // 2. Creation: Eloquent handles the INSERT securely.
    $payment = new self($data);
    $invoice->payments()->save($payment);

    return $payment;
  }

  /**
   * Deletes an invoice payment by its ID.
   * Replaces the procedural delete() method, ensuring tenancy via the parent invoice.
   *
   * @param int $id The ID of the invoice payment to delete.
   * @return bool True on successful deletion, false otherwise.
   * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the associated invoice is not accessible.
   */
  public static function deletePayment(int $id): bool
  {
    // 1. Find the payment and load its invoice relationship
    $payment = static::with('invoice')->find($id);

    if (!$payment) {
      return false; // Payment not found
    }

    // 2. Security Check: Check if the loaded parent invoice is null.
    // If $payment->invoice is null, it means:
    // a) The payment exists but its invoice was deleted, OR
    // b) The invoice exists but the HasTenantScope on the Invoice model filtered it out.
    // In either case, the user cannot access the parent invoice, so we deny deletion.
    if (!$payment->invoice) {
      // The original procedural code threw an exception here, so we'll mimic that if preferred,
      // otherwise, return false for a cleaner flow. We'll throw the exception for security consistency.
      throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
        "Cannot delete payment: Associated invoice not found or not accessible."
      );
    }

    // 3. Deletion. Since we validated access to the parent Invoice, deletion is safe.
    return $payment->delete();
  }
}
