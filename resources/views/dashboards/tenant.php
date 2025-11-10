<?php $access = $tenantAccess ?? ['state' => 'unknown', 'days_left' => null]; ?>

<?php if ($access['state'] === 'trialing'): ?>
  <div class="notice notice--info">
    <strong>Trial:</strong> <?= $access['days_left'] ?> days left.
    <a class="btn btn--primary" href="/billing/upgrade">Upgrade</a>
  </div>
<?php elseif ($access['state'] === 'beta'): ?>
  <div class="notice notice--beta">
    <strong>Beta access</strong> <?= $access['days_left'] !== null ? "— {$access['days_left']} days remaining." : '' ?>
    <a class="btn btn--ghost" href="/contact?topic=feedback">Send feedback</a>
  </div>
<?php elseif ($access['state'] === 'expired'): ?>
  <div class="notice notice--danger">
    Your trial has ended. <a class="btn btn--primary" href="/billing/upgrade">Activate your account</a>
  </div>
<?php endif; ?>



<section class="section section--white">
  <div class="container">

    <?php if (!empty($trial['trial_status']) && $trial['trial_status'] === 'active'): ?>
      <div class="card card--glass" style="padding:16px; margin-bottom:16px">
        <strong>Trial</strong>
        <?php if ($trial['days_left'] !== null): ?>
          — <?= (int)$trial['days_left'] ?> days left
        <?php endif; ?>
        <?php if (!empty($trial['trial_ends_at'])): ?>
          (ends <?= htmlspecialchars(date('M j, Y', strtotime($trial['trial_ends_at']))) ?>)
        <?php endif; ?>
        <div style="margin-top:8px">
          <a class="btn btn--primary" href="/pricing">Upgrade now</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if (empty($trial['onboarded_at'])): ?>
      <div class="card card--glass" style="padding:16px; margin-bottom:16px">
        <strong>Finish setup</strong> — You still have a few steps left.
        <div style="margin-top:8px">
          <a class="btn btn--ghost" href="/onboarding/company">Resume onboarding</a>
        </div>
      </div>
    <?php endif; ?>

    <h2 class="h2">Welcome to Optic Hub</h2>
    <p class="copy">Quick next steps to get value fast.</p>

    <div class="cards" style="margin-top:12px">
      <article class="card card--glass feature">
        <div class="feature__icon"></div>
        <div class="feature__body">
          <h3 class="h4">Connect your website form</h3>
          <p class="copy">Use your API key to send new inquiries straight into Optic Hub.</p>
          <a class="btn btn--ghost" href="/settings/api">View API Keys</a>
        </div>
      </article>

      <article class="card card--glass feature">
        <div class="feature__icon feature__icon--accent"></div>
        <div class="feature__body">
          <h3 class="h4">Add your first project</h3>
          <p class="copy">Create a project and assign next steps to keep work moving.</p>
          <a class="btn btn--ghost" href="/projects/new">New Project</a>
        </div>
      </article>

      <article class="card card--glass feature">
        <div class="feature__icon"></div>
        <div class="feature__body">
          <h3 class="h4">Invite a teammate</h3>
          <p class="copy">If you’re on a team plan, get someone else in here with you.</p>
          <a class="btn btn--ghost" href="/settings/team">Manage Team</a>
        </div>
      </article>
    </div>

    <div class="cards" style="margin-top:16px">
      <article class="card card--glass" style="padding:16px">
        <h3 class="h4">At a glance</h3>
        <ul class="clean">
          <li><span class="icon-check"></span>Projects hours entries: <?= count($projectHours ?? []) ?></li>
          <li><span class="icon-check"></span>Leads — New: <?= (int)($leadStatusCounts['new'] ?? 0) ?>, Contacted: <?= (int)($leadStatusCounts['contacted'] ?? 0) ?>, Interested: <?= (int)($leadStatusCounts['interested'] ?? 0) ?></li>
          <li><span class="icon-check"></span>Recent task activity records: <?= count($taskData ?? []) ?></li>
        </ul>
      </article>
    </div>

  </div>
</section>