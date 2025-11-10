<?php require_once __DIR__ . '/../layouts/main.php'; ?>

<div class="page-container">
  <div class="form-wrapper">
    <form method="POST" action="/account/reset-password" class="form-container">
      <h2 class="form-heading">Reset Your Password</h2>

      <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">

      <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
          <?= $_SESSION['error_message'];
          unset($_SESSION['error_message']); ?>
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label>New Password:</label>
        <input type="password" name="new_password" required>
      </div>

      <div class="form-group">
        <label>Confirm New Password:</label>
        <input type="password" name="confirm_password" required>
      </div>

      <button class="btn btn-add" type="submit">Update Password</button>
    </form>
  </div>
</div>