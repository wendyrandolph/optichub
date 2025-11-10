<?php require_once base_path('app/helpers/CSRF.php'); ?>

<?php // Flash Message Block 
if (!empty($_SESSION['success_message'])): ?>
  <div class="alert alert-success">
    <?= htmlspecialchars($_SESSION['success_message']) ?>
    <?php unset($_SESSION['success_message']); ?>
  </div>
<?php endif; ?>

<?php if (!empty($_SESSION['error_message'])): ?>
  <div class="alert alert-error">
    <?= htmlspecialchars($_SESSION['error_message']) ?>
    <?php unset($_SESSION['error_message']); ?>
  </div>
<?php endif; ?>
<p>
  <a href="/client/portal" class="btn btn-back">&larr; Back to Project</a>
</p>
<div class="task-comments">
  <h2 class="card-header">Task: <?= htmlspecialchars($task['title']) ?></h2>
  <div class="comment-info">
    <p><?= nl2br(htmlspecialchars($task['description'])) ?></p>
    <p><strong>Due:</strong> <?= date('F j, Y', strtotime($task['due_date'])) ?></p>
    <p class="highlight"> Newest Comments are listed first </p>
  </div>

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