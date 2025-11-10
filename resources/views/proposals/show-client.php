<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Proposal: <?= htmlspecialchars($proposal['title']) ?></title>
  <link rel="stylesheet" href="/assets/css/public.css">
</head>

<body>
  <div class="proposal-container">
    <h1><?= htmlspecialchars($proposal['title']) ?></h1>
    <hr>
    <div class="proposal-content">
      <?= $proposal['content'] ?>
    </div>

    <div class="proposal-actions">
      <?php if ($proposal['status'] === 'sent'): ?>
        <form action="/proposals/accept/<?= htmlspecialchars($proposal['unique_share_token']) ?>" method="POST" style="display:inline-block;">
          <button type="submit" class="btn btn-success">Accept</button>
        </form>
        <form action="/proposals/reject/<?= htmlspecialchars($proposal['unique_share_token']) ?>" method="POST" style="display:inline-block;">
          <button type="submit" class="btn btn-danger">Decline</button>
        </form>
      <?php else: ?>
        <p>Status: <?= htmlspecialchars(ucfirst($proposal['status'])) ?></p>
      <?php endif; ?>
    </div>
  </div>
</body>

</html>