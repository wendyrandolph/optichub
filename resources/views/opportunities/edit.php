<?php require_once base_path('app/helpers/CSRF.php'); ?>
<div class="container">
  <a href="/opportunities" class="btn btn-back">Back to Opportunities</a>
</div>
<div class="form-wrapper cws-card-4">
  <div class="cws-container cws-theme">
    <p class="cws-title"> Edit This Opportunity </p>
  </div>
  <form method="POST" action="/opportunities/update/<?= $opportunity['id'] ?>" class="cws-container" style="margin-top: 16px;">
    <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
    <div class="row-padding">
      <div class="cws-half">
        <label for="title">Title *</label>
        <input class="cws-input" type="text" name="title" value="<?= htmlspecialchars($opportunity['title']) ?>" required>
      </div>

      <div class="cws-half">
        <label for="organization_id">Organization</label>
        <select name="organization_id" class="cws-select">
          <option value="">-- None --</option>
          <?php foreach ($organizations as $org): ?>
            <option value="<?= $org['id'] ?>" <?= $opportunity['organization_id'] == $org['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($org['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row-padding">
      <div class="cws-half">
        <label for="stage">Stage</label>
        <select name="stage" class="cws-select">
          <?php
          $stages = ['Prospect', 'Proposal Sent', 'Negotiation', 'Contract Signed', 'Lost'];
          foreach ($stages as $stage):
          ?>
            <option value="<?= $stage ?>" <?= $opportunity['stage'] === $stage ? 'selected' : '' ?>><?= $stage ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="cws-half">
        <label for="estimated_value">Estimated Value</label>
        <input class="cws-input" type="number" step="0.01" name="estimated_value" value="<?= $opportunity['estimated_value'] ?>">
      </div>
    </div>
    <div class="row-padding">
      <div class="cws-half">
        <label for="close_date">Expected Close Date</label>
        <input class="cws-date" type="date" name="close_date" value="<?= $opportunity['close_date'] ?>">
      </div>
      <div class="cws-half">
        <label for="notes">Notes</label>
        <textarea class="cws-input notes" name="notes"><?= htmlspecialchars($opportunity['notes']) ?></textarea>
      </div>
    </div>
    <div class="row-padding">
      <div class="cws-half">
        <button type="submit" class="btn btn-save">Update Opportunity</button>
      </div>
    </div>
  </form>
</div>