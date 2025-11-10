<div class="centered">
  <h1>Your Invoices</h1>
</div>
<div class="invoice-card">
  <?php if (empty($invoices)): ?>
    <div class="card">
      <p>You currently have no invoices on file.</p>
    </div>
  <?php else: ?>


    <?php foreach ($invoices as $invoice): ?>

      <div class="card">
        <p class="b-bot"><strong>Invoice #<?= $invoice['invoice_number'] ?></strong>
        <div class="invoice-row">
          <div class="invoice-info">

            <span class="status-pill <?= $invoice['status'] === 'Paid' ? 'paid' : 'unpaid' ?>">
              <?= htmlspecialchars($invoice['status']) ?>
            </span>
            </p>
            <p><strong>Issued:</strong> <?= $invoice['issue_date'] ?></p>
            <p><strong>Total:</strong> $<?= number_format($invoice['total'] ?? 0, 2) ?></p>
            <?php if (!empty($invoice['paid_at'])): ?>
              <p><strong>Paid on:</strong> <?= date('F j, Y \a\t g:ia', strtotime($invoice['paid_at'])) ?></p>
            <?php endif; ?>
          </div>

          <div class="invoice-actions">
            <a href="/my-invoice/<?= $invoice['id'] ?>" class="btn btn-outline-2 btn-underline" aria-label="View Invoice <?= $invoice['invoice_number'] ?>">View Invoice</a>
            <a href=" /my-invoice/pdf/<?= $invoice['id'] ?>" class="btn btn-outline btn-underline" target="_blank" aria-label="Download Invoice PDF <?= $invoice['invoice_number'] ?>">Download PDF</a>
          </div>
        </div>
      </div>

    <?php endforeach; ?>
</div>
<?php endif; ?>