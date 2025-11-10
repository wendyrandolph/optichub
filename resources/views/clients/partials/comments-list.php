 <?php foreach ($comments as $comment): ?>
   <li class="comment-item <?= $comment['role'] === 'admin' ? 'admin-comment' : 'client-comment' ?>">

     <div class="comment-meta">
       <strong><?= htmlspecialchars($comment['username'] ?? 'Client') ?></strong> &middot;
       <span><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></span>
     </div>
     <div class="comment-body">
       <?= nl2br(htmlspecialchars($comment['message'])) ?>
     </div>
     <?php if ($comment['user_id'] === $_SESSION['user_id']): ?>
       <button
         class="btn btn-delete"
         data-task-id="<?= $task['id'] ?>"
         data-comment-id="<?= $comment['id'] ?>">
         Delete
       </button>
     <?php endif; ?>

   </li>
 <?php endforeach; ?>