<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php if (!empty($_SESSION['success_message'])): ?>
  <div class="alert alert-success">
    <?php echo htmlspecialchars($_SESSION['success_message']);
    unset($_SESSION['success_message']); ?>
  </div>
<?php endif; ?>

<?php if (!empty($_SESSION['error_message'])): ?>
  <div class="alert alert-error">
    <?php echo htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']); ?>
  </div>
<?php endif; ?>

<h3>Add Activity</h3>
<form method="POST" action="/leads/activity/add/<?php echo htmlspecialchars($lead['id']); ?>">
  <input type="hidden" name="csrf_token" value="<?php echo CSRF::generate(); ?>">
  <label for="activity_type">Activity Type:</label>
  <select name="activity_type" required>
    <option value="call">Call</option>
    <option value="email">Email</option>
    <option value="meeting">Meeting</option>
    <option value="note">Note</option>
  </select>
  <br><br>
  <label for="description">Description:</label>
  <textarea name="description" rows="4" required></textarea>
  <br><br>
  <button type="submit">Add Activity</button>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>