<?php require_once __DIR__ . '/../layouts/main.php'; ?>
<div class="page-container">
  <div class="content-wrap">
    <h1>Thank You!</h1>
    <p>Your payment for Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?> was received successfully.</p>

    <p><strong>Total Paid:</strong> $<?= number_format($total, 2) ?></p>
    <p>Status: <span style="color:green;font-weight:bold;">Paid</span></p>

    <p>Need anything else? Feel free to <a href="/contact">contact us</a>.</p>
  </div>
</div>