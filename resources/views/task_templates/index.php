<?php
// File: app/views/task_templates/index.php

require_once __DIR__ . '/../layouts/header.php'; ?>

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

<h2>Manage Task Templates</h2>

<form method="POST" action="/task-templates/create">
  <input type="hidden" name="csrf_token" value="<?php echo CSRF::generate(); ?>">
  <label>Template Title:<br><input type="text" name="title" required></label><br><br>
  <label>Description:<br><textarea name="description"></textarea></label><br><br>
  <button type="submit">Add Template</button>
</form>

<hr>

<h3>Existing Task Templates</h3>

<table border="1">
  <thead>
    <tr>
      <th>ID</th>
      <th>Title</th>
      <th>Description</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($templates as $template): ?>
      <tr>
        <td><?php echo htmlspecialchars($template['id']); ?></td>
        <td><?php echo htmlspecialchars($template['title']); ?></td>
        <td><?php echo htmlspecialchars($template['description']); ?></td>
        <td>
          <form method="POST" action="/task-templates/delete/<?php echo $template['id']; ?>" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?php echo CSRF::generate(); ?>">
            <button type="submit" onclick="return confirm('Are you sure you want to delete this template?');">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>