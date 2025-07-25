<?php
session_start();
require_once 'db.php';
$admin_name = 'Admin';

// Fetch live statistics
$total_users = $db->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
$total_giftcards = $db->query('SELECT COUNT(*) FROM giftcard_rates')->fetch_row()[0];
$total_bank_accounts = $db->query('SELECT COUNT(*) FROM bank_accounts')->fetch_row()[0];
$total_btc_trades = $db->query('SELECT COUNT(*) FROM btc_transactions')->fetch_row()[0];
$total_giftcard_trades = $db->query('SELECT COUNT(*) FROM giftcard_transactions')->fetch_row()[0];
$total_trades = $total_btc_trades + $total_giftcard_trades;

// Recent users
$recent_users = [];
$res = $db->query('SELECT name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5');
while ($row = $res->fetch_assoc()) { $recent_users[] = $row; }
$res->close();
// Recent trades (btc + giftcard)
$recent_btc = $db->query('SELECT user_id, amount, status, date FROM btc_transactions ORDER BY date DESC LIMIT 5');
$recent_giftcard = $db->query('SELECT user_id, card_type, amount, status, date FROM giftcard_transactions ORDER BY date DESC LIMIT 5');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard | Bitcoin Giftcards</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <style>
    html, body {
      height: 100%;
    }
    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: linear-gradient(252deg, #f8fafd 0%, #e6f4ea 100.44%);
      color: #19376d;
    }
    .admin-header {
      background: #fff;
      border-bottom: 1px solid #e9ecef;
      padding: 0.7rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      z-index: 110;
      min-height: 64px;
      box-shadow: 0 2px 12px rgba(26,147,138,0.04);
    }
    .admin-logo {
      font-weight: 700;
      font-size: 1.3rem;
      color: #1a938a;
      letter-spacing: 1px;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .admin-sidebar {
      background: linear-gradient(180deg, #1a938a 0%, #19376d 100%);
      color: #fff;
      min-height: 100vh;
      padding: 2rem 0.5rem 2rem 0.5rem;
      width: 200px;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 120;
      transition: width 0.2s, left 0.2s;
      box-shadow: 2px 0 16px rgba(26,147,138,0.07);
      display: flex;
      flex-direction: column;
      align-items: stretch;
    }
    .admin-sidebar.collapsed { width: 64px; }
    .admin-sidebar .nav-link {
      color: #fff;
      font-weight: 500;
      border-radius: 0.7rem;
      margin-bottom: 0.3rem;
      display: flex;
      align-items: center;
      gap: 0.7rem;
      padding: 0.7rem 1rem;
      transition: background 0.15s, color 0.15s;
      font-size: 1.08rem;
    }
    .admin-sidebar .nav-link.active, .admin-sidebar .nav-link:hover {
      background: #ffbf3f;
      color: #19376d;
    }
    .admin-sidebar .nav-link .bi { font-size: 1.3rem; }
    .admin-sidebar .sidebar-label { transition: opacity 0.2s; }
    .admin-sidebar.collapsed .sidebar-label { opacity: 0; width: 0; overflow: hidden; }
    .admin-sidebar-toggler {
      background: none;
      border: none;
      color: #fff;
      font-size: 1.5rem;
      margin-bottom: 2rem;
      margin-left: 0.5rem;
      cursor: pointer;
      align-self: flex-end;
      transition: color 0.2s;
    }
    .admin-main-content {
      margin-left: 230px;
      padding: 2rem 2rem 1.5rem 2rem;
      min-height: 100vh;
      transition: margin-left 0.2s;
      flex: 1 0 auto;
      margin-top: 7rem;
    }
    .admin-sidebar.collapsed ~ .admin-main-content { margin-left: 64px; }
    @media (max-width: 991px) {
      .admin-header { position: fixed; top: 0; left: 0; width: 100vw; z-index: 110; }
      .admin-sidebar { position: fixed; left: -230px; top: 0; height: 100vh; z-index: 120; }
      .admin-sidebar.open { left: 0; }
      #adminSidebarOverlay { display: none; }
      #adminSidebarOverlay.active { display: block; }
      .admin-main-content { margin-left: 0; padding: 1.2rem 0.5rem; padding-top: 60px; }
      .admin-sidebar.collapsed ~ .admin-main-content { margin-left: 0; }
      .admin-widget-grid {
        grid-template-columns: repeat(2, 1fr);
        max-width: 600px;
        margin-top: 3.5rem;
        padding-top: 2.5rem;
      }
    }
    @media (max-width: 600px) {
      .admin-widget-grid {
        grid-template-columns: repeat(2, 1fr);
        max-width: 100%;
        gap: 1rem;
        justify-items: stretch;
      }
    }
    .admin-card {
      background: #fff;
      border-radius: 1.2rem;
      box-shadow: 0 4px 24px rgba(26,147,138,0.10);
      padding: 1rem 0.7rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: box-shadow 0.18s, transform 0.18s;
      min-height: 120px;
    }
    .admin-card:hover {
      box-shadow: 0 8px 32px rgba(26,147,138,0.13);
      transform: translateY(-2px) scale(1.01);
    }
    .admin-card h5 { color: #19376d; font-weight: 700; margin-bottom: 0.5rem; font-size: 1.08rem; }
    .admin-card .admin-card-icon {
      font-size: 1.7rem;
      color: #fff;
      background: linear-gradient(90deg, #1a938a 0%, #ffbf3f 100%);
      border-radius: 50%;
      width: 38px;
      height: 38px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 0.5rem;
      box-shadow: 0 2px 8px rgba(26,147,138,0.10);
    }
    .admin-card .admin-card-stat {
      font-size: 1.08rem;
      font-weight: 700;
      color: #1a938a;
      margin-bottom: 0.2rem;
    }
    .admin-card .admin-card-desc {
      color: #888;
      font-size: 0.97rem;
    }
    footer {
      background: #fff;
      border-top: 1px solid #e9ecef;
      color: #888;
      font-size: 1rem;
      text-align: center;
      padding: 1.2rem 0 0.7rem 0;
      margin-top: 2rem;
      flex-shrink: 0;
      width: 100%;
    }
    .widget-card {
      border-radius: 1.1rem;
      box-shadow: 0 2px 16px rgba(26,147,138,0.10);
      background: #ffbf3f;
      min-height: 90px;
      transition: box-shadow 0.18s, transform 0.18s;
      cursor: pointer;
      position: relative;
    }
    .widget-card:hover {
      box-shadow: 0 6px 32px rgba(26,147,138,0.13);
      transform: translateY(-2px) scale(1.02);
    }
    .widget-icon {
      font-size: 2.2rem;
      color: #fff;
      padding: 0.7rem;
      border-radius: 1rem;
      background: #1a938a;
      box-shadow: 0 2px 8px rgba(26,147,138,0.10);
      min-width: 48px;
      text-align: center;
      display: inline-block;
    }
    .gradient-blue { background: linear-gradient(90deg, #1a938a 60%, #0a174e 100%); color: #fff; }
    .gradient-green { background: linear-gradient(90deg, #ffbf3f 60%, #1a938a 100%); color: #fff; }
    .gradient-purple { background: linear-gradient(90deg, #6f42c1 60%, #1a938a 100%); color: #fff; }
    .gradient-orange { background: linear-gradient(90deg, #fd7e14 60%, #ffbf3f 100%); color: #fff; }
    .widget-label { font-size: 1.05rem; font-weight: 500; opacity: 0.85; }
    .widget-value { font-size: 1.25rem; font-weight: 700; margin-top: 0.2rem; }
    .dashboard-card {
      background: #fff;
      border-radius: 1.2rem;
      box-shadow: 0 4px 24px rgba(26,147,138,0.08);
      padding: 1.5rem 1.2rem;
      margin-bottom: 2rem;
      transition: box-shadow 0.2s;
    }
    .dashboard-card:hover {
      box-shadow: 0 6px 32px rgba(26,147,138,0.12);
    }
    .dashboard-card h5 { color: #1a938a; font-weight: 700; }
    .refresh-btn {
      background: #1a938a;
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s;
      position: absolute;
      top: 1rem;
      right: 1rem;
    }
    .refresh-btn:hover {
      background: #0a174e;
      transform: rotate(180deg);
    }
    .refresh-btn.loading {
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .number-animation {
      transition: all 0.3s ease;
    }
    .number-animation.updating {
      color: #ffbf3f;
      transform: scale(1.1);
    }
          .stats-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        margin-top: 0.5rem;
      }
    .auto-refresh-toggle {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.9rem;
      color: #666;
    }
    .toggle-switch {
      position: relative;
      width: 50px;
      height: 24px;
      background: #ccc;
      border-radius: 12px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .toggle-switch.active {
      background: #1a938a;
    }
    .toggle-switch::after {
      content: '';
      position: absolute;
      top: 2px;
      left: 2px;
      width: 20px;
      height: 20px;
      background: #fff;
      border-radius: 50%;
      transition: transform 0.3s;
    }
    .toggle-switch.active::after {
      transform: translateX(26px);
    }
    .loading-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255,255,255,0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 1.2rem;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s;
    }
    .loading-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    .pulse {
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.5; }
      100% { opacity: 1; }
    }
    @media (min-width: 992px) {
      .widgets-container {
        margin-top: 2.5rem;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <nav class="admin-sidebar" id="adminSidebar">
    <button class="admin-sidebar-toggler d-none d-lg-block" id="adminSidebarToggler" title="Toggle Sidebar"><span class="bi bi-list"></span></button>
    <ul class="nav flex-column">
      <li><a class="nav-link active" href="#"><span class="bi bi-speedometer2"></span> <span class="sidebar-label">Dashboard Overview</span></a></li>
      <li><a class="nav-link" href="#"><span class="bi bi-card-image"></span> <span class="sidebar-label">Gift Cards</span></a></li>
      <li><a class="nav-link" href="#"><span class="bi bi-people"></span> <span class="sidebar-label">Users</span></a></li>
      <li><a class="nav-link" href="#"><span class="bi bi-arrow-left-right"></span> <span class="sidebar-label">Trades</span></a></li>
      <li><a class="nav-link" href="#"><span class="bi bi-bank"></span> <span class="sidebar-label">Bank Accounts</span></a></li>
      <li><a class="nav-link" href="#"><span class="bi bi-box-arrow-right"></span> <span class="sidebar-label">Logout</span></a></li>
    </ul>
  </nav>
  <div id="adminSidebarOverlay" style="display:none;position:fixed;inset:0;z-index:99;background:rgba(10,23,78,0.35);transition:opacity 0.2s;"></div>
  <!-- Header -->
  <header class="admin-header">
    <div class="d-flex align-items-center gap-3 flex-grow-1">
      <button class="btn btn-outline-primary d-lg-none me-2" id="adminMobileSidebarBtn" style="font-size:1.5rem;"><span class="bi bi-list"></span></button>
      <div class="admin-logo flex-grow-1">
        <img src="images/logo.png" alt="Logo" style="height:32px;"> Admin Dashboard
      </div>
      <span class="bi bi-bell" style="font-size:1.3rem;cursor:pointer;" title="Notifications"></span>
      <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="adminUserDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="bi bi-person-circle" style="font-size:1.7rem;color:#19376d;"></span>
          <span class="ms-2 d-none d-md-inline" style="color:#19376d;font-weight:600;">Hi, <?php echo $admin_name; ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminUserDropdown">
          <li><a class="dropdown-item" href="#">Profile</a></li>
          <li><a class="dropdown-item" href="#">Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="#">Logout</a></li>
        </ul>
      </div>
    </div>
  </header>
  <!-- Main Content -->
  <main class="admin-main-content" id="adminMainContent">
    <div class="container-fluid px-0 widgets-container">
      <div class="stats-header">
        <h4 class="mb-0"><span class="bi bi-speedometer2"></span> Dashboard Overview</h4>
        <div class="d-flex align-items-center gap-3">
          <div class="auto-refresh-toggle">
            <span>Auto-refresh</span>
            <div class="toggle-switch" id="autoRefreshToggle"></div>
          </div>
          <button class="refresh-btn" id="refreshStatsBtn" title="Refresh Statistics">
            <span class="bi bi-arrow-clockwise"></span>
          </button>
        </div>
      </div>
      <div class="row g-4 mb-2">
        <div class="col-md-3">
          <div class="widget-card gradient-blue d-flex align-items-center gap-3 p-3 h-100 position-relative">
            <div class="loading-overlay" id="usersLoading">
              <div class="spinner-border text-primary" role="status"></div>
            </div>
            <span class="bi bi-people widget-icon"></span>
            <div>
              <div class="widget-label">Total Users</div>
              <div class="widget-value number-animation" id="usersCount"><?php echo $total_users; ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="widget-card gradient-green d-flex align-items-center gap-3 p-3 h-100 position-relative">
            <div class="loading-overlay" id="tradesLoading">
              <div class="spinner-border text-success" role="status"></div>
            </div>
            <span class="bi bi-arrow-left-right widget-icon"></span>
            <div>
              <div class="widget-label">Total Trades</div>
              <div class="widget-value number-animation" id="tradesCount"><?php echo $total_trades; ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <a href="admin_giftcard_types.php" style="text-decoration:none;">
            <div class="widget-card gradient-purple d-flex align-items-center gap-3 p-3 h-100 position-relative">
              <div class="loading-overlay" id="giftcardLoading">
                <div class="spinner-border text-warning" role="status"></div>
              </div>
              <span class="bi bi-gift widget-icon"></span>
              <div>
                <div class="widget-label">Gift Card Types</div>
                <div class="widget-value number-animation" id="giftcardCount"><?php echo $total_giftcards; ?></div>
              </div>
            </div>
          </a>
        </div>
        <div class="col-md-3">
          <a href="admin_bank_accounts.php" style="text-decoration:none;">
            <div class="widget-card gradient-orange d-flex align-items-center gap-3 p-3 h-100 position-relative">
              <div class="loading-overlay" id="bankLoading">
                <div class="spinner-border text-info" role="status"></div>
              </div>
              <span class="bi bi-bank widget-icon"></span>
              <div>
                <div class="widget-label">Bank Accounts</div>
                <div class="widget-value number-animation" id="bankCount"><?php echo $total_bank_accounts; ?></div>
              </div>
            </div>
          </a>
        </div>
      </div>
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="dashboard-card position-relative">
            <div class="loading-overlay" id="usersListLoading">
              <div class="spinner-border text-primary" role="status"></div>
            </div>
            <h5 class="mb-3"><span class="bi bi-person-plus"></span> Recent Users</h5>
            <div id="recentUsersList">
              <ul class="list-group list-group-flush">
                <?php foreach ($recent_users as $user): ?>
                  <li class="list-group-item d-flex align-items-center justify-content-between">
                    <span><strong><?php echo htmlspecialchars($user['name']); ?></strong> <span class="text-muted">(<?php echo htmlspecialchars($user['email']); ?>)</span></span>
                    <span class="badge bg-primary">Joined <?php echo date('M d', strtotime($user['created_at'])); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="dashboard-card position-relative">
            <div class="loading-overlay" id="tradesListLoading">
              <div class="spinner-border text-success" role="status"></div>
            </div>
            <h5 class="mb-3"><span class="bi bi-arrow-left-right"></span> Recent Trades</h5>
            <div id="recentTradesList">
              <ul class="list-group list-group-flush">
                <?php while ($row = $recent_btc->fetch_assoc()): ?>
                  <li class="list-group-item d-flex align-items-center justify-content-between">
                    <span><span class="badge bg-info">BTC</span> User #<?php echo $row['user_id']; ?>: $<?php echo $row['amount']; ?> (<?php echo htmlspecialchars($row['status']); ?>)</span>
                    <span class="text-muted"><?php echo date('M d', strtotime($row['date'])); ?></span>
                  </li>
                <?php endwhile; ?>
                <?php while ($row = $recent_giftcard->fetch_assoc()): ?>
                  <li class="list-group-item d-flex align-items-center justify-content-between">
                    <span><span class="badge bg-warning text-dark">Giftcard</span> User #<?php echo $row['user_id']; ?>: <?php echo htmlspecialchars($row['card_type']); ?> ₦<?php echo $row['amount']; ?> (<?php echo htmlspecialchars($row['status']); ?>)</span>
                    <span class="text-muted"><?php echo date('M d', strtotime($row['date'])); ?></span>
                  </li>
                <?php endwhile; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <div class="row g-4 mt-2">
        <div class="col-12">
          <div class="dashboard-card">
            <h5 class="mb-3"><span class="bi bi-bar-chart"></span> Live Crypto Market Prices</h5>
            <div class="crypto-widget-embed" style="min-height:90px;">
              <iframe src="https://widget.coinlib.io/widget?type=full_v2&theme=light&cnt=5&pref_coin_id=1505" width="100%" height="196px" scrolling="auto" marginwidth="0" marginheight="0" frameborder="0" style="border:0;margin:0;padding:0;"></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  <!-- Footer -->
  <footer>
    &copy; <?php echo date('Y'); ?> Giftcard & Bitcoin Trading Admin. All Rights Reserved. &middot;
    <a href="#" class="text-decoration-none text-primary">Privacy Policy</a> &middot;
    <a href="#" class="text-decoration-none text-primary">Terms of Service</a> &middot;
    <a href="#" class="text-decoration-none text-primary">Contact</a>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle
    const adminSidebar = document.getElementById('adminSidebar');
    const adminSidebarToggler = document.getElementById('adminSidebarToggler');
    const adminMobileSidebarBtn = document.getElementById('adminMobileSidebarBtn');
    const adminSidebarOverlay = document.getElementById('adminSidebarOverlay');
    const adminMainContent = document.getElementById('adminMainContent');
    if (adminSidebarToggler) {
      adminSidebarToggler.addEventListener('click', function() {
        adminSidebar.classList.toggle('collapsed');
        document.querySelector('.admin-main-content').classList.toggle('collapsed');
      });
    }
    if (adminMobileSidebarBtn) {
      adminMobileSidebarBtn.addEventListener('click', function() {
        adminSidebar.classList.add('open');
        adminSidebarOverlay.classList.add('active');
      });
    }
    if (adminSidebarOverlay) {
      adminSidebarOverlay.addEventListener('click', function() {
        adminSidebar.classList.remove('open');
        adminSidebarOverlay.classList.remove('active');
      });
    }
    // Close sidebar when clicking outside (on overlay or main content)
    if (adminMainContent) {
      adminMainContent.addEventListener('click', function() {
        if (window.innerWidth < 992 && adminSidebar.classList.contains('open')) {
          adminSidebar.classList.remove('open');
          adminSidebarOverlay.classList.remove('active');
        }
      });
    }

    // Enhanced Interactivity
    let autoRefreshInterval;
    let isAutoRefreshActive = false;

    // Animate number changes
    function animateNumber(element, newValue, duration = 1000) {
      const startValue = parseInt(element.textContent) || 0;
      const increment = (newValue - startValue) / (duration / 16);
      let currentValue = startValue;
      
      element.classList.add('updating');
      
      const timer = setInterval(() => {
        currentValue += increment;
        if ((increment > 0 && currentValue >= newValue) || (increment < 0 && currentValue <= newValue)) {
          element.textContent = newValue;
          element.classList.remove('updating');
          clearInterval(timer);
        } else {
          element.textContent = Math.floor(currentValue);
        }
      }, 16);
    }

    // Show loading state
    function showLoading(elementId) {
      const element = document.getElementById(elementId);
      if (element) {
        element.classList.add('active');
      }
    }

    // Hide loading state
    function hideLoading(elementId) {
      const element = document.getElementById(elementId);
      if (element) {
        element.classList.remove('active');
      }
    }

    // Refresh statistics via AJAX
    async function refreshStats() {
      const refreshBtn = document.getElementById('refreshStatsBtn');
      refreshBtn.classList.add('loading');
      
      // Show loading states
      showLoading('usersLoading');
      showLoading('tradesLoading');
      showLoading('giftcardLoading');
      showLoading('bankLoading');
      showLoading('usersListLoading');
      showLoading('tradesListLoading');

      try {
        const response = await fetch('admin_stats.php');
        const data = await response.json();
        
        if (data.success) {
          // Animate number changes
          animateNumber(document.getElementById('usersCount'), data.stats.total_users);
          animateNumber(document.getElementById('tradesCount'), data.stats.total_trades);
          animateNumber(document.getElementById('giftcardCount'), data.stats.total_giftcards);
          animateNumber(document.getElementById('bankCount'), data.stats.total_bank_accounts);
          
          // Update recent users list
          const usersList = document.getElementById('recentUsersList');
          usersList.innerHTML = data.recent_users;
          
          // Update recent trades list
          const tradesList = document.getElementById('recentTradesList');
          tradesList.innerHTML = data.recent_trades;
          
          // Show success notification
          showNotification('Statistics updated successfully!', 'success');
        } else {
          showNotification('Failed to update statistics', 'error');
        }
      } catch (error) {
        console.error('Error refreshing stats:', error);
        showNotification('Error updating statistics', 'error');
      } finally {
        // Hide loading states
        hideLoading('usersLoading');
        hideLoading('tradesLoading');
        hideLoading('giftcardLoading');
        hideLoading('bankLoading');
        hideLoading('usersListLoading');
        hideLoading('tradesListLoading');
        refreshBtn.classList.remove('loading');
      }
    }

    // Show notification
    function showNotification(message, type = 'info') {
      const notification = document.createElement('div');
      notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
      notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
      notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      document.body.appendChild(notification);
      
      // Auto remove after 5 seconds
      setTimeout(() => {
        if (notification.parentNode) {
          notification.remove();
        }
      }, 5000);
    }

    // Auto-refresh toggle
    const autoRefreshToggle = document.getElementById('autoRefreshToggle');
    if (autoRefreshToggle) {
      autoRefreshToggle.addEventListener('click', function() {
        isAutoRefreshActive = !isAutoRefreshActive;
        this.classList.toggle('active');
        
        if (isAutoRefreshActive) {
          autoRefreshInterval = setInterval(refreshStats, 30000); // Refresh every 30 seconds
          showNotification('Auto-refresh enabled (30s interval)', 'success');
        } else {
          clearInterval(autoRefreshInterval);
          showNotification('Auto-refresh disabled', 'info');
        }
      });
    }

    // Manual refresh button
    const refreshStatsBtn = document.getElementById('refreshStatsBtn');
    if (refreshStatsBtn) {
      refreshStatsBtn.addEventListener('click', refreshStats);
    }

    // Add pulse animation to widgets on hover
    document.querySelectorAll('.widget-card').forEach(card => {
      card.addEventListener('mouseenter', function() {
        this.querySelector('.widget-icon').classList.add('pulse');
      });
      
      card.addEventListener('mouseleave', function() {
        this.querySelector('.widget-icon').classList.remove('pulse');
      });
    });

    // Initialize with a welcome notification
    setTimeout(() => {
      showNotification('Welcome to Admin Dashboard! Statistics are live and updating.', 'success');
    }, 1000);
  </script>
</body>
</html> 