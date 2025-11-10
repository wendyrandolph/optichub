<?php
require_once __DIR__ . '/../bootstrap.php'; // sets up $pdo

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ensure columns exist (idempotent): sent flags to avoid duplicate emails
$pdo->exec("ALTER TABLE subscriptions
  ADD COLUMN IF NOT EXISTS trial_reminder_3d_sent_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS trial_reminder_1d_sent_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS trial_expired_sent_at     DATETIME NULL");

$now = gmdate('Y-m-d H:i:s');

// 3 days before
$sql3 = "SELECT s.tenant_id, s.current_period_end, u.email
         FROM subscriptions s
         JOIN users u ON u.tenant_id = s.tenant_id AND u.role='admin'
         WHERE s.status='trialing'
           AND s.trial_reminder_3d_sent_at IS NULL
           AND TIMESTAMPDIFF(DAY, UTC_TIMESTAMP(), s.current_period_end) = 3
         LIMIT 200";
foreach ($pdo->query($sql3) as $row) {
  @mail(
    $row['email'],
    'Your Optic Hub trial ends soon',
    "Heads up — your trial ends on {$row['current_period_end']} (UTC).\n\n" .
      "Upgrade anytime from Settings → Billing.\n"
  );
  $upd = $pdo->prepare("UPDATE subscriptions SET trial_reminder_3d_sent_at=:now WHERE tenant_id=:t");
  $upd->execute([':now' => $now, ':t' => $row['tenant_id']]);
}

// 1 day before
$sql1 = "SELECT s.tenant_id, s.current_period_end, u.email
         FROM subscriptions s
         JOIN users u ON u.tenant_id = s.tenant_id AND u.role='admin'
         WHERE s.status='trialing'
           AND s.trial_reminder_1d_sent_at IS NULL
           AND TIMESTAMPDIFF(DAY, UTC_TIMESTAMP(), s.current_period_end) = 1
         LIMIT 200";
foreach ($pdo->query($sql1) as $row) {
  @mail(
    $row['email'],
    'Last day of your Optic Hub trial',
    "Your trial ends on {$row['current_period_end']} (UTC).\n\n" .
      "Keep your workspace active by upgrading in Settings → Billing.\n"
  );
  $upd = $pdo->prepare("UPDATE subscriptions SET trial_reminder_1d_sent_at=:now WHERE tenant_id=:t");
  $upd->execute([':now' => $now, ':t' => $row['tenant_id']]);
}

// Expired
$sqlE = "SELECT s.tenant_id, s.current_period_end, u.email
         FROM subscriptions s
         JOIN users u ON u.tenant_id = s.tenant_id AND u.role='admin'
         WHERE s.status='trialing'
           AND s.trial_expired_sent_at IS NULL
           AND UTC_TIMESTAMP() > s.current_period_end
         LIMIT 200";
foreach ($pdo->query($sqlE) as $row) {
  @mail(
    $row['email'],
    'Your Optic Hub trial has ended',
    "Your trial ended on {$row['current_period_end']} (UTC).\n\n" .
      "Upgrade anytime to keep access.\n"
  );
  $upd = $pdo->prepare("UPDATE subscriptions SET trial_expired_sent_at=:now WHERE tenant_id=:t");
  $upd->execute([':now' => $now, ':t' => $row['tenant_id']]);
}
