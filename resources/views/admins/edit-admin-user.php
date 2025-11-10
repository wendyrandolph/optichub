<h1>Edit Admin Account</h1>
<form method="POST" action="/admin/users/store">
  <input type="hidden" value="<?php echo htmlspecialchars($admin['username']); ?>">
  <label>UserName:</label>
  <input type="text" name="name" value="<?php echo htmlspecialchars($admin['username']); ?>" required>

  <label>Email:</label>
  <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>

  <input type="hidden" name="role" value="admin">

  <button type="submit" class="btn btn-submit">Create Admin</button>
</form>