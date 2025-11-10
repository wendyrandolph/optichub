<?php /* uses layout main.php via BaseController->view */ ?>
<section class="section">
  <div class="container container--narrow">
    <h1 class="h2">Tell us about your business</h1>

    <?php if (!empty($error)): ?>
      <div class="card card--glass" style="padding:12px;border-left:4px solid #e11d48;margin:10px 0;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/onboarding/company" class="card card--glass" style="padding:16px;">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">

      <label class="h4" for="name">Business name *</label>
      <input id="name" name="name" required
        value="<?= htmlspecialchars($org['name'] ?? '') ?>"
        style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;margin:6px 0 14px;">

      <div style="display:grid;gap:12px;grid-template-columns:1fr 1fr;">
        <div>
          <label class="h4" for="phone">Phone</label>
          <input id="phone" name="phone"
            value="<?= htmlspecialchars($org['phone'] ?? '') ?>"
            style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;margin-top:6px;">
        </div>
        <div>
          <label class="h4" for="website">Website</label>
          <input id="website" name="website"
            value="<?= htmlspecialchars($org['website'] ?? '') ?>"
            style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;margin-top:6px;">
        </div>
      </div>

      <div class="btn-row" style="margin-top:16px;">
        <button class="btn btn--primary">Continue</button>
      </div>
    </form>
  </div>
</section>