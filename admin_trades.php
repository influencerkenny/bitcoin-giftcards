<?php
session_start();
require_once 'db.php';

// Check if admin is logged in (you may want to add proper admin authentication)
// For now, we'll assume admin access

// Fetch all giftcard transactions with user details
$giftcard_transactions = [];
$giftcard_query = "
    SELECT 
        gt.id,
        gt.user_id,
        u.name as user_name,
        u.email as user_email,
        gt.card_type,
        gt.amount,
        gt.status,
        gt.date,
        gt.card_image,
        gr.rate
    FROM giftcard_transactions gt
    LEFT JOIN users u ON gt.user_id = u.id
    LEFT JOIN giftcard_rates gr ON gt.card_type = gr.card_name
    ORDER BY gt.date DESC
";
$giftcard_result = $db->query($giftcard_query);
if ($giftcard_result) {
    while ($row = $giftcard_result->fetch_assoc()) {
        $giftcard_transactions[] = $row;
    }
    $giftcard_result->close();
}

// Fetch all bitcoin transactions with user details
$bitcoin_transactions = [];
$bitcoin_query = "
    SELECT 
        bt.id,
        bt.user_id,
        u.name as user_name,
        u.email as user_email,
        bt.amount,
        bt.status,
        bt.date,
        bt.txid
    FROM btc_transactions bt
    LEFT JOIN users u ON bt.user_id = u.id
    ORDER BY bt.date DESC
";
$bitcoin_result = $db->query($bitcoin_query);
if ($bitcoin_result) {
    while ($row = $bitcoin_result->fetch_assoc()) {
        $bitcoin_transactions[] = $row;
    }
    $bitcoin_result->close();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_giftcard_status'])) {
        $transaction_id = intval($_POST['transaction_id']);
        $new_status = $_POST['new_status'];
        
        // First, get the transaction details to calculate the payout
        $get_transaction_stmt = $db->prepare('SELECT user_id, amount, card_type FROM giftcard_transactions WHERE id = ?');
        $get_transaction_stmt->bind_param('i', $transaction_id);
        $get_transaction_stmt->execute();
        $get_transaction_stmt->bind_result($user_id, $amount, $card_type);
        $get_transaction_stmt->fetch();
        $get_transaction_stmt->close();
        
        // Update the transaction status
        $update_stmt = $db->prepare('UPDATE giftcard_transactions SET status = ? WHERE id = ?');
        $update_stmt->bind_param('si', $new_status, $transaction_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // If the trade is approved (Completed), update user's balance
        if ($new_status === 'Completed') {
            // Get the rate for this card type
            $get_rate_stmt = $db->prepare('SELECT rate FROM giftcard_rates WHERE card_name = ?');
            $get_rate_stmt->bind_param('s', $card_type);
            $get_rate_stmt->execute();
            $get_rate_stmt->bind_result($rate);
            $get_rate_stmt->fetch();
            $get_rate_stmt->close();
            
            if ($rate) {
                // Calculate the payout amount (USD amount * Naira rate)
                // Example: $100 * ₦240 = ₦24,000
                $payout_amount = $amount * $rate;
                
                // Update user's balance
                $update_balance_stmt = $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
                $update_balance_stmt->bind_param('di', $payout_amount, $user_id);
                $update_balance_stmt->execute();
                $update_balance_stmt->close();
            }
        }
        
        // Set success message
        $_SESSION['admin_message'] = "Giftcard trade status updated successfully. User balance has been updated.";
        header('Location: admin_trades.php');
        exit();
    }
    
    if (isset($_POST['update_bitcoin_status'])) {
        $transaction_id = intval($_POST['transaction_id']);
        $new_status = $_POST['new_status'];
        
        // First, get the transaction details
        $get_transaction_stmt = $db->prepare('SELECT user_id, amount FROM btc_transactions WHERE id = ?');
        $get_transaction_stmt->bind_param('i', $transaction_id);
        $get_transaction_stmt->execute();
        $get_transaction_stmt->bind_result($user_id, $amount);
        $get_transaction_stmt->fetch();
        $get_transaction_stmt->close();
        
        // Update the transaction status
        $update_stmt = $db->prepare('UPDATE btc_transactions SET status = ? WHERE id = ?');
        $update_stmt->bind_param('si', $new_status, $transaction_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // If the trade is approved (Completed), update user's balance
        if ($new_status === 'Completed') {
            // For bitcoin trades, we need to determine if it's buy or sell
            // For now, we'll assume it's a sell (user selling BTC for Naira)
            // You may need to adjust this logic based on your business rules
            
            // Get current BTC sell rate from rates table
            $btc_sell_rate = null;
            $rates_result = $db->query("SELECT value FROM rates WHERE type = 'btc_sell'");
            if ($rates_result && $rates_row = $rates_result->fetch_assoc()) {
                $btc_sell_rate = $rates_row['value'];
            }
            $rates_result->close();
            
            // Use default rate if not found in database
            if (!$btc_sell_rate) {
                $btc_sell_rate = 50000; // Default rate in Naira
            }
            
            // Calculate the payout amount (BTC amount * BTC sell rate)
            $payout_amount = $amount * $btc_sell_rate;
            
            // Update user's balance
            $update_balance_stmt = $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
            $update_balance_stmt->bind_param('di', $payout_amount, $user_id);
            $update_balance_stmt->execute();
            $update_balance_stmt->close();
        }
        
        // Set success message
        $_SESSION['admin_message'] = "Bitcoin trade status updated successfully. User balance has been updated.";
        header('Location: admin_trades.php');
        exit();
    }
}

// Get statistics
$total_giftcard_trades = count($giftcard_transactions);
$total_bitcoin_trades = count($bitcoin_transactions);
$pending_giftcard_trades = count(array_filter($giftcard_transactions, function($t) { return $t['status'] === 'Processing'; }));
$pending_bitcoin_trades = count(array_filter($bitcoin_transactions, function($t) { return $t['status'] === 'Processing'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Trades | Bitcoin Giftcards</title>
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
      min-height: 20vh;
      transition: margin-left 0.2s;
      flex: 1 0 auto;
      margin-top: 110%;
    }
    .admin-sidebar.collapsed ~ .admin-main-content { margin-left: 64px; }
    /* --- Add unified width for main content card, matching admin_giftcard_types.php --- */
    .dashboard-card {
      background: #fff;
      border-radius: 1.2rem;
      box-shadow: 0 4px 24px rgba(26,147,138,0.08);
      padding: 1.5rem 1.2rem;
      margin-bottom: 2rem;
      transition: box-shadow 0.2s;
      width: 100%;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }
    .dashboard-card:hover { box-shadow: 0 6px 32px rgba(26,147,138,0.12); }
    
    @media (max-width: 991px) {
      .admin-header { position: fixed; top: 0; left: 0; width: 100vw; z-index: 110; }
      .admin-sidebar { position: fixed; left: -230px; top: 0; height: 100vh; z-index: 120; }
      .admin-sidebar.open { left: 0; }
      #adminSidebarOverlay { display: none; }
      #adminSidebarOverlay.active { display: block; }
      .admin-main-content {
        margin-left: 0;
        padding: 1.2rem 0.5rem;
        padding-top: 460%;
        margin-top: 300%;
      }
      .admin-sidebar.collapsed ~ .admin-main-content { margin-left: 0; }
    }
    @media (max-width: 767px) {
      .stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
      .trades-section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 1rem 1rem;
      }
      .stat-card {
        padding: 1rem;
        font-size: 0.97rem;
      }
      .table th, .table td {
        padding: 0.7rem 0.5rem;
        font-size: 0.95rem;
      }
      .admin-main-content {
        margin-top: 220% !important;
      }
      /* Hide tables on mobile */
      .table-responsive {
        display: none;
      }
      
      /* Show mobile cards */
      .mobile-cards {
        display: block;
        padding: 1rem;
      }
      
      /* Mobile card styles */
      .trade-card {
        background: #fff;
        border-radius: 1rem;
        padding: 1.2rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 12px rgba(26,147,138,0.08);
        border: 1px solid #f1f3f4;
        transition: transform 0.2s, box-shadow 0.2s;
      }
      
      .trade-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(26,147,138,0.12);
      }
      
      .trade-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
        padding-bottom: 0.8rem;
        border-bottom: 1px solid #f1f3f4;
      }
      
      .trade-card-user {
        flex: 1;
      }
      
      .trade-card-user-name {
        font-weight: 600;
        color: #19376d;
        font-size: 1rem;
        margin-bottom: 0.2rem;
      }
      
      .trade-card-user-email {
        font-size: 0.85rem;
        color: #666;
      }
      
      .trade-card-status {
        margin-left: 0.5rem;
      }
      
      .trade-card-body {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
      }
      
      .trade-card-item {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
      }
      
      .trade-card-label {
        font-size: 0.8rem;
        color: #666;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
      
      .trade-card-value {
        font-weight: 600;
        color: #19376d;
        font-size: 0.95rem;
      }
      
      .trade-card-amount {
        color: #1a938a;
        font-size: 1.1rem;
      }
      
      .trade-card-payout {
        font-weight: 700;
        color: #28a745;
        font-size: 1.1rem;
      }
      
      .trade-card-image {
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      
      .trade-card-image img {
        width: 40px;
        height: 28px;
        object-fit: cover;
        border-radius: 0.3rem;
        border: 1px solid #e9ecef;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      }
      
      .trade-card-value code {
        background: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 0.3rem;
        border: 1px solid #e9ecef;
        font-family: 'Courier New', monospace;
      }
      
      .trade-card-item:nth-child(even) {
        background: rgba(248, 249, 250, 0.5);
        border-radius: 0.5rem;
        padding: 0.3rem 0.5rem;
        margin: 0.2rem -0.5rem;
      }
      
      .trade-card-processing-time {
        color: #6c757d;
        font-size: 0.9rem;
        font-style: italic;
      }
      
      .trade-card-user-id {
        background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
        border: 1px solid #bbdefb;
        color: #1976d2;
        font-weight: 600;
      }
      
      .trade-card-actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
        padding-top: 0.8rem;
        border-top: 1px solid #f1f3f4;
      }
      
      .trade-card-date {
        font-size: 0.85rem;
        color: #666;
        text-align: right;
      }
    }
    
    /* Hide mobile cards on desktop */
    @media (min-width: 768px) {
      .mobile-cards {
        display: none;
      }
    }
    
    .table-responsive {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 575px) {
      .admin-header, .admin-main-content {
        padding-left: 0.3rem;
        padding-right: 0.3rem;
      }
      .trades-section-header h3 {
        font-size: 1.05rem;
      }
      .stat-card .stat-number {
        font-size: 1.2rem;
      }
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    .stat-card {
      background: #fff;
      border-radius: 1.2rem;
      padding: 1.5rem;
      box-shadow: 0 4px 24px rgba(26,147,138,0.10);
      text-align: center;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 32px rgba(26,147,138,0.15);
    }
    .stat-card .stat-icon {
      font-size: 2rem;
      color: #1a938a;
      margin-bottom: 0.5rem;
    }
    .stat-card .stat-number {
      font-size: 1.8rem;
      font-weight: 700;
      color: #19376d;
      margin-bottom: 0.25rem;
    }
    .stat-card .stat-label {
      color: #666;
      font-size: 0.9rem;
      font-weight: 500;
    }
    
    .trades-section {
      background: #fff;
      border-radius: 1.2rem;
      box-shadow: 0 4px 24px rgba(26,147,138,0.10);
      margin-bottom: 2rem;
      overflow: hidden;
    }
    .trades-section-header {
      background: linear-gradient(90deg, #1a938a 0%, #19376d 100%);
      color: #fff;
      padding: 1.5rem 2rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .trades-section-header h3 {
      margin: 0;
      font-weight: 600;
      font-size: 1.3rem;
    }
    .trades-section-header .bi {
      font-size: 1.5rem;
    }
    
    .table-responsive {
      border-radius: 0;
    }
    .table {
      margin: 0;
      border-collapse: separate;
      border-spacing: 0;
    }
    .table th {
      background: #f8f9fa;
      border: none;
      padding: 1rem 1.5rem;
      font-weight: 600;
      color: #19376d;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .table td {
      border: none;
      padding: 1rem 1.5rem;
      vertical-align: middle;
      border-bottom: 1px solid #f1f3f4;
    }
    .table tbody tr:hover {
      background: #f8f9fa;
    }
    
    .status-badge {
      padding: 0.4rem 0.8rem;
      border-radius: 2rem;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .status-processing {
      background: #fff3cd;
      color: #856404;
    }
    .status-completed {
      background: #d1edff;
      color: #0c5460;
    }
    .status-rejected {
      background: #f8d7da;
      color: #721c24;
    }
    .status-pending {
      background: #fff3cd;
      color: #856404;
    }
    
    .action-btn {
      padding: 0.4rem 0.8rem;
      border: none;
      border-radius: 0.5rem;
      font-size: 0.8rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-block;
    }
    .action-btn-success {
      background: #28a745;
      color: #fff;
    }
    .action-btn-success:hover {
      background: #218838;
      color: #fff;
    }
    .action-btn-danger {
      background: #dc3545;
      color: #fff;
    }
    .action-btn-danger:hover {
      background: #c82333;
      color: #fff;
    }
    .action-btn-warning {
      background: #ffc107;
      color: #212529;
    }
    .action-btn-warning:hover {
      background: #e0a800;
      color: #212529;
    }
    
    .user-info {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }
    .user-name {
      font-weight: 600;
      color: #19376d;
    }
    .user-email {
      font-size: 0.8rem;
      color: #666;
    }
    
    .amount-display {
      font-weight: 600;
      color: #1a938a;
    }
    
    .date-display {
      font-size: 0.85rem;
      color: #666;
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
  </style>
</head>
<body>
  <!-- Admin Header -->
  <header class="admin-header">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-outline-primary d-lg-none me-2" id="mobileSidebarBtn" style="font-size:1.5rem;"><span class="bi bi-list"></span></button>
      <div class="admin-logo">
        <span class="bi bi-currency-bitcoin"></span>
        <span class="sidebar-label">Admin Panel</span>
      </div>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="bi bi-bell" style="font-size:1.3rem;cursor:pointer;" title="Notifications"></span>
      <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="bi bi-person-circle" style="font-size:1.7rem;color:#19376d;"></span>
          <span class="ms-2 d-none d-md-inline" style="color:#19376d;font-weight:600;">Admin</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
          <li><a class="dropdown-item" href="admin_dashboard.php">Dashboard</a></li>
          <li><a class="dropdown-item" href="admin_stats.php">Statistics</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- Admin Sidebar -->
  <nav class="admin-sidebar" id="adminSidebar">
    <button class="admin-sidebar-toggler" id="adminSidebarToggler" title="Toggle Sidebar">
      <span class="bi bi-list"></span>
    </button>
    <ul class="nav flex-column">
      <li><a class="nav-link" href="admin_dashboard.php"><span class="bi bi-house"></span> <span class="sidebar-label">Dashboard</span></a></li>
      <li><a class="nav-link" href="admin_stats.php"><span class="bi bi-graph-up"></span> <span class="sidebar-label">Statistics</span></a></li>
      <li><a class="nav-link active" href="admin_trades.php"><span class="bi bi-currency-exchange"></span> <span class="sidebar-label">Trades</span></a></li>
      <li><a class="nav-link" href="admin_giftcard_types.php"><span class="bi bi-gift"></span> <span class="sidebar-label">Giftcard Types</span></a></li>
      <li><a class="nav-link" href="admin_bank_accounts.php"><span class="bi bi-bank"></span> <span class="sidebar-label">Bank Accounts</span></a></li>
    </ul>
  </nav>
  <div id="adminSidebarOverlay" style="display:none;position:fixed;inset:0;z-index:99;background:rgba(10,23,78,0.35);transition:opacity 0.2s;"></div>

  <!-- Main Content -->
  <main class="admin-main-content">
    <div class="container-fluid">
      <!-- Success Message -->
      <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <span class="bi bi-check-circle me-2"></span>
          <?php echo htmlspecialchars($_SESSION['admin_message']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['admin_message']); ?>
      <?php endif; ?>
      <!-- Page Header and Statistics Cards -->
      <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h1 class="h3 mb-1" style="color: #19376d; font-weight: 700;">Trade Management</h1>
            <p class="text-muted mb-0">Manage all giftcard and bitcoin transactions</p>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="exportTrades()">
              <span class="bi bi-download"></span> Export
            </button>
            <button class="btn btn-primary" onclick="refreshPage()">
              <span class="bi bi-arrow-clockwise"></span> Refresh
            </button>
          </div>
        </div>
        <!-- Statistics Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon"><span class="bi bi-gift"></span></div>
            <div class="stat-number"><?php echo $total_giftcard_trades; ?></div>
            <div class="stat-label">Total Giftcard Trades</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon"><span class="bi bi-currency-bitcoin"></span></div>
            <div class="stat-number"><?php echo $total_bitcoin_trades; ?></div>
            <div class="stat-label">Total Bitcoin Trades</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon"><span class="bi bi-clock"></span></div>
            <div class="stat-number"><?php echo $pending_giftcard_trades + $pending_bitcoin_trades; ?></div>
            <div class="stat-label">Pending Trades</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon"><span class="bi bi-check-circle"></span></div>
            <div class="stat-number"><?php echo ($total_giftcard_trades + $total_bitcoin_trades) - ($pending_giftcard_trades + $pending_bitcoin_trades); ?></div>
            <div class="stat-label">Completed Trades</div>
          </div>
        </div>
      </div>
      <!-- Giftcard Trades Section -->
      <div class="dashboard-card" style="max-width:1000px;margin-left:auto;margin-right:auto;">
        <div class="trades-section">
          <div class="trades-section-header">
            <span class="bi bi-gift"></span>
            <h3 style="font-size:1.15rem;">Giftcard Trades</h3>
            <span class="badge bg-light text-dark ms-auto" style="font-size:0.95rem;"><?php echo $total_giftcard_trades; ?> trades</span>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered align-middle bg-white" style="border-radius:1.1rem;overflow:hidden;font-size:0.93rem;min-width:600px;">
              <thead class="table-light">
                <tr>
                  <th style="font-size:0.97rem;">User</th>
                  <th style="font-size:0.97rem;">Giftcard</th>
                  <th style="font-size:0.97rem;">Card Image</th>
                  <th style="font-size:0.97rem;">Date</th>
                  <th style="font-size:0.97rem;">Amount (USD)</th>
                  <th style="font-size:0.97rem;">You Get (₦)</th>
                  <th style="font-size:0.97rem;">Status</th>
                  <th style="font-size:0.97rem;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($giftcard_transactions)): ?>
                  <tr>
                    <td colspan="8" class="text-center py-4 text-muted" style="font-size:0.95rem;">
                      <span class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                      No giftcard trades found
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($giftcard_transactions as $trade): ?>
                  <tr style="font-size:0.93rem;">
                    <td>
                      <div style="line-height:1.2;">
                        <span style="font-weight:600;font-size:0.98em;"> <?php echo htmlspecialchars($trade['user_name']); ?> </span><br>
                        <span style="color:#888;font-size:0.92em;"> <?php echo htmlspecialchars($trade['user_email']); ?> </span>
                      </div>
                    </td>
                    <td style="font-weight:600;min-width:90px;"> <?php echo htmlspecialchars($trade['card_type']); ?> </td>
                    <td>
                      <?php if (!empty($trade['card_image'])): ?>
                        <img src="<?php echo htmlspecialchars($trade['card_image']); ?>" alt="Card Image" style="width:44px;height:30px;object-fit:cover;border-radius:0.3rem;cursor:pointer;" onclick="openImageModal('<?php echo htmlspecialchars($trade['card_image']); ?>')">
                      <?php else: ?>
                        <span class="text-muted" style="font-size:0.92em;">No image</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted" style="font-size:0.92em;"> <?php echo htmlspecialchars($trade['date']); ?> </td>
                    <td class="fw-bold" style="color:#19376d;font-size:0.97em;">$<?php echo number_format($trade['amount'],2); ?></td>
                    <td class="fw-bold text-success" style="font-size:0.97em;">₦<?php echo ($trade['rate'] ? number_format($trade['amount'] * $trade['rate'],2) : '0.00'); ?></td>
                    <td>
                      <span class="badge" style="font-size:0.93em;min-width:90px;
                        <?php if ($trade['status'] === 'Processing') echo 'background:#fff3cd;color:#856404;';
                              elseif ($trade['status'] === 'Completed' || $trade['status'] === 'Paid Out') echo 'background:#d4edda;color:#155724;';
                              elseif ($trade['status'] === 'Declined' || $trade['status'] === 'Rejected') echo 'background:#f8d7da;color:#721c24;';
                        ?>">
                        <?php if ($trade['status'] === 'Processing'): ?>
                          <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($trade['status']); ?>
                      </span>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <?php if ($trade['status'] === 'Processing'): ?>
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                            <input type="hidden" name="new_status" value="Completed">
                            <button type="submit" name="update_giftcard_status" class="action-btn action-btn-success" 
                                    title="Approve trade and add ₦<?php echo number_format($trade['amount'] * ($trade['rate'] ?? 0), 2); ?> to user balance">
                              <span class="bi bi-check"></span>
                            </button>
                          </form>
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                            <input type="hidden" name="new_status" value="Rejected">
                            <button type="submit" name="update_giftcard_status" class="action-btn action-btn-danger" title="Reject Trade">
                              <span class="bi bi-x"></span>
                            </button>
                          </form>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          
          <!-- Mobile Card View -->
          <div class="mobile-cards">
            <?php if (empty($giftcard_transactions)): ?>
              <div class="text-center py-4 text-muted">
                <span class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                No giftcard trades found
              </div>
            <?php else: ?>
              <?php foreach ($giftcard_transactions as $trade): ?>
                <div class="trade-card">
                  <div class="trade-card-header">
                    <div class="trade-card-user">
                      <div class="trade-card-user-name"><?php echo htmlspecialchars($trade['user_name']); ?></div>
                      <div class="trade-card-user-email"><?php echo htmlspecialchars($trade['user_email']); ?></div>
                    </div>
                    <div class="trade-card-status">
                      <span class="status-badge status-<?php echo strtolower($trade['status']); ?>">
                        <?php echo htmlspecialchars($trade['status']); ?>
                      </span>
                    </div>
                  </div>
                  
                  <div class="trade-card-body">
                    <div class="trade-card-item">
                      <div class="trade-card-label">Amount</div>
                      <div class="trade-card-value trade-card-amount">$<?php echo number_format($trade['amount'], 2); ?></div>
                    </div>
                    <div class="trade-card-item">
                      <div class="trade-card-label">Card Type</div>
                      <div class="trade-card-value">
                        <span class="badge bg-primary" style="font-size: 0.8rem; padding: 0.4rem 0.6rem;">
                          <?php echo htmlspecialchars($trade['card_type']); ?>
                        </span>
                      </div>
                    </div>
                    <div class="trade-card-item">
                      <div class="trade-card-label">Rate (₦/USD)</div>
                      <div class="trade-card-value">
                        ₦<?php echo number_format($trade['rate'] ?? 0, 2); ?>
                      </div>
                    </div>
                    <div class="trade-card-item">
                      <div class="trade-card-label">Estimated Payout</div>
                      <div class="trade-card-value trade-card-payout">
                        ₦<?php echo number_format($trade['amount'] * ($trade['rate'] ?? 0), 2); ?>
                      </div>
                    </div>
                    <div class="trade-card-item">
                      <div class="trade-card-label">Transaction ID</div>
                      <div class="trade-card-value">
                        <code class="text-muted" style="font-size: 0.75rem; word-break: break-all;">
                          #<?php echo $trade['id']; ?>
                        </code>
                      </div>
                    </div>
                    <div class="trade-card-item">
                      <div class="trade-card-label">User ID</div>
                      <div class="trade-card-value">
                        <code class="trade-card-user-id" style="font-size: 0.75rem;">
                          #<?php echo $trade['user_id']; ?>
                        </code>
                      </div>
                    </div>
                    <div class="trade-card-item">
                      <div class="trade-card-label">Card Image</div>
                      <div class="trade-card-value trade-card-image">
                        <?php if (!empty($trade['card_image'])): ?>
                          <img src="<?php echo htmlspecialchars($trade['card_image']); ?>" 
                               alt="Card Image" 
                               onclick="openImageModal('<?php echo htmlspecialchars($trade['card_image']); ?>')" 
                               title="Click to view larger image">
                        <?php else: ?>
                          <span class="text-muted" style="font-size: 0.8rem;">
                            <i class="bi bi-image"></i> No image
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="trade-card-item">
                      <div class="trade-card-label">Date</div>
                      <div class="trade-card-value">
                        <?php echo date('M j, Y', strtotime($trade['date'])); ?><br>
                        <small><?php echo date('g:i A', strtotime($trade['date'])); ?></small>
                      </div>
                    </div>
                    <div class="trade-card-item">
                      <div class="trade-card-label">Processing Time</div>
                      <div class="trade-card-value trade-card-processing-time">
                        <?php 
                        $processing_time = time() - strtotime($trade['date']);
                        $hours = floor($processing_time / 3600);
                        $minutes = floor(($processing_time % 3600) / 60);
                        if ($hours > 0) {
                          echo $hours . 'h ' . $minutes . 'm ago';
                        } else {
                          echo $minutes . 'm ago';
                        }
                        ?>
                      </div>
                    </div>
                  </div>
                  
                  <div class="trade-card-actions">
                    <div class="trade-card-date">
                      <?php if ($trade['status'] === 'Processing'): ?>
                        <div class="d-flex gap-1">
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                            <input type="hidden" name="new_status" value="Completed">
                            <button type="submit" name="update_giftcard_status" class="action-btn action-btn-success" 
                                    title="Approve trade and add ₦<?php echo number_format($trade['amount'] * ($trade['rate'] ?? 0), 2); ?> to user balance">
                              <span class="bi bi-check"></span> Approve
                            </button>
                          </form>
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                            <input type="hidden" name="new_status" value="Rejected">
                            <button type="submit" name="update_giftcard_status" class="action-btn action-btn-danger" title="Reject Trade">
                              <span class="bi bi-x"></span> Reject
                            </button>
                          </form>
                        </div>
                      <?php else: ?>
                        <span class="text-muted">No actions available</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <!-- Bitcoin Trades Section -->
      <div class="dashboard-card">
        <div class="trades-section">
          <div class="trades-section-header">
            <span class="bi bi-currency-bitcoin"></span>
            <h3>Bitcoin Trades</h3>
            <span class="badge bg-light text-dark ms-auto"><?php echo $total_bitcoin_trades; ?> trades</span>
          </div>
          <!-- Desktop Table View -->
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Amount (BTC)</th>
                  <th>Transaction ID</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($bitcoin_transactions)): ?>
                  <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                      <span class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                      No bitcoin trades found
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($bitcoin_transactions as $trade): ?>
                    <tr>
                      <td>
                        <div class="user-info">
                          <div class="user-name"><?php echo htmlspecialchars($trade['user_name']); ?></div>
                          <div class="user-email"><?php echo htmlspecialchars($trade['user_email']); ?></div>
                        </div>
                      </td>
                      <td>
                        <div class="amount-display"><?php echo number_format($trade['amount'], 8); ?> BTC</div>
                      </td>
                      <td>
                        <code class="text-muted" style="font-size: 0.8rem;">
                          <?php echo htmlspecialchars($trade['txid'] ?? 'N/A'); ?>
                        </code>
                      </td>
                      <td>
                        <span class="status-badge status-<?php echo strtolower($trade['status']); ?>">
                          <?php echo htmlspecialchars($trade['status']); ?>
                        </span>
                      </td>
                      <td>
                        <div class="date-display">
                          <?php echo date('M j, Y', strtotime($trade['date'])); ?><br>
                          <small><?php echo date('g:i A', strtotime($trade['date'])); ?></small>
                        </div>
                      </td>
                                         <td>
                       <div class="d-flex gap-1">
                         <?php if ($trade['status'] === 'Processing'): ?>
                           <?php 
                           // Calculate estimated payout for tooltip
                           $btc_rate = 50000; // Default rate
                           $rates_result = $db->query("SELECT value FROM rates WHERE type = 'btc_sell'");
                           if ($rates_result && $rates_row = $rates_result->fetch_assoc()) {
                               $btc_rate = $rates_row['value'];
                           }
                           $rates_result->close();
                           $estimated_payout = $trade['amount'] * $btc_rate;
                           ?>
                           <form method="POST" style="display: inline;">
                             <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                             <input type="hidden" name="new_status" value="Completed">
                             <button type="submit" name="update_bitcoin_status" class="action-btn action-btn-success" 
                                     title="Approve trade and add ₦<?php echo number_format($estimated_payout, 2); ?> to user balance">
                               <span class="bi bi-check"></span>
                             </button>
                           </form>
                           <form method="POST" style="display: inline;">
                             <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                             <input type="hidden" name="new_status" value="Rejected">
                             <button type="submit" name="update_bitcoin_status" class="action-btn action-btn-danger" title="Reject Trade">
                               <span class="bi bi-x"></span>
                             </button>
                           </form>
                         <?php else: ?>
                           <span class="text-muted">-</span>
                         <?php endif; ?>
                       </div>
                     </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <!-- Mobile Card View -->
        <div class="mobile-cards">
          <?php if (empty($bitcoin_transactions)): ?>
            <div class="text-center py-4 text-muted">
              <span class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
              No bitcoin trades found
            </div>
          <?php else: ?>
            <?php foreach ($bitcoin_transactions as $trade): ?>
              <div class="trade-card">
                <div class="trade-card-header">
                  <div class="trade-card-user">
                    <div class="trade-card-user-name"><?php echo htmlspecialchars($trade['user_name']); ?></div>
                    <div class="trade-card-user-email"><?php echo htmlspecialchars($trade['user_email']); ?></div>
                  </div>
                  <div class="trade-card-status">
                    <span class="status-badge status-<?php echo strtolower($trade['status']); ?>">
                      <?php echo htmlspecialchars($trade['status']); ?>
                    </span>
                  </div>
                </div>
                
                <div class="trade-card-body">
                  <div class="trade-card-item">
                    <div class="trade-card-label">Amount</div>
                    <div class="trade-card-value trade-card-amount"><?php echo number_format($trade['amount'], 8); ?> BTC</div>
                  </div>
                  <?php 
                  // Calculate estimated payout for display
                  $btc_rate = 50000; // Default rate
                  $rates_result = $db->query("SELECT value FROM rates WHERE type = 'btc_sell'");
                  if ($rates_result && $rates_row = $rates_result->fetch_assoc()) {
                      $btc_rate = $rates_row['value'];
                  }
                  $rates_result->close();
                  $estimated_payout = $trade['amount'] * $btc_rate;
                  ?>
                  <div class="trade-card-item">
                    <div class="trade-card-label">Rate (₦/BTC)</div>
                    <div class="trade-card-value">
                      ₦<?php echo number_format($btc_rate, 2); ?>
                    </div>
                  </div>
                  <div class="trade-card-item">
                    <div class="trade-card-label">Estimated Payout</div>
                    <div class="trade-card-value trade-card-payout">
                      ₦<?php echo number_format($estimated_payout, 2); ?>
                    </div>
                  </div>
                  <div class="trade-card-item">
                    <div class="trade-card-label">Transaction ID</div>
                    <div class="trade-card-value">
                      <code class="text-muted" style="font-size: 0.75rem; word-break: break-all;">
                        <?php echo htmlspecialchars($trade['txid'] ?? 'N/A'); ?>
                      </code>
                    </div>
                  </div>
                  <div class="trade-card-item">
                    <div class="trade-card-label">Internal ID</div>
                    <div class="trade-card-value">
                      <code class="text-muted" style="font-size: 0.75rem;">
                        #<?php echo $trade['id']; ?>
                      </code>
                    </div>
                  </div>
                  <div class="trade-card-item">
                    <div class="trade-card-label">User ID</div>
                    <div class="trade-card-value">
                      <code class="trade-card-user-id" style="font-size: 0.75rem;">
                        #<?php echo $trade['user_id']; ?>
                      </code>
                    </div>
                  </div>
                  <div class="trade-card-item">
                    <div class="trade-card-label">Date</div>
                    <div class="trade-card-value">
                      <?php echo date('M j, Y', strtotime($trade['date'])); ?><br>
                      <small><?php echo date('g:i A', strtotime($trade['date'])); ?></small>
                    </div>
                  </div>
                  <div class="trade-card-item">
                    <div class="trade-card-label">Processing Time</div>
                    <div class="trade-card-value trade-card-processing-time">
                      <?php 
                      $processing_time = time() - strtotime($trade['date']);
                      $hours = floor($processing_time / 3600);
                      $minutes = floor(($processing_time % 3600) / 60);
                      if ($hours > 0) {
                        echo $hours . 'h ' . $minutes . 'm ago';
                      } else {
                        echo $minutes . 'm ago';
                      }
                      ?>
                    </div>
                  </div>
                </div>
                
                <div class="trade-card-actions">
                  <div class="trade-card-date">
                    <?php if ($trade['status'] === 'Processing'): ?>
                      <?php 
                      // Calculate estimated payout for tooltip
                      $btc_rate = 50000; // Default rate
                      $rates_result = $db->query("SELECT value FROM rates WHERE type = 'btc_sell'");
                      if ($rates_result && $rates_row = $rates_result->fetch_assoc()) {
                          $btc_rate = $rates_row['value'];
                      }
                      $rates_result->close();
                      $estimated_payout = $trade['amount'] * $btc_rate;
                      ?>
                      <div class="d-flex gap-1">
                        <form method="POST" style="display: inline;">
                          <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                          <input type="hidden" name="new_status" value="Completed">
                          <button type="submit" name="update_bitcoin_status" class="action-btn action-btn-success" 
                                  title="Approve trade and add ₦<?php echo number_format($estimated_payout, 2); ?> to user balance">
                            <span class="bi bi-check"></span> Approve
                          </button>
                        </form>
                        <form method="POST" style="display: inline;">
                          <input type="hidden" name="transaction_id" value="<?php echo $trade['id']; ?>">
                          <input type="hidden" name="new_status" value="Rejected">
                          <button type="submit" name="update_bitcoin_status" class="action-btn action-btn-danger" title="Reject Trade">
                            <span class="bi bi-x"></span> Reject
                          </button>
                        </form>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">No actions available</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <!-- Image Modal -->
  <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="imageModalLabel">Card Image</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <img id="modalImage" src="" alt="Card Image" style="max-width:100%;height:auto;border-radius:0.5rem;">
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    &copy; <?php echo date('Y'); ?> Bitcoin Giftcards Admin Panel. All Rights Reserved.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle functionality
    const adminSidebar = document.getElementById('adminSidebar');
    const adminSidebarToggler = document.getElementById('adminSidebarToggler');
    const mobileSidebarBtn = document.getElementById('mobileSidebarBtn');
    const adminSidebarOverlay = document.getElementById('adminSidebarOverlay');

    adminSidebarToggler.addEventListener('click', function() {
      adminSidebar.classList.toggle('collapsed');
    });

    mobileSidebarBtn.addEventListener('click', function() {
      adminSidebar.classList.toggle('open');
      adminSidebarOverlay.classList.toggle('active');
    });

    adminSidebarOverlay.addEventListener('click', function() {
      adminSidebar.classList.remove('open');
      adminSidebarOverlay.classList.remove('active');
    });

    // Utility functions
    function refreshPage() {
      location.reload();
    }

    function exportTrades() {
      // Implement export functionality
      alert('Export functionality will be implemented here');
    }

    // Function to open image modal for viewing uploaded card images
    function openImageModal(imageSrc) {
      document.getElementById('modalImage').src = imageSrc;
      var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
      imageModal.show();
    }

    // Auto-refresh every 30 seconds
    setInterval(function() {
      // You can implement AJAX refresh here instead of full page reload
    }, 30000);
  </script>
</body>
</html> 