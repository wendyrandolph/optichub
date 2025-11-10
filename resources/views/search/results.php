<h1 class="page-title">Search Results for "<?= htmlspecialchars($query) ?>"</h1>

<?php if (empty($clients) && empty($projects) && empty($tasks)): ?>
  <p>No results found.</p>
<?php endif; ?>

<?php if (!empty($clients)): ?>
  <h2>Clients</h2>
  <ul>
    <?php foreach ($clients as $client): ?>
      <li><a href="/contacts/view/<?= $client['id'] ?>"><?= htmlspecialchars($client['client_name']) ?></a></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php if (!empty($projects)): ?>
  <h2>Projects</h2>
  <ul>
    <?php foreach ($projects as $project): ?>
      <li><a href="/projects/edit/<?= $project['id'] ?>"><?= htmlspecialchars($project['project_name']) ?></a></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php if (!empty($tasks)): ?>
  <h2>Tasks</h2>
  <ul>
    <?php foreach ($tasks as $task): ?>
      <li><a href="/tasks/view/<?= $task['id'] ?>"><?= htmlspecialchars($task['title']) ?></a></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
</div>
</div>