<div class="view-invoice">
  <?php if (isset($_GET['sent'])): ?>
    <div style="padding: 1rem; background: <?= $_GET['sent'] ? '#e0ffe0' : '#ffe0e0' ?>; border: 1px solid #ccc; margin-bottom: 1rem;">
      <?= $_GET['sent'] ? '✅ Invoice sent successfully!' : '❌ Failed to send invoice.' ?>
    </div>
  <?php endif; ?>


  <div class="invoice-header">
    <img src="https://causeywebsolutions.com/wp-content/uploads/Causey-Logo-1.png" class="logo" alt="Causey Web Solutions Logo">
    <h1>Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></h1>

    <p><strong>Client:</strong> <?= htmlspecialchars($invoice['client_name']) ?></p>
    <p><strong>Issued:</strong> <?= htmlspecialchars($invoice['issue_date']) ?> |
      <strong>Due Date:</strong> <?= htmlspecialchars($invoice['due_date']) ?>
    </p>
    <p><strong>Status:</strong> <?= $invoice['status'] ?>
      <?= !empty($invoice['paid_at']) ? '(Paid on ' . date('F j, Y', strtotime($invoice['paid_at'])) . ')' : '' ?>
    </p>
    <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
  </div>
  <h3>Line Items</h3>
  <table class="admin-table">
    <thead>
      <tr>
        <th>Description</th>
        <th>Qty</th>
        <th>Unit Price</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php $total = 0; ?>
      <?php foreach ($items as $item): ?>
        <?php
        $qty = $item['quantity'] ?? 0;
        $unit = $item['unit_price'] ?? 0;
        $lineTotal = $qty * $unit;
        ?>

        <tr>
          <td><?= htmlspecialchars($item['description']) ?></td>
          <td><?= $item['quantity'] ?></td>
          <td>$<?= number_format($item['unit_price'], 2) ?></td>
          <td>$<?= number_format($lineTotal, 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3" style="text-align:right;"><strong>Total:</strong></td>
        <td><strong>$<?= number_format($total, 2) ?></strong></td>
      </tr>
    </tfoot>
  </table>
  <?php if ($invoice['status'] === 'Paid'): ?>
    <p class="alert"><strong>This invoice is already paid.</strong></p>
  <?php else: ?>
    <a href="<?= htmlspecialchars($invoice['stripe_link']) ?>" class="button" target="_blank">
      Pay Now
    </a>
  <?php endif; ?>


  <div class="view-actions">
    <a href="/my-invoices" class="btn btn-back">← Back to Invoice List</a>
    <a href="/my-invoice/pdf/<?= $invoice['id'] ?>" class="btn btn-outline btn-underline" target="_blank">Download PDF</a>

  </div>
</div>