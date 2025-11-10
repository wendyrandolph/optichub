    <?php if (isset($_GET['sent'])): ?>
      <div style="padding: 1rem; background: <?= $_GET['sent'] ? '#e0ffe0' : '#ffe0e0' ?>; border: 1px solid #ccc; margin-bottom: 1rem;">
        <?= $_GET['sent'] ? '✅ Invoice sent successfully!' : '❌ Failed to send invoice.' ?>
      </div>
    <?php endif; ?>
    <a href="/invoices/pdf/<?= $invoice['id'] ?>" class="button" target="_blank">Download PDF</a>

    <h1>Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></h1>

    <p><strong>Status:</strong> <?= htmlspecialchars($invoice['status']) ?></p>
    <p><strong>Issue Date:</strong> <?= htmlspecialchars($invoice['issue_date']) ?></p>
    <p><strong>Due Date:</strong> <?= htmlspecialchars($invoice['due_date']) ?></p>
    <p><strong>Client:</strong> <?= htmlspecialchars($invoice['client_name']) ?></p>
    <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>

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
          <?php $lineTotal = $item['quantity'] * $item['unit_price']; ?>
          <?php $total += $lineTotal; ?>
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
    <?php if (!empty($invoice['stripe_link'])): ?>
      <div style="margin-top: 1.5rem;">
        <a href="<?= htmlspecialchars($invoice['stripe_link']) ?>" target="_blank" class="button" style="background:#00bb88; color:white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none;">
          💳 Pay Now
        </a>
      </div>
    <?php endif; ?>
    <form method="POST" action="/invoices/send/<?= $invoice['id'] ?>" style="margin-top: 2rem;">
      <button type="submit" class="button" style="background:#1F3C66;color:white;padding:0.5rem 1rem;border-radius:6px;">
        📧 Send Invoice to Client
      </button>
    </form>

    <div style="margin-top: 1.5rem;">
      <a href="/invoices" class="button">← Back to Invoice List</a>
    </div>
    </div>
    </div>