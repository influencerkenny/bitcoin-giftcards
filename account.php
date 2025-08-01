<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}
require_once 'db.php';
$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Account | Bitcoin Giftcards</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { background: linear-gradient(252deg, #1a938a 0%, rgba(26, 147, 138, 0) 100.44%); min-height: 100vh; color: #19376d; }
    
    /* Mobile sidebar improvements */
    @media (max-width: 991px) {
        .dashboard-header { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100vw; 
            z-index: 1001; 
        }
        .sidebar { 
            position: fixed; 
            left: -220px; 
            top: 64px; 
            height: 100vh; 
            z-index: 1000; 
            transition: left 0.3s ease; 
        }
        .sidebar.show { 
            left: 0; 
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        #sidebarOverlay { 
            z-index: 999;
            background: rgba(0,0,0,0.5);
        }
        .main-content { 
            margin-left: 0; 
            padding: 1rem; 
            padding-top: 80px; 
        }
        .sidebar.collapsed ~ .main-content { 
            margin-left: 0; 
        }
        
        /* Mobile sidebar improvements */
        .sidebar-toggler {
            display: none; /* Hide desktop toggle on mobile */
        }
        
        .sidebar {
            top: 64px; /* Ensure it starts below header */
            height: calc(100vh - 64px);
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            padding: 1rem;
            font-size: 1rem;
        }
        
        .sidebar .nav-link span:first-child {
            font-size: 1.3rem;
        }
        
        /* Mobile sidebar button improvements */
        #mobileSidebarBtn {
            transition: all 0.2s ease;
        }
        
        #mobileSidebarBtn:hover {
            transform: scale(1.1);
            background-color: #1a938a;
            border-color: #1a938a;
            color: white;
        }
        
        #mobileSidebarBtn:active {
            transform: scale(0.95);
        }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
    <button class="sidebar-toggler" id="sidebarToggler" title="Toggle Sidebar"><span class="bi bi-list"></span></button>
    <ul class="nav flex-column">
      <li><a class="nav-link" href="dashboard.php"><span class="bi bi-house"></span> <span class="sidebar-label">Dashboard</span></a></li>
      <li><a class="nav-link" href="bank_account.php"><span class="bi bi-bank"></span> <span class="sidebar-label">Bank Account</span></a></li>
      <li><a class="nav-link" href="giftcard_trade.php"><span class="bi bi-gift"></span> <span class="sidebar-label">Sell Giftcard</span></a></li>
      <li><a class="nav-link" href="bitcoin_trade.php"><span class="bi bi-currency-bitcoin"></span> <span class="sidebar-label">Buy/Sell Bitcoin</span></a></li>
      <li><a class="nav-link" href="support.php"><span class="bi bi-life-preserver"></span> <span class="sidebar-label">Support</span></a></li>
      <li><a class="nav-link active" href="account.php"><span class="bi bi-person"></span> <span class="sidebar-label">Account</span></a></li>
      <li><a class="nav-link" href="security.php"><span class="bi bi-shield-lock"></span> <span class="sidebar-label">Security</span></a></li>
      <li><a class="nav-link" href="logout.php"><span class="bi bi-box-arrow-right"></span> <span class="sidebar-label">Logout</span></a></li>
    </ul>
  </nav>
  <div id="sidebarOverlay" style="display:none;position:fixed;inset:0;z-index:99;background:rgba(10,23,78,0.35);transition:opacity 0.2s;"></div>
  <!-- Header -->
  <header class="dashboard-header">
    <div class="d-flex align-items-center gap-3 flex-grow-1">
      <button class="btn btn-outline-primary d-lg-none me-2" id="mobileSidebarBtn" style="font-size:1.5rem; transition: all 0.2s ease;"><span class="bi bi-list"></span></button>
      <div class="dashboard-logo flex-grow-1">
        <img src="images/logo.png" alt="Logo" style="height:32px;"> Giftcard & Bitcoin
      </div>
      <span class="bi bi-bell" style="font-size:1.3rem;cursor:pointer;" title="Notifications"></span>
      <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="bi bi-person-circle" style="font-size:1.7rem;color:#19376d;"></span>
          <span class="ms-2 d-none d-md-inline" style="color:#19376d;font-weight:600;">Hi, <?php echo $user_name; ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
          <li><a class="dropdown-item" href="account.php">Account</a></li>
          <li><a class="dropdown-item" href="security.php">Security</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </header>
  <!-- Main Content -->
  <main class="main-content" id="mainContent">
    <div class="container" style="max-width:600px;margin:48px auto 0 auto;background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(26,147,138,0.08);padding:32px 24px;text-align:center;">
      <h2><i class="bi bi-person"></i> Account</h2>
      <p>Your account details and settings will be available here soon.</p>
    </div>
  </main>
  <!-- Footer -->
  <footer>
    &copy; <?php echo date('Y'); ?> Giftcard & Bitcoin Trading. All Rights Reserved. &middot;
    <a href="#" class="text-decoration-none text-primary">Privacy Policy</a> &middot;
    <a href="#" class="text-decoration-none text-primary">Terms of Service</a> &middot;
    <a href="#" class="text-decoration-none text-primary">Contact</a>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarToggler = document.getElementById('sidebarToggler');
    const mainContent = document.getElementById('mainContent');
    const mobileSidebarBtn = document.getElementById('mobileSidebarBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    sidebarToggler.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
    });

    // Mobile sidebar toggle
    mobileSidebarBtn.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        sidebarOverlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
        
        // Add visual feedback
        if (sidebar.classList.contains('show')) {
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        } else {
            document.body.style.overflow = ''; // Restore scrolling
        }
    });

    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('show');
        sidebarOverlay.style.display = 'none';
        document.body.style.overflow = ''; // Restore scrolling
    });

    // Close sidebar when clicking on navigation links (mobile)
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                sidebar.classList.remove('show');
                sidebarOverlay.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
            }
        });
    });

    // Close sidebar when clicking outside (on main content)
    mainContent.addEventListener('click', function() {
        if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            sidebarOverlay.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
        }
    });

    // Close sidebar on window resize if switching to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove('show');
            sidebarOverlay.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
        }
    });
  </script>
</body>
</html> 