<?php foreach ($comments as $comment): ?>
  <li class="comment">
    <div>
      <strong><?= htmlspecialchars($comment['role']) ?>:</strong>
      <p><?= nl2br(htmlspecialchars($comment['message'])) ?></p>
      <small><?= htmlspecialchars($comment['created_at']) ?></small>
    </div>

    <?php if ($_SESSION['user_id'] == $comment['user_id']): ?>
      <button class="btn-delete-comment" data-id="<?= $comment['id'] ?>">✖</button>
    <?php endif; ?>
  </li>
<?php endforeach; ?>