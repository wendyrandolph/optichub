<a href="/admins" class="btn btn-back"> <i class="fa fa-arrow-left" aria-hidden="true"></i>
  Back to List of Admins</a>
<div class="form-wrapper">
  <form method="POST" action="/admins/update/<?= $admin['id'] ?>" class="form-container">
    <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
    <h2 class="form-heading"> Edit Admin </h2>
    <div class="form-group">
      <label>First Name</label>
      <input type="text" name="firstName" value="<?= htmlspecialchars($admin['firstName']) ?>" required>
    </div>
    <div class="form-group">
      <label>Last Name</label>
      <input type="text" name="firstName" value="<?= htmlspecialchars($admin['lastName']) ?>" required>
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>">
    </div>
    <div class="form-group">
      <label>Phone</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($admin['phone']) ?>">
    </div>
    <div class="form-group">
      <label>Title</label>
      <input type="text" name="title" value="<?= htmlspecialchars($admin['title']) ?>">
    </div>
    <div class="form-group">
      <label>Notes</label>
      <?php if (!empty($admin['notes'])): ?>
        <textarea name="notes"><?= htmlspecialchars($admin['notes']) ?></textarea>
      <?php else: ?>
        <textarea name="notes"></textarea>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <button type="submit" class="btn btn-save">Update</button>
    </div>
  </form>
</div>
</div>