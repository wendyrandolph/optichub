<?php require_once base_path('app/helpers/CSRF.php'); ?>

<div class="page-container">
  <div class="content-wrap">
    <div class="card">
      <h2 class="card-heading">Upload Files for: <?= htmlspecialchars($task['title']) ?></h2>

      <form method="POST" action="/clients/upload/<?= $task['id'] ?>" enctype="multipart/form-data" class="form">

        <div id="upload-fields">
          <div class="input">
            <input class="input-field" type="file" name="files[]" required>
            <label class="input-label">Choose File</label>
          </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="addUploadField()">+ Add Another File</button>

        <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">

        <div class="action">
          <button type="submit" class="action-button">Upload</button>
        </div>
      </form>

      <?php if (!empty($task['uploaded_files'])): ?>
        <div class="uploaded-files">
          <h3>Previously Uploaded</h3>
          <ul>
            <?php foreach (explode(',', $task['uploaded_files']) as $file): ?>
              <li><a href="<?= htmlspecialchars($file) ?>" target="_blank"><?= basename($file) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>