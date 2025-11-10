<?php

namespace App\Models;

use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent Model for the 'invoice_items' table.
 * Invoice items do not require the HasTenantScope directly, as their security 
 * is guaranteed by the tenant_id on the parent Invoice record.
 */
class InvoiceItem extends Model
{
  use HasFactory;
  // NOTE: HasTenantScope is NOT needed here. Security is delegated to the parent Invoice.

  protected $table = 'invoice_items';

  // Disable timestamps as invoice items are often static and updated/deleted via the parent.
  public $timestamps = false;

  protected $fillable = [
    'invoice_id',
    'description',
    'quantity',
    'unit_price',
  ];

  /**
   * The attributes that should be cast to native types.
   */
  protected $casts = [
    'quantity' => 'decimal:2',
    'unit_price' => 'decimal:4',
  ];

  // --- Relationships ---

  /**
   * An invoice item belongs to an Invoice.
   */
  public function invoice(): BelongsTo
  {
    return $this->belongsTo(Invoice::class);
  }

  // --- Core CRUD & Retrieval Refactors ---

  /**
   * Retrieves all items for a given invoice ID.
   * This relies on the security of the parent Invoice query.
   * Replaces the procedural getByInvoiceId().
   *
   * @param int $invoiceId The ID of the invoice.
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public static function getItemsByInvoiceId(int $invoiceId)
  {
    // We use the Invoice relationship to ensure the item lookup respects tenancy.
    // If Invoice::find($invoiceId) returns null (due to HasTenantScope),
    // the items() relationship will never be queried.
    $invoice = Invoice::find($invoiceId);

    if (!$invoice) {
      // Invoice is not found or not accessible to the current tenant.
      return collect([]); // Return an empty collection
    }

    // Use the relationship to fetch the items securely.
    return $invoice->items;
  }

  /**
   * Replaces the procedural create() method.
   *
   * @param array $data Data for the invoice item including 'invoice_id'.
   * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the associated invoice is not accessible.
   */
  public static function createItem(array $data): self
  {
    // 1. Security Check: Find the parent Invoice. 
    // findOrFail() ensures the invoice exists AND is visible to the current tenant
    // (due to HasTenantScope on the Invoice model).
    $invoice = Invoice::findOrFail($data['invoice_id']);

    // 2. Creation: Eloquent handles the INSERT securely.
    $item = new self($data);
    $invoice->items()->save($item);

    return $item;
  }

  /**
   * Deletes invoice items associated with a given invoice ID.
   * Ensures the associated invoice belongs to the current user's organization.
   * Replaces deleteByInvoiceId().
   */
  public static function deleteItemsByInvoiceId(int $invoiceId): bool
  {
    // 1. Security Check: Find the parent Invoice (tenant-scoped).
    $invoice = Invoice::find($invoiceId);

    if (!$invoice) {
      // Cannot delete items if the invoice is not found or not accessible.
      return false;
    }

    // 2. Deletion: Use the relationship to perform the bulk delete.
    // This is safe because we verified the parent invoice is tenant-scoped.
    return $invoice->items()->delete();
  }
}
