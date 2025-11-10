<?php
$apiKeyContext = $apiKeyContext ?? 'onboarding';
$newPlainKey = $newPlainKey ?? null;
$error = $error ?? null;
$keys = $keys ?? [];
$generateAction = $generateAction ?? ($apiKeyContext === 'onboarding' ? '/onboarding/api-key' : '/settings/api/generate');
$createAnotherAction = $createAnotherAction ?? $generateAction;
$showEndpoint = $showEndpoint ?? '/settings/api/show';
$generateButtonLabel = $generateButtonLabel ?? 'Generate API Key';
$createAnotherLabel = $createAnotherLabel ?? 'Create another key';
$csrfToken = function_exists('CSRF::token') ? CSRF::token() : ($_SESSION['csrf_token'] ?? null);
$legacyKeyMasked = $legacyKeyMasked ?? null;
$legacyShowEndpoint = $legacyShowEndpoint ?? ($showEndpoint ?? null);
$legacyRegenerateAction = $legacyRegenerateAction ?? '/settings/api/generate';

if ($legacyKeyMasked === null && $apiKeyContext === 'settings') {
  $connection = $legacyPdo ?? $pdo ?? $db ?? ($GLOBALS['pdo'] ?? ($GLOBALS['db'] ?? null));
  $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
  if ($connection instanceof PDO && $tenantId > 0) {
    try {
      $stmt = $connection->prepare("SELECT api_key FROM organizations WHERE id = ? LIMIT 1");
      $stmt->execute([$tenantId]);
      $legacyKeyRaw = $stmt->fetchColumn();
      if (!empty($legacyKeyRaw)) {
        $length = strlen($legacyKeyRaw);
        $legacyKeyMasked = substr($legacyKeyRaw, 0, 4) . str_repeat('•', max(0, $length - 8)) . substr($legacyKeyRaw, -4);
      }
    } catch (Throwable $e) {
      // legacy column absent; ignore gracefully
    }
  }
}
?>

<?php if (!empty($newPlainKey)): ?>
  <div class="card card--glass">
    <p><strong>Copy this key now</strong> — you won’t see it again:</p>
    <pre><?= htmlspecialchars($newPlainKey) ?></pre>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if (empty($keys)): ?>
  <form method="post" action="<?= htmlspecialchars($generateAction) ?>">
    <?php if (!empty($csrfToken)): ?>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <?php endif; ?>
    <button class="btn btn--primary" type="submit"><?= htmlspecialchars($generateButtonLabel) ?></button>
  </form>
<?php else: ?>
  <h3 class="h4" style="margin-top:24px">Active Keys</h3>
  <ul class="clean">
    <?php foreach ($keys as $k): ?>
      <li>
        <strong><?= htmlspecialchars($k['name'] ?? 'Untitled key') ?></strong>
        <?php if (!empty($k['scopes'])): ?>
          <small>Scopes: <?= htmlspecialchars($k['scopes']) ?></small>
        <?php endif; ?>
        <?php if (!empty($k['created_at'])): ?>
          <small>Created <?= htmlspecialchars(date('M j, Y', strtotime($k['created_at']))) ?></small>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <form method="post" action="<?= htmlspecialchars($createAnotherAction) ?>" style="margin-top:12px">
    <?php if (!empty($csrfToken)): ?>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <?php endif; ?>
    <button class="btn btn--primary" type="submit"><?= htmlspecialchars($createAnotherLabel) ?></button>
  </form>
<?php endif; ?>

<?php if ($legacyKeyMasked): ?>
  <div class="card" style="padding:12px; margin-top:24px;">
    <p class="copy">Current key: <code id="apiKey" style="user-select:all;"><?= htmlspecialchars($legacyKeyMasked) ?></code></p>
    <div class="btn-row">
      <button class="btn btn--ghost" type="button" onclick="copyExistingKey()">Copy full key</button>
      <form method="post" action="<?= htmlspecialchars($legacyRegenerateAction) ?>" onsubmit="return confirm('Regenerate key? Existing integrations will need updating.');">
        <?php if (!empty($csrfToken)): ?>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <?php endif; ?>
        <button class="btn btn--primary" type="submit">Regenerate</button>
      </form>
    </div>
  </div>
  <script>
    function copyExistingKey() {
      fetch('<?= htmlspecialchars($legacyShowEndpoint) ?>', {
          method: 'POST',
          headers: {
            'X-CSRF-Token': '<?= htmlspecialchars($csrfToken ?? '') ?>'
          }
        })
        .then(r => r.ok ? r.text() : Promise.reject())
        .then(full => navigator.clipboard.writeText(full));
    }
  </script>
<?php endif; ?>
