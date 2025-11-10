<?php if (isset($_SESSION['user_id'])) { ?>
  <div class="tab-navigation">
    <ul class="nav-tabs" id="myCustomTabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/dashboard') === 0 ? 'active' : ''; ?>"
          href="/dashboard"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
      <li class="nav-item">
        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/time') === 0 ? 'active' : ''; ?>"
          href="/time"><i class="fas fa-clock"></i><span>Time</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/calendar') === 0 ? 'active' : ''; ?>"
          href="/calendar"><i class="fas fa-calendar-alt"></i><span>Calendar</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/organizations') === 0 ? 'active' : ''; ?>"
          href="/organizations"><i class="fas fa-building"></i><span>SaaS Tenants</span></a>
      </li>
      <li class="nav-item dropdown-custom">
        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/opportunities') === 0 ? 'active' : ''; ?>"
          href="/opportunities"> <i class="fas fa-chart-line"></i><span>Opportunities</span> </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/contacts') === 0 ? 'active' : ''; ?>"
          href="/contacts"><i class="fas fa-address-book"></i><span>My Clients</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/contacts') === 0 ? 'active' : ''; ?>"
          href="/invoices"><i class="fas fa-file-invoice"></i> <span>Invoices</span></a>
      </li>
      <li class="nav-item dropdown-custom">
        <a class="nav-link dropdown-toggle-custom <?php echo strpos($_SERVER['REQUEST_URI'], '/projects') === 0 || strpos($_SERVER['REQUEST_URI'], '/tasks') === 0 ||  strpos($_SERVER['REQUEST_URI'], '/proposals') === 0  ? 'active' : ''; ?>"><span>Projects</span> <i class="fas fa-caret-down"></i></a>
        <ul class="dropdown-menu-custom">
          <li><a class="dropdown-item-custom <?php echo strpos($_SERVER['REQUEST_URI'], '/projects') === 0 ? 'active' : ''; ?>" href="/projects"><i class="fas fa-folder-open"><span>All Projects</span></a></li>
          <li><a class="dropdown-item-custom <?php echo strpos($_SERVER['REQUEST_URI'], '/tasks') === 0 ? 'active' : ''; ?>" href="/tasks"><i class="fas fa-tasks"><span>All Tasks</span></a></li>
          <li><a class="dropdown-item-custom <?php echo strpos($_SERVER['REQUEST_URI'], '/proposals') === 0 ? 'active' : ''; ?>" href="/proposals"><i class="fas fa-list-alt"><span>All Proposals</span></a></li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/dashboards/leads') === 0 ? 'active' : ''; ?>"
          href="/dashboards/leads"><i class="fa fa-users"><span>Leads</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/interactions') === 0 ? 'active' : ''; ?>"
          href="/interactions">Interactions</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/extensions') === 0 ? 'active' : ''; ?>"
          href="/extensions">Extensions</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/extensions') === 0 ? 'active' : ''; ?>"
          href="/logs"><i class="fas fa-list-alt">Activity Logs</a>
      </li>
    </ul>
  </div>
<?php } ?>