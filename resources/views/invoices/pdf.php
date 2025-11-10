<!DOCTYPE html>
<html>

<head>
  <style>
    body {
      font-family: 'Arial', sans-serif;
      color: #333333;
      padding: 20px;
    }

    h1 {
      color: #1F3C66;
      font-size: 24px;
      margin-bottom: 5px;
    }

    h3 {
      color: #1F3C66;
      margin-top: 30px;
      font-size: 18px;
    }

    p {
      margin: 4px 0;
      font-size: 14px;
    }

    .header {
      padding-bottom: 10px;
      border-bottom: 3px solid #EA7D51;
      margin-bottom: 20px;
    }

    .section {
      margin-bottom: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th {
      background-color: #1F3C66;
      color: #fff;
      padding: 8px;
      font-size: 13px;
    }

    td {
      border: 1px solid #ccc;
      padding: 8px;
      font-size: 13px;
    }

    .total {
      text-align: right;
      font-weight: bold;
      font-size: 15px;
      margin-top: 10px;
    }

    .logo {
      float: right;
      max-width: 150px;
      margin-bottom: 10px;
    }
  </style>

</head>

<body>
  <div class="header">
    <img src="https://causeywebsolutions.com/wp-content/uploads/Causey-Logo-1.png" class="logo" alt="Causey Web Solutions Logo">
    <h1>Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></h1>
    <p><strong>Client:</strong> <?= htmlspecialchars($_SESSION['name']) ?></p>
    <p><strong>Issued:</strong> <?= $invoice['issue_date'] ?> |
      <strong>Due:</strong> <?= $invoice['due_date'] ?>
    </p>
    <p><strong>Status:</strong> <?= $invoice['status'] ?>
      <?= !empty($invoice['paid_at']) ? '(Paid on ' . date('F j, Y', strtotime($invoice['paid_at'])) . ')' : '' ?>
    </p>


  </div>

  <h3>Line Items</h3>
  <table>
    <thead>
      <tr>
        <th>Description</th>
        <th>Qty</th>
        <th>Unit Price</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <?php
        $qty = $item['quantity'] ?? 0;
        $unit = $item['unit_price'] ?? 0;
        $lineTotal = $qty * $unit;
        ?>
        <tr>
          <td><?= htmlspecialchars($item['description']) ?></td>
          <td><?= $qty ?></td>
          <td>$<?= number_format($unit, 2) ?></td>
          <td>$<?= number_format($lineTotal, 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <?php
    $grandTotal = 0;
    foreach ($items as $item) {
      $qty = $item['quantity'] ?? 0;
      $unit = $item['unit_price'] ?? 0;
      $grandTotal += $qty * $unit;
    }
    ?>

  </table>

  <div class="total">
    Total: $<?= number_format($grandTotal, 2) ?>
  </div>
  <p style="margin-top: 10px; font-size: 13px; color: #333;">
    This document serves as your receipt.
  </p>

</body>

</html>