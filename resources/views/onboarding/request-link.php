<!-- app/views/onboarding/request_link.php -->
<h1>Send a new setup link</h1>
<?php if (!empty($error)): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" action="/onboarding/request-link">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::generate()) ?>">
  <label>Email</label>
  <input type="email" name="email" required>
  <button class="btn btn--primary">Send link</button>
</form>