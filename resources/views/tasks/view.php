<div class="container">

  <a href="/tasks" class="btn btn-back">Back to All Tasks</a>

  <div class="task-view card">
    <h1 class="page-title"><?= htmlspecialchars($task['title']) ?></h1>
    <div class="task-meta-box">
      <p><strong>Status:</strong> <?= ucfirst($task['status']) ?></p>
      <p><strong>Due Date:</strong> <?= date('F j, Y', strtotime($task['due_date'])) ?></p>
      <p><strong>Assigned To:</strong> <?= htmlspecialchars($task['client_name']) ?> (<?= $task['assign_type'] ?>)</p>
      <p><strong>Project:</strong> <?= htmlspecialchars($task['project_name'] ?? '') ?></p>
      <p><strong>Phase:</strong> <?= htmlspecialchars($task['phase_name'] ?? '') ?></p>
    </div>

    <div class="task-description-box">
      <h3>Description</h3>
      <p><?= nl2br(htmlspecialchars($task['description'])) ?></p>
    </div>

    <?php if (!empty($task['upload_path'])): ?>
      <div class="task-upload-box">
        <h3>Attached File</h3>
        <a href="<?= $task['upload_path'] ?>" target="_blank">View Upload</a>
      </div>
    <?php endif; ?>

    <?php if (!empty($task['form_url'])): ?>
      <div class="task-form-link">
        <h3>External Form</h3>
        <a href="<?= $task['form_url'] ?>" target="_blank"><?= $task['form_url'] ?></a>
      </div>
    <?php endif; ?>

    <?php if (!empty($task['feedback_image'])): ?>
      <div class="task-feedback-image">
        <h3>Feedback Image</h3>
        <img src="<?= $task['feedback_image'] ?>" alt="Feedback" style="max-width: 100%; height: auto;">
      </div>
    <?php endif; ?>

    <?php
    // Assuming $currentUserId is available and holds the logged-in admin's ID
    // You would retrieve $currentUserId from your session or authentication system
    // Example: $currentUserId = $_SESSION['user_id'] ?? null;
    $isTaskAssignedToCurrentUser = ($task['assign_type'] === 'admin' && $task['assigned_to_id'] === $currentUserId);
    ?>

    <div class="task-buttons">
      <?php if ($task['status'] !== 'completed' && $isTaskAssignedToCurrentUser): ?>
        <form method="POST" action="/tasks/complete/<?= $task['id'] ?>" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
          <button type="submit" class="btn btn-complete">Mark as Complete</button>
        </form>
      <?php elseif ($task['status'] === 'completed' && $isTaskAssignedToCurrentUser): ?>
        <p class="task-complete-message"><em>This task is marked complete.</em></p>
      <?php endif; ?>
    </div>

    <?php
    // This condition was previously incorrect: elseif ($task['assign_type'] == "client"  || 'admin')
    // It would always evaluate to true because 'admin' is a truthy string.
    // The comments section should be visible to admins if they can see the task, regardless of assignment type.
    // You might want to display comments if the user is an admin or if the task is assigned to the current client.
    // For now, let's assume if an admin can view the task, they can view/leave comments.
    // If the comments section should ONLY appear for admins/clients related to the task, adjust this condition.
    ?>
    <div class="comments-section">
      <div class="comment-form">
        <h3>Leave a Comment</h3>
        <form method="POST" action="/clients/task/<?= $task['id'] ?>/comment" data-task-id="<?= $task['id'] ?>">
          <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
          <div class="form-group">
            <textarea name="message" class="input-field" placeholder="Your comment..." required></textarea>
          </div>
          <div class="action">
            <button type="submit" class="btn btn-save">Submit Comment</button>
          </div>
        </form>
      </div>
      <?php if (!empty($comments)): ?>
        <div class="comment-list" id="comments-<?= $task['id'] ?>">
          <?php include __DIR__ . '/partials/comments-list.php'; ?>
        </div>
      <?php else: ?>
        <p>No comments yet.</p>
      <?php endif; ?>
    </div>

  </div>
</div>