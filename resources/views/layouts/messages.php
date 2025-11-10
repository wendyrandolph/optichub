<?php if (!empty($_SESSION['success_message'])): ?>
  <div class="flash-message success">
    <?= htmlspecialchars($_SESSION['success_message']);
    unset($_SESSION['success_message']); ?>
  </div>
<?php endif; ?>

<?php if (!empty($_SESSION['error_message'])): ?>
  <div class="flash-message error">
    <?= htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']); ?>
  </div>
<?php endif; ?>