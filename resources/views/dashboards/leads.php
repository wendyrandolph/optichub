<?php
// Group recent leads dynamically by month
$leadsGrowthData = [];

foreach ($recentLeads as $lead) {
  if (!empty($lead['created_at'])) {
    $monthName = date('F', strtotime($lead['created_at']));
    if (!isset($leadsGrowthData[$monthName])) {
      $leadsGrowthData[$monthName] = 0;
    }
    $leadsGrowthData[$monthName]++;
  }
}

$orderedMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$leadsGrowthData = array_merge(array_fill_keys($orderedMonths, 0), $leadsGrowthData);
$leadsGrowthData = array_intersect_key($leadsGrowthData, array_flip($orderedMonths));
?>

<?php
require_once base_path('app/helpers/CSRF.php');
?>
<div class="container">
  <div class="dashboard-grid">

    <div class=" dashboard-card cws-dark">
      <div class="cws-db-row">
        <i class="fas fa-user-check" aria-hidden="true"></i>
        <p class="dashboard-number">
          <?php echo htmlspecialchars($clientCount); ?>
        </p>
      </div>
      <p class="db-card-header">Total Clients</p>

    </div>

    <div class="dashboard-card cws-mid">
      <div class="cws-db-row">
        <i class="fas fa-user-plus" aria-hidden="true"></i>
        <p class="dashboard-number ">
          <?php echo htmlspecialchars($leadCount); ?>
        </p>
      </div>
      <p class="db-card-header">Total Leads</p>
    </div>

    <div class="dashboard-card cws-light">
      <div class="cws-db-row">
        <i class="fas fa-chart-line" aria-hidden="true"></i>
        <p class="dashboard-number">
          <?php echo htmlspecialchars($newLeadsThisWeek); ?>
        </p>
      </div>
      <p class="db-card-header">New Leads This Week</p>
    </div>
  </div>
</div>
<!-- Quick Actions -->
<div class="container">
  <div class="cws-wrapper">
    <?php include __DIR__ . '/../layouts/partials/dash-q-a.php';  ?>

    <div class="hero-section" style="border-bottom: none;">
      <h1 class="page-title">Leads</h1>
      <div class="description">
        <p> Here you will find all the projects that are currently in our database </p>
      </div>
      <div class="container-3-col">
        <!-- Search and Filter Form -->
        <div class="search-filter-row" style="grid-column: 1 / span 3">
          <form method="GET" action="" class="search-filter-form">
            <select name="status">
              <option value="">All Statuses</option>
              <?php foreach (['new', 'contacted', 'interested', 'client', 'closed', 'lost'] as $statusOption): ?>
                <option value="<?php echo $statusOption; ?>"
                  <?php if (isset($_GET['status']) && $_GET['status'] === $statusOption) echo 'selected'; ?>>
                  <?php echo ucfirst($statusOption); ?>
                </option>
              <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-filter">
              <i class="fa fa-filter" aria-hidden="true"></i> Filter
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="leads">
  <div class="dashboard-content">
    <div class="dashboard-table-section">


      <?php
      $perPage = 10;
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $offset = ($page - 1) * $perPage;
      $filteredLeads = [];
      foreach ($recentLeads as $lead) {
        $matchesSearch = empty($_GET['search']) || stripos($lead['lead_name'], $_GET['search']) !== false;
        $matchesStatus = empty($_GET['status']) || $lead['status'] === $_GET['status'];
        if ($matchesSearch && $matchesStatus) {
          $filteredLeads[] = $lead;
        }
      }
      $totalFiltered = count($filteredLeads);
      $paginatedLeads = array_slice($filteredLeads, $offset, $perPage);
      $totalPages = ceil($totalFiltered / $perPage);
      ?>

      <table class="table-opportunities">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Title</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($paginatedLeads as $lead): ?>
            <tr>
              <?php $leadName = $lead['first_name'] . ' ' . $lead['last_name']; ?>
              <td><?php echo htmlspecialchars($lead['id']); ?></td>
              <td><?php echo htmlspecialchars($leadName); ?></td>
              <td><?php echo htmlspecialchars($lead['title'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($lead['email']  ?? ''); ?></td>
              <td><?php echo htmlspecialchars($lead['phone'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($lead['status']  ?? ''); ?></td>
              <td>
                <a href="/leads/view/<?php echo $lead['id']; ?>" class="tooltip" title="View Lead">
                  <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                  <span class="tooltiptext">View Lead Details</span>
                </a>

                <a href="/leads/edit/<?php echo $lead['id']; ?>" class="tooltip" title="Edit Lead Details">
                  <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                  <span class="tooltiptext">Edit Lead</span>
                </a>

                <a href="/proposals/create?lead_id=<?php echo $lead['id']; ?>" class="tooltip" title="Create Proposal">
                  <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                  <span class="tooltiptext">Create Proposal</span>
                </a>

                <a href="/leads/convert/<?= $lead['id'] ?>" class="tooltip" title="Convert To Client">
                  <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                  <span class="tooltiptext">Convert To Client</span>
                </a>

                <form method="POST" action="/leads/delete/<?php echo $lead['id']; ?>" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?php echo CSRF::generate(); ?>">
                  <button type="submit" onclick="return confirm('Are you sure you want to delete this lead?');" class="tooltip">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    <span class="tooltiptext">Delete Lead</span>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Pagination Links -->
      <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div id="leads-dashboard-data"
  data-lead-status-counts='<?php echo json_encode($leadStatusCounts); ?>'
  data-leads-growth-data='<?php echo json_encode($leadsGrowthData); ?>'
  style="display: none;">
</div>