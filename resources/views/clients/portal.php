<?php $csrf = CSRF::generate(); ?>
<section class="client-home" aria-labelledby="homeHeading">
  <header class="home-hero">
    <h1 id="homeHeading">Welcome back<?= isset($client['first_name']) ? ', ' . htmlspecialchars($client['first_name']) : '' ?>.</h1>
    <p class="muted">Here’s what needs your attention today.</p>
  </header>

  <!-- 1) Action Items -->
  <section class="home-section" aria-labelledby="actHeading">
    <div class="section-head">
      <h2 id="actHeading">Your Action Items</h2>
      <a class="link" href="/tasks?mine=1">See all</a>
    </div>

    <?php if (!empty($actionItems)): ?>
      <ul class="cards grid-3">
        <?php foreach (array_slice($actionItems, 0, 5) as $t): ?>
          <li class="card action-card" role="article">
            <h3 class="card-title"><?= htmlspecialchars($t['title']) ?></h3>
            <p class="card-meta">
              <span class="pill"><?= htmlspecialchars($t['project_name']) ?></span>
              <span class="dot"></span>
              <span class="muted">Due <?= $t['due_date'] ? date('M j', strtotime($t['due_date'])) : '—' ?></span>
            </p>
            <p class="card-desc"><?= nl2br(htmlspecialchars($t['description'] ?? '')) ?></p>
            <div class="card-actions">
              <?php if (!empty($t['form_url'])): ?>
                <a class="btn btn-ghost" href="<?= htmlspecialchars($t['form_url']) ?>" target="_blank">Open form</a>
              <?php endif; ?>
              <a class="btn btn-primary" href="/clients/task/<?= (int)$t['id'] ?>">Review</a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="empty">You’re all caught up ✅</div>
    <?php endif; ?>
  </section>

  <!-- 2) Quick Actions -->
  <section class="home-section" aria-labelledby="qaHeading">
    <h2 id="qaHeading" class="sr-only">Quick actions</h2>
    <div class="quick-actions">
      <a class="qa" href="/request/new"><i class="fa-solid fa-plus"></i> Start a request</a>
      <a class="qa" href="/uploads"><i class="fa-solid fa-upload"></i> Upload files</a>
      <a class="qa" href="/messages"><i class="fa-solid fa-message"></i> Message your team</a>
      <a class="qa" href="/invoices"><i class="fa-solid fa-file-invoice-dollar"></i> View invoices</a>
    </div>
  </section>

  <!-- 3) Projects overview -->
  <section class="home-section" aria-labelledby="projHeading">
    <div class="section-head">
      <h2 id="projHeading">Your Projects</h2>
    </div>

    <?php if (!empty($activeProjects)): ?>
      <ul class="project-list">
        <?php foreach ($activeProjects as $project): ?>
          <?php
          $open = 0;
          $last = null;
          $phaseName = null;
          foreach ($project['phases'] as $ph) {
            if (!$phaseName) $phaseName = $ph['name'] ?? null;  // first/active phase
            foreach ($ph['tasks'] as $tt) {
              if (($tt['status'] ?? '') !== 'completed') $open++;
              $ts = strtotime($tt['updated_at'] ?? $tt['created_at'] ?? '');
              if ($ts && ($last === null || $ts > $last)) $last = $ts;
            }
          }
          ?>

          <li class="project-card" role="article">
            <div class="pc-main">
              <h3 class="pc-title"><?= htmlspecialchars($project['project_name']) ?></h3>
              <p class="pc-meta">
                <?php if ($phaseName): ?><span class="pill pill--phase"><?= htmlspecialchars($phaseName) ?></span><?php endif; ?>
                <span class="pill"><?= $open ?> open task<?= $open === 1 ? '' : 's' ?></span>
                <?php if ($last): ?><span class="muted" data-timeago="<?= date('c', $last) ?>">Updated just now</span><?php endif; ?>
              </p>
              <?php
              $taskCount = 0;
              $fileCount = 0;
              $msgUnread = 0;
              foreach ($project['phases'] as $ph) {
                foreach ($ph['tasks'] as $tt) {
                  if (($tt['status'] ?? '') !== 'completed') $taskCount++;
                  $fileCount += (int)($tt['upload_count'] ?? 0);                // or 0
                  $msgUnread += (int)($tt['unread_comments_for_client'] ?? 0);  // or 0
                }
              }
              ?>
              <div class="pc-links">
                <a href="/tasks?project=<?= (int)$project['id'] ?>">Tasks <span class="pill"><?= $taskCount ?></span></a>
                <a href="/uploads?project=<?= (int)$project['id'] ?>">Files <span class="pill"><?= $fileCount ?></span></a>
                <a href="/messages?project=<?= (int)$project['id'] ?>">Messages<?php if ($msgUnread): ?>
                  <span class="badge badge--danger"><?= $msgUnread ?></span><?php endif; ?>
                </a>
              </div>
              <?php
              $total = 0;
              $done = 0;
              foreach ($project['phases'] as $ph) {
                foreach ($ph['tasks'] as $tt) {
                  $total++;
                  if (($tt['status'] ?? '') === 'completed') $done++;
                }
              }
              $pct = $total ? round(($done / $total) * 100) : 0;
              ?>
              <div class="pc-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pct ?>" aria-label="Project progress">
                <div style="width: <?= $pct ?>%"></div>
              </div>

              <div class="pc-alerts">
                <?php
                $overdue = 0;
                $dueSoon = 0;
                $today = strtotime('today +0 day');
                foreach ($project['phases'] as $ph) {
                  foreach ($ph['tasks'] as $tt) {
                    if (($tt['status'] ?? '') === 'completed' || empty($tt['due_date'])) continue;
                    $d = strtotime($tt['due_date']);
                    if ($d < $today) $overdue++;
                    elseif ($d <= strtotime('+7 days', $today)) $dueSoon++;
                  }
                }
                ?>
                <?php if ($overdue): ?>
                  <span class="badge badge--danger"><?= $overdue ?> overdue</span>
                <?php elseif ($dueSoon): ?>
                  <span class="badge badge--warn"><?= $dueSoon ?> due soon</span>
                <?php endif; ?>
              </div>
              <?php if ($last): ?>
                <span class="muted" data-timeago="<?= date('c', $last) ?>">Updated just now</span>
              <?php endif; ?>
            </div>
            <a class="pc-cta-link" href="/client/portal/project/<?= (int)$project['id'] ?>" aria-label="Open <?= htmlspecialchars($project['project_name']) ?>">
              Open <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>

          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="empty">No active projects yet.</div>
    <?php endif; ?>
  </section>

  <!-- 4) Messages & Approvals -->
  <section class="home-section" aria-labelledby="maHeading">
    <div class="section-head tabs">
      <h2 id="maHeading">Messages & Approvals</h2>
      <div class="tablist" role="tablist">
        <button class="tab is-active" role="tab" data-tab="msgs">Messages</button>
        <button class="tab" role="tab" data-tab="approvals">Approvals</button>
      </div>
    </div>

    <div class="tabpanels">
      <div class="panel is-active" data-panel="msgs">
        <?php if (!empty($messages)): ?>
          <ul class="list">
            <?php foreach (array_slice($messages, 0, 5) as $m): ?>
              <li class="list-row">
                <div>
                  <div class="row-title"><?= htmlspecialchars($m['subject'] ?? 'Message') ?></div>
                  <div class="muted"><?= htmlspecialchars($m['project_name']) ?> • <span data-timeago="<?= date('c', strtotime($m['created_at'])) ?>">now</span></div>
                </div>
                <a class="btn btn-ghost" href="/clients/task/<?= (int)$m['task_id'] ?>">Open</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="empty">No messages yet.</div>
        <?php endif; ?>
      </div>

      <div class="panel" data-panel="approvals">
        <?php if (!empty($approvals)): ?>
          <ul class="list">
            <?php foreach (array_slice($approvals, 0, 5) as $a): ?>
              <li class="list-row">
                <div>
                  <div class="row-title"><?= htmlspecialchars($a['title']) ?></div>
                  <div class="muted"><?= htmlspecialchars($a['project_name']) ?> • Needs your review</div>
                </div>
                <div class="row-actions">
                  <form method="POST" action="/tasks/approve/<?= (int)$a['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <button class="btn btn-primary">Approve</button>
                  </form>
                  <a class="btn btn-ghost" href="/clients/task/<?= (int)$a['id'] ?>">Details</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="empty">Nothing to approve right now.</div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- 5) Recent uploads -->
  <section class="home-section" aria-labelledby="upHeading">
    <div class="section-head">
      <h2 id="upHeading">Recent Files</h2>
      <a class="link" href="/uploads">All files</a>
    </div>
    <?php if (!empty($recentUploads)): ?>
      <ul class="list">
        <?php foreach (array_slice($recentUploads, 0, 5) as $u): ?>
          <li class="list-row">
            <div>
              <div class="row-title"><?= htmlspecialchars($u['filename']) ?></div>
              <div class="muted"><?= htmlspecialchars($u['project_name']) ?> • <span data-timeago="<?= date('c', strtotime($u['uploaded_at'])) ?>">now</span></div>
            </div>
            <a class="btn btn-ghost" href="<?= htmlspecialchars($u['url']) ?>" target="_blank">View</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="empty">No uploads yet.</div>
    <?php endif; ?>
  </section>

  <!-- 6) Recent activity -->
  <section class="home-section" aria-labelledby="raHeading">
    <h2 id="raHeading" class="section-head">Recent Activity</h2>
    <?php if (!empty($activity)): ?>
      <ul class="timeline">
        <?php foreach (array_slice($activity, 0, 6) as $ev): ?>
          <li>
            <span class="dot"></span>
            <div class="tl-body">
              <div class="row-title"><?= htmlspecialchars($ev['message']) ?></div>
              <div class="muted"><span data-timeago="<?= date('c', strtotime($ev['created_at'])) ?>">now</span></div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="empty">We’ll show updates as they happen.</div>
    <?php endif; ?>
  </section>
</section>