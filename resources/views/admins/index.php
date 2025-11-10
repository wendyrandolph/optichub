<!-- Quick Actions -->
<div class="dash-q-a" style="width:50%; margin-left: 0; border-bottom: none; justify-content:left;">
  <a href="/admins/create" class="underline-effect">Create Admin</a>
</div>
<div class="hero-section">
  <h1>List of Users</h1>
  <div class="description">
    <p> Some sample description here? </p>
  </div>
</div>
<div style="width: 100%; max-width: 1000px; margin: 0 auto;">
  <table class=" table-admins">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Title</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($admins as $admin): ?>
        <tr>
          <td><?= htmlspecialchars($admin['firstName'] . " " .  $admin['lastName']) ?></td>
          <td><?= htmlspecialchars($admin['email']) ?></td>
          <td><?= formatPhone($admin['phone']) ?></td>

          <td><?= htmlspecialchars($admin['title']) ?></td>
          <td>
            <a href="/admins/edit/<?= $admin['id'] ?>" class="tooltip" title="Edit Admin"><i class="fa-solid fa-pencil"></i><span class="tooltiptext">Edit Admin</span></a>
            <?php if (!($admin['user_id'] ?? false)) : ?>
              <a href="/admins/create-admin-user?id=<?= $admin['id'] ?>" title="Create Admin Login" class="tooltip">
                <i class="fa-solid fa-user-plus"></i><span class="tooltiptext">Create Admin Login </span>
              </a>
            <?php else : ?>
              <span class="tooltip" title="Login Created"><i class="fa-solid fa-check-double"></i> <span class="tooltiptext"> Login Created</span></span>
            <?php endif; ?>

            <form method="POST" action="/admins/delete/<?= $admin['id'] ?>" onsubmit="return confirm('Delete this admin?');" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
              <button type="submit" class="tooltip" title="Delete Admin"><i class="fa-solid fa-trash"></i><span class="tooltiptext">Delete</span></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>