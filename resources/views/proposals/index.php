<div class="container-fluid">
  <div class="header-with-button">
    <h2>Proposals</h2>
    <a href="/proposals/create" class="btn btn-primary">Create New Proposal</a>
  </div>

  <table class="data-table">
    <thead>
      <tr>
        <th>Title</th>
        <th>Client</th>
        <th>Project</th>
        <th>Status</th>
        <th>Sent At</th>
        <th class="action-column">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($proposals)): ?>
        <?php foreach ($proposals as $proposal): ?>
          <tr>
            <td><?= htmlspecialchars($proposal['title']) ?></td>
            <td><?= htmlspecialchars($proposal['client_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($proposal['project_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($proposal['status']) ?></td>
            <td><?= htmlspecialchars($proposal['sent_at'] ?? 'N/A') ?></td>
            <td class="action-column">
              <a href="/proposals/view/<?= $proposal['id'] ?>" class="tooltip" title="View"><i class="fas fa-eye"></i></a>
              <a href="/proposals/edit/<?= $proposal['id'] ?>" class="tooltip" title="Edit"><i class="fas fa-edit"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="6">No proposals found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>