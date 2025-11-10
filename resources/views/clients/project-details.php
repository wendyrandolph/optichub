<?php
// ------------------------- Normalize inputs -------------------------------
if (!function_exists('oh_to_array')) {
  function oh_to_array($v)
  {
    if (is_array($v)) {
      foreach ($v as $k => $vv) $v[$k] = oh_to_array($vv);
      return $v;
    }
    if (is_object($v)) return oh_to_array(get_object_vars($v));
    return $v;
  }
}
$project       = oh_to_array($project ?? []);
$phaseGroups   = oh_to_array($phaseGroups ?? []);
$milestones    = oh_to_array($milestones ?? []);
$activity      = oh_to_array($activity ?? []);
$messages      = oh_to_array($messages ?? []);
$recentUploads = oh_to_array($recentUploads ?? []);
$csrfToken     = $csrf ?? (class_exists('CSRF') ? CSRF::generate() : '');

// ------------------------- Helpers & derived data -------------------------
$taskStatus = fn($t) => strtolower($t['status'] ?? '');
$taskDueTs  = fn($t) => !empty($t['due_date']) ? strtotime($t['due_date']) : PHP_INT_MAX;
$isClientTask = function ($t) {
  $who = strtolower($t['assign_type'] ?? $t['assign_role'] ?? '');
  return $who === 'client';
};

// Collect all tasks from phase groups
$allTasks = [];
if (!empty($phaseGroups)) {
  foreach ($phaseGroups as $g) {
    if (!empty($g['tasks']) && is_array($g['tasks'])) {
      foreach ($g['tasks'] as $t) $allTasks[] = $t;
    }
  }
}

// Progress + last update
$total = 0;
$done = 0;
$last = null;
foreach ($allTasks as $t) {
  $total++;
  if ($taskStatus($t) === 'completed') $done++;
  $ts = strtotime($t['updated_at'] ?? $t['created_at'] ?? '');
  if ($ts && ($last === null || $ts > $last)) $last = $ts;
}
$pct = $total ? (int)round(($done / $total) * 100) : 0;

// Current/visible client actions (“Next steps”)
$next = array_values(array_filter($allTasks, function ($t) use ($isClientTask, $taskStatus) {
  // client-assigned OR requires approval, and not completed
  if (in_array($taskStatus($t), ['open', 'in_progress'], true) && $isClientTask($t)) return true;
  if (!empty($t['requires_approval']) && $taskStatus($t) !== 'completed') return true;
  return false;
}));
usort($next, fn($a, $b) => $taskDueTs($a) <=> $taskDueTs($b));

// Approvals list for tab
$approvals = array_values(array_filter($allTasks, fn($t) => !empty($t['requires_approval']) && $taskStatus($t) !== 'completed'));

// If controller didn’t pass activity, create a lightweight one
if (empty($activity) && !empty($allTasks)) {
  foreach ($allTasks as $t) {
    $activity[] = [
      'message'    => ($taskStatus($t) === 'completed' ? 'Completed: ' : 'Updated: ') . ($t['title'] ?? 'Task'),
      'created_at' => $t['updated_at'] ?? $t['created_at'] ?? date('c'),
    ];
  }
  usort($activity, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
  $activity = array_slice($activity, 0, 6);
}

// Current phase name for header chip
$phaseName = 'Project';
if (!empty($phaseGroups)) {
  $firstNonEmpty = null;
  foreach ($phaseGroups as $g) {
    $tasks = $g['tasks'] ?? [];
    if ($tasks && !$firstNonEmpty) $firstNonEmpty = ($g['name'] ?? 'Project');
    $hasActive = array_filter($tasks, fn($x) => in_array($taskStatus($x), ['open', 'in_progress'], true));
    if ($hasActive) {
      $phaseName = $g['name'] ?? 'Project';
      break;
    }
  }
  if ($phaseName === 'Project' && $firstNonEmpty) $phaseName = $firstNonEmpty;
}

$now = strtotime('today');
$in7 = strtotime('+7 days');

$openClient = 0;
$overdue = 0;
$dueSoon = 0;
$approvalsCount = 0;
foreach ($allTasks as $t) {
  $status = strtolower($t['status'] ?? '');
  $role   = strtolower($t['assign_type'] ?? $t['assign_role'] ?? '');
  $dueTs  = !empty($t['due_date']) ? strtotime($t['due_date']) : null;

  if (in_array($status, ['open', 'in_progress'], true) && $role === 'client') $openClient++;
  if ($dueTs && $status !== 'completed' && $dueTs < $now) $overdue++;
  if ($dueTs && $status !== 'completed' && $dueTs >= $now && $dueTs <= $in7) $dueSoon++;
  if (!empty($t['requires_approval']) && $status !== 'completed') $approvalsCount++;
}

// Phase roadmap percentages per group you already passed in $phaseGroups
$phaseProgress = [];
foreach ($phaseGroups as $g) {
  $tasks = $g['tasks'] ?? [];
  $tot = count($tasks);
  $doneInPhase = 0;
  foreach ($tasks as $t) if (strtolower($t['status'] ?? '') === 'completed') $doneInPhase++;
  $phaseProgress[] = [
    'name' => $g['name'] ?? 'Phase',
    'pct'  => $tot ? round(($doneInPhase / $tot) * 100) : 0
  ];
}

?>
<section class="client-project" aria-labelledby="projTitle">
  <!-- Header -->
  <header class="proj-header">
    <a class="link-back" href="/client/portal">← Back</a>
    <h1 id="projTitle"><?= htmlspecialchars($project['project_name'] ?? 'Project') ?></h1>
    <!-- Meta row (phase chip + progress) -->
    <div class="oh-summary">
      <span class="pill phase"><?= htmlspecialchars($phaseName ?? 'Project') ?></span>
      <span class="pill done"><strong><?= $done ?></strong>/<span><?= $total ?></span> done</span>
      <span class="updated">
        <?php if ($last): ?>Updated <span data-timeago="<?= date('c', $last) ?>">just now</span><?php endif; ?>
      </span>
    </div>
    <div class="oh-progress" role="progressbar" aria-valuenow="<?= (int)$pct ?>">
      <div style="width:<?= (int)$pct ?>%"></div>
    </div>

    <!-- Status chips (no raw echoes) -->
    <div class="oh-statusbar">
      <div class="stat"><span class="k"><?= (int)$openClient ?></span><span class="l">Your open tasks</span></div>
      <div class="stat <?= $overdue ? 'bad' : '' ?>"><span class="k"><?= (int)$overdue ?></span><span class="l">Overdue</span></div>
      <div class="stat <?= $dueSoon ? 'warn' : '' ?>"><span class="k"><?= (int)$dueSoon ?></span><span class="l">Due soon</span></div>
      <div class="stat <?= $approvalsCount ? 'info' : '' ?>"><span class="k"><?= (int)$approvalsCount ?></span><span class="l">Approvals</span></div>
    </div>

    <!-- Optional roadmap (keep or remove) -->
    <?php if (!empty($phaseProgress)): ?>
      <ul class="oh-roadmap">
        <?php foreach ($phaseProgress as $i => $p): ?>
          <li class="node<?= $p['pct'] >= 100 ? ' done' : ($p['pct'] > 0 ? ' active' : '') ?>">
            <div class="dot"></div>
            <div class="label">
              <div class="name"><?= htmlspecialchars($p['name']) ?></div>
              <div class="bar"><span style="width:<?= (int)$p['pct'] ?>%"></span></div>
            </div>
          </li>
          <?php if ($i < count($phaseProgress) - 1): ?><li class="rail"></li><?php endif; ?>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <!-- Next steps -->
    <div class="oh-grid">
      <main class="oh-main">
        <section class="proj-section" aria-labelledby="nextSteps">
          <h2 id="nextSteps">What we need from you</h2>
          <?php if (!empty($next)): ?>
            <ul class="cards">
              <?php foreach (array_slice($next, 0, 5) as $t): ?>
                <li class="card">
                  <div class="card-title"><?= htmlspecialchars($t['title'] ?? 'Task') ?></div>
                  <div class="card-meta">
                    <span class="pill"><?= htmlspecialchars($t['phase_name'] ?? 'Task') ?></span>
                    <span class="dot"></span>
                    <span class="muted">Due <?= !empty($t['due_date']) ? date('M j', strtotime($t['due_date'])) : '—' ?></span>
                  </div>
                  <?php if (!empty($t['description'])): ?>
                    <p class="card-desc"><?= nl2br(htmlspecialchars($t['description'])) ?></p>
                  <?php endif; ?>
                  <div class="card-actions">
                    <?php if (!empty($t['form_url'])): ?>
                      <a class="btn btn-ghost" target="_blank" href="<?= htmlspecialchars($t['form_url']) ?>">Open form</a>
                    <?php endif; ?>
                    <a class="btn btn-primary" href="/clients/task/<?= (int)($t['id'] ?? 0) ?>">Review</a>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="empty">Nothing needs your attention right now ✅</div>
          <?php endif; ?>
        </section>

        <!-- Tabs -->
        <section class="proj-tabs">
          <div class="tabbar" role="tablist">
            <button class="tab is-active" role="tab" data-tab="overview">Overview</button>
            <button class="tab" role="tab" data-tab="tasks">Tasks</button>
            <button class="tab" role="tab" data-tab="files">Files</button>
            <button class="tab" role="tab" data-tab="messages">Messages</button>
            <button class="tab" role="tab" data-tab="approvals">Approvals</button>
          </div>
        </section>
      </main>
      <div class="oh-aside">
        <div class="panels">
          <!-- Overview -->
          <div class="panel is-active" data-panel="overview">
            <div class="grid-2">
              <div>
                <h3>Milestones</h3>
                <ul class="list">
                  <?php if (!empty($milestones)): ?>
                    <?php foreach ($milestones as $m): ?>
                      <li class="list-row">
                        <div class="row-title"><?= htmlspecialchars($m['title'] ?? '') ?></div>
                        <div class="muted"><?= !empty($m['due_date']) ? date('M j', strtotime($m['due_date'])) : '' ?></div>
                      </li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li class="empty">No milestones yet.</li>
                  <?php endif; ?>
                </ul>
              </div>
              <div>
                <h3>Recent activity</h3>
                <ul class="list">
                  <?php if (!empty($activity)): ?>
                    <?php foreach (array_slice($activity, 0, 5) as $ev): ?>
                      <li class="list-row">
                        <div class="row-title"><?= htmlspecialchars($ev['message'] ?? '') ?></div>
                        <?php if (!empty($ev['created_at'])): ?>
                          <div class="muted" data-timeago="<?= date('c', strtotime($ev['created_at'])) ?>">now</div>
                        <?php endif; ?>
                      </li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li class="empty">We’ll show updates as they happen.</li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>
          </div>

          <!-- Tasks (reuse your existing rendered HTML/accordion/table) -->
          <div class="panel" data-panel="tasks">
            <?= $tasksHtml ?? '' ?>
          </div>

          <!-- Files -->
          <div class="panel" data-panel="files">
            <?php
            // Allow uploads to any actionable client task
            $uploadable = array_values(array_filter($next, fn($t) => !empty($t['id'])));
            $defaultUploadId = !empty($uploadable[0]['id']) ? (int)$uploadable[0]['id'] : 0;
            ?>
            <form id="projUpload" class="drop" method="POST" action="/clients/upload/<?= $defaultUploadId ?>" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
              <?php if ($uploadable): ?>
                <select id="uploadTask" class="oh-drop__select" aria-label="Choose task">
                  <?php foreach ($uploadable as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['title'] ?? 'Task') ?></option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
              <input id="projFiles" type="file" name="files[]" multiple hidden>
              <p><strong>Drop files</strong> here or <button type="button" class="link" id="browseFiles">browse</button></p>
            </form>

            <ul class="list" style="margin-top:10px">
              <?php if (!empty($recentUploads)): ?>
                <?php foreach (array_slice($recentUploads, 0, 8) as $u): ?>
                  <li class="list-row">
                    <div class="row-title"><?= htmlspecialchars($u['filename'] ?? basename($u['url'] ?? '')) ?></div>
                    <div class="muted"><?= !empty($u['uploaded_at']) ? date('M j', strtotime($u['uploaded_at'])) : '' ?></div>
                    <?php if (!empty($u['url'])): ?>
                      <a class="btn btn-ghost" href="<?= htmlspecialchars($u['url']) ?>" target="_blank">View</a>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="empty">No files yet.</li>
              <?php endif; ?>
            </ul>
          </div>

          <!-- Messages -->
          <div class="panel" data-panel="messages">
            <ul class="list">
              <?php if (!empty($messages)): ?>
                <?php foreach (array_slice($messages, 0, 10) as $m): ?>
                  <li class="list-row">
                    <div>
                      <div class="row-title"><?= htmlspecialchars($m['subject'] ?? ($m['task_title'] ?? 'Message')) ?></div>
                      <div class="muted">
                        <?= htmlspecialchars($m['project_name'] ?? '') ?>
                        <?php if (!empty($m['created_at'])): ?>
                          • <span data-timeago="<?= date('c', strtotime($m['created_at'])) ?>">now</span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php if (!empty($m['task_id'])): ?>
                      <a class="btn btn-ghost" href="/clients/task/<?= (int)$m['task_id'] ?>">Open</a>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="empty">No messages yet.</li>
              <?php endif; ?>
            </ul>
          </div>

          <!-- Approvals -->
          <div class="panel" data-panel="approvals">
            <ul class="list">
              <?php if (!empty($approvals)): ?>
                <?php foreach ($approvals as $a): ?>
                  <li class="list-row">
                    <div class="row-title"><?= htmlspecialchars($a['title'] ?? 'Approval') ?></div>
                    <div class="row-actions">
                      <form method="POST" action="/tasks/approve/<?= (int)($a['id'] ?? 0) ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <button class="btn btn-primary">Approve</button>
                      </form>
                      <a class="btn btn-ghost" href="/clients/task/<?= (int)($a['id'] ?? 0) ?>">Details</a>
                    </div>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="empty">Nothing to approve right now.</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
</section>


<!-- Tabs / time-ago / upload JS -->
<script>
  (() => {
    // Tabs
    document.addEventListener('click', (e) => {
      const t = e.target.closest('.tab');
      if (!t) return;
      const wrap = t.closest('.proj-tabs');
      wrap.querySelectorAll('.tab').forEach(x => x.classList.toggle('is-active', x === t));
      const name = t.dataset.tab;
      wrap.querySelectorAll('.panel').forEach(p => p.classList.toggle('is-active', p.dataset.panel === name));
    });

    // Time ago
    const ago = iso => {
      const s = Math.floor((Date.now() - new Date(iso)) / 1000),
        f = (v, u) => `${v} ${u}${v>1?'s':''} ago`;
      if (s < 60) return f(s, 'sec');
      const m = s / 60 | 0;
      if (m < 60) return f(m, 'min');
      const h = m / 60 | 0;
      if (h < 24) return f(h, 'hour');
      const d = h / 24 | 0;
      if (d < 30) return f(d, 'day');
      const mo = d / 30 | 0;
      if (mo < 12) return f(mo, 'month');
      return f((mo / 12 | 0), 'year');
    };
    const renderTimes = () => document.querySelectorAll('[data-timeago]').forEach(el => {
      const iso = el.getAttribute('data-timeago');
      if (iso) el.textContent = ago(iso);
    });
    renderTimes();
    setInterval(renderTimes, 60000);

    // Upload: set action to selected task and support drag/drop
    const dz = document.getElementById('projUpload');
    const pick = document.getElementById('uploadTask');
    const input = document.getElementById('projFiles');
    const browse = document.getElementById('browseFiles');

    function setAction() {
      if (dz && pick) dz.action = `/clients/upload/${pick.value}`;
    }
    pick && setAction() && pick.addEventListener('change', setAction);

    if (dz && input) {
      ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => {
        e.preventDefault();
        dz.classList.add('is-drag');
      }));
      ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
        e.preventDefault();
        dz.classList.remove('is-drag');
      }));
      dz.addEventListener('drop', e => {
        if (e.dataTransfer.files?.length) {
          input.files = e.dataTransfer.files;
          dz.submit();
        }
      });
      browse?.addEventListener('click', () => input.click());
      input.addEventListener('change', () => {
        if (input.files.length) dz.submit();
      });
    }
  })();
</script>