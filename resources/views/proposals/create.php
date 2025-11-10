<div class="container">
  <h2>Create Proposal</h2>
  <form action="/proposals/store" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div class="form-group">
      <label for="title">Title</label>
      <input type="text" class="form-control" id="title" name="title" required>
    </div>

    <div class="form-group">
      <label for="project_id">Project</label>
      <select class="form-control" id="project_id" name="project_id" required>
        <?php foreach ($projects as $project): ?>
          <option value="<?= htmlspecialchars($project['id']) ?>"><?= htmlspecialchars($project['project_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="client_id">Client</label>
      <select class="form-control" id="client_id" name="client_id" required>
        <?php foreach ($clients as $client): ?>
          <option value="<?= htmlspecialchars($client['id']) ?>"><?= htmlspecialchars($client['firstName'] . ' ' . $client['lastName']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="goals">Goals</label>
      <textarea name="goals" id="goals" class="form-control" rows="5"></textarea>
    </div>

    <div class="form-group">
      <label for="objectives">Objectives</label>
      <textarea name="objectives" id="objectives" class="form-control" rows="5"></textarea>
    </div>

    <div class="form-group">
      <label for="investment">Project Investment & Payment Terms</label>
      <textarea name="investment" id="investment" class="form-control" rows="5"></textarea>
    </div>

    <div class="form-group">
      <label for="timeline">Project Timeline</label>
      <textarea name="timeline" id="timeline" class="form-control" rows="5"></textarea>
    </div>

    <button type="submit" class="btn btn-save">Save Proposal</button>
  </form>
</div>