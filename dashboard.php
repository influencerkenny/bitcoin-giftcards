<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: index.html');
  exit;
}
require_once 'db.php';
$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);

// Fetch Crypto transactions
$crypto_transactions = [];
$stmt = $db->prepare('SELECT crypto_name, crypto_symbol, transaction_type, amount, rate, estimated_payment, status, created_at FROM crypto_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($crypto_name, $crypto_symbol, $transaction_type, $amount, $rate, $estimated_payment, $status, $created_at);
while ($stmt->fetch()) {
  $crypto_transactions[] = [
    'crypto_name' => $crypto_name,
    'crypto_symbol' => $crypto_symbol,
    'transaction_type' => $transaction_type,
    'amount' => $amount,
    'rate' => $rate,
    'estimated_payment' => $estimated_payment,
    'status' => $status,
    'created_at' => $created_at
  ];
}
$stmt->close();

// Fetch Giftcard transactions
$giftcard_transactions = [];
$stmt = $db->prepare('SELECT card_type, amount, date, status, card_image FROM giftcard_transactions WHERE user_id = ? ORDER BY date DESC LIMIT 5');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($gc_type, $gc_amount, $gc_date, $gc_status, $gc_image);
while ($stmt->fetch()) {
  $giftcard_transactions[] = [
    'card_type' => $gc_type,
    'amount' => $gc_amount,
    'date' => $gc_date,
    'status' => $gc_status,
    'card_image' => $gc_image
  ];
}
$stmt->close();

// Fetch rates
$btc_sell_rate = null;
$btc_buy_rate = null;
$giftcard_rate = null;
$res = $db->query("SELECT type, label, value FROM rates");
while ($row = $res->fetch_assoc()) {
  if ($row['type'] === 'btc_sell') $btc_sell_rate = $row['value'];
  if ($row['type'] === 'btc_buy') $btc_buy_rate = $row['value'];
  if ($row['type'] === 'giftcard') $giftcard_rate = $row['value'];
}
$res->close();

// Fetch gift card rates
$giftcard_rates = [];
$res = $db->query("SELECT id, card_name, rate, image_url FROM giftcard_rates WHERE status='active' ORDER BY card_name ASC");
while ($row = $res->fetch_assoc()) {
  $giftcard_rates[] = $row;
}
$res->close();
// Simulate 2FA status (replace with real user security check)
$user_2fa = false;
if (isset($_SESSION['user_2fa'])) {
  $user_2fa = $_SESSION['user_2fa'];
}
// Fetch wallet balance for the user
$wallet_balance = 0.00;
$stmt = $db->prepare('SELECT balance FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($wallet_balance);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Dashboard | Giftcard & Bitcoin Trading</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <style>
    body {
      background: linear-gradient(252deg, #1a938a 0%, rgba(26, 147, 138, 0) 100.44%);
      min-height: 100vh;
      color: #19376d;
    }
    .dashboard-header {
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
    }
    .dashboard-logo {
      font-weight: 700;
      font-size: 1.3rem;
      color: #19376d;
      letter-spacing: 1px;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .sidebar {
      background: #1a938a;
      color: #fff;
      min-height: 100vh;
      padding: 2rem 0.5rem 2rem 0.5rem;
      width: 220px;
      position: fixed;
      top: 64px;
      left: 0;
      z-index: 10;
      transition: width 0.2s;
    }
    .sidebar.collapsed { width: 60px; }
    .sidebar .nav-link {
      color: #fff;
      font-weight: 500;
      border-radius: 0.7rem;
      margin-bottom: 0.3rem;
      display: flex;
      align-items: center;
      gap: 0.7rem;
      padding: 0.7rem 1rem;
      transition: background 0.15s;
    }
    .sidebar .nav-link.active, .sidebar .nav-link:hover {
      background: #ffbf3f;
      color: #19376d;
    }
    .sidebar .nav-link .bi { font-size: 1.2rem; }
    .sidebar-toggler {
      background: none;
      border: none;
      color: #fff;
      font-size: 1.3rem;
      margin-bottom: 1.5rem;
      margin-left: 0.5rem;
      cursor: pointer;
    }
    .main-content {
      margin-left: 220px;
      padding: 2.5rem 2rem 1.5rem 2rem;
      min-height: 100vh;
      transition: margin-left 0.2s;
      padding-top: 80px;
    }
    .sidebar.collapsed ~ .main-content { margin-left: 60px; }
    .dashboard-card {
      background: #fff;
      border-radius: 1.2rem;
      box-shadow: 0 4px 24px rgba(26,147,138,0.08);
      padding: 1.5rem 1.2rem;
      margin-bottom: 2rem;
    }
    .dashboard-card h5 { color: #1a938a; font-weight: 700; }
    .dashboard-table th { color: #1a938a; font-weight: 600; }
    .dashboard-table td, .dashboard-table th { vertical-align: middle; }
    .dashboard-table .badge { font-size: 0.95em; }
    .rate-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1.5rem;
      margin-bottom: 1rem;
    }
    .rate-card .rate-label { font-weight: 600; color: #1a938a; }
    .rate-card .rate-value { font-size: 1.2rem; font-weight: 700; color: #0a174e; }
    .rate-card .btn { border-radius: 2rem; font-weight: 600; }
    @media (max-width: 991px) {
      .dashboard-header { position: fixed; top: 0; left: 0; width: 100vw; z-index: 110; }
      .sidebar { position: fixed; left: 0; top: 64px; height: 100vh; z-index: 100; }
      .main-content { margin-left: 0; padding: 1.2rem 0.5rem; padding-top: 80px; }
      .sidebar.collapsed ~ .main-content { margin-left: 0; }
    }
    @media (max-width: 600px) {
      .dashboard-header { padding: 0.5rem 0.7rem; }
      .dashboard-card { padding: 1rem 0.5rem; }
    }
    footer {
      background: #fff;
      border-top: 1px solid #e9ecef;
      color: #888;
      font-size: 1rem;
      text-align: center;
      padding: 1.2rem 0 0.7rem 0;
      margin-top: 2rem;
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
    .widget-label { font-size: 1.05rem; font-weight: 500; opacity: 0.85; }
    .widget-value { font-size: 1.25rem; font-weight: 700; margin-top: 0.2rem; }
    @media (max-width: 991px) {
        .sidebar { 
            left: -220px; 
            transition: left 0.3s ease; 
            z-index: 1000;
        }
        .sidebar.show { 
            left: 0; 
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .main-content { 
            margin-left: 0; 
            flex: 1; 
            padding: 1rem;
        }
        .main-content.collapsed { 
            margin-left: 0; 
        }
        
        /* Ensure header stays on top */
        .dashboard-header {
            z-index: 1001;
        }
        
        /* Improve overlay */
        #sidebarOverlay {
            z-index: 999;
            background: rgba(0,0,0,0.5);
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
    .btc-list { margin-top: 0.5rem; }
    .btc-row { box-shadow: 0 1px 6px rgba(26,147,138,0.04); border-left: 4px solid #1a938a; }
    .btc-type-badge.buy { background: #e0f7fa; color: #007bff; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .btc-type-badge.sell { background: #fce4ec; color: #d63384; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .btc-status-badge.paid { background: #e6f4ea; color: #198754; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .btc-status-badge.pending { background: #e3f2fd; color: #0d6efd; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .btc-status-badge.declined { background: #f8d7da; color: #dc3545; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .giftcard-list { margin-top: 0.5rem; }
    .giftcard-row { box-shadow: 0 1px 6px rgba(26,147,138,0.04); border-left: 4px solid #1a938a; }
    .giftcard-status-badge.paid { background: #e6f4ea; color: #198754; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .giftcard-status-badge.pending { background: #e3f2fd; color: #0d6efd; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .giftcard-status-badge.declined { background: #f8d7da; color: #dc3545; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .giftcard-amount { font-size: 1.01rem; letter-spacing: 0.01em; }
    .crypto-list { margin-top: 0.5rem; }
    .crypto-row { box-shadow: 0 1px 6px rgba(26,147,138,0.04); border-left: 4px solid #1a938a; }
    .crypto-type-badge.buy { background: #e0f7fa; color: #007bff; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .crypto-type-badge.sell { background: #fce4ec; color: #d63384; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .crypto-status-badge.paid { background: #e6f4ea; color: #198754; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .crypto-status-badge.pending { background: #e3f2fd; color: #0d6efd; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
    .crypto-status-badge.declined { background: #f8d7da; color: #dc3545; font-weight: 600; border-radius: 1.2rem; padding: 0.45em 1.1em; font-size: 0.98em; }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
    <button class="sidebar-toggler" id="sidebarToggler" title="Toggle Sidebar"><span class="bi bi-list"></span></button>
    <ul class="nav flex-column">
      <li><a class="nav-link active" href="#"><span class="bi bi-house"></span> <span class="sidebar-label">Dashboard</span></a></li>
      <li><a class="nav-link" href="bank_account.php"><span class="bi bi-bank"></span> <span class="sidebar-label">Bank Account</span></a></li>
      <li><a class="nav-link" href="giftcard_trade.php"><span class="bi bi-gift"></span> <span class="sidebar-label">Sell Giftcard</span></a></li>
      <li><a class="nav-link" href="bitcoin_trade.php"><span class="bi bi-currency-bitcoin"></span> <span class="sidebar-label">Buy/Sell Bitcoin</span></a></li>
      <li><a class="nav-link" href="support.php"><span class="bi bi-life-preserver"></span> <span class="sidebar-label">Support</span></a></li>
      <li><a class="nav-link" href="account.php"><span class="bi bi-person"></span> <span class="sidebar-label">Account</span></a></li>
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
          <li><a class="dropdown-item" href="#">Account</a></li>
          <li><a class="dropdown-item" href="#">Security</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </header>
  <!-- Main Content -->
  <main class="main-content" id="mainContent">
    <!-- Dashboard Widgets -->
    <div class="row g-4 mb-2">
      <div class="col-md-4">
        <div class="widget-card gradient-blue d-flex align-items-center gap-3 p-3 h-100">
          <span class="bi bi-wallet2 widget-icon"></span>
          <div style="flex:1;">
            <div class="widget-label d-flex align-items-center justify-content-between">
              Wallet Balance
              <button id="toggleBalanceBtn" class="btn btn-sm btn-outline-light ms-2" type="button" title="Show/Hide Balance" style="border-radius:50%;padding:2px 7px;">
                <span id="balanceEye" class="bi bi-eye-slash"></span>
              </button>
            </div>
            <div class="widget-value" id="walletBalance" style="letter-spacing:1px;">
              ****
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="widget-card gradient-blue d-flex align-items-center gap-3 p-3 h-100">
          <span class="bi bi-arrow-down-circle-fill widget-icon"></span>
          <div>
            <div class="widget-label">Bitcoin Sell Rate</div>
            <div class="widget-value"><?php echo $btc_sell_rate ? '$' . htmlspecialchars($btc_sell_rate) : 'N/A'; ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="widget-card gradient-green d-flex align-items-center gap-3 p-3 h-100">
          <span class="bi bi-arrow-up-circle-fill widget-icon"></span>
          <div>
            <div class="widget-label">Bitcoin Buy Rate</div>
            <div class="widget-value"><?php echo $btc_buy_rate ? '$' . htmlspecialchars($btc_buy_rate) : 'N/A'; ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="widget-card gradient-purple d-flex align-items-center gap-3 p-3 h-100">
          <span class="bi bi-shield-lock-fill widget-icon"></span>
          <div>
            <div class="widget-label">2FA Security</div>
            <div class="widget-value" id="twofaStatus">
              <?php echo $user_2fa ? '<span class=\'badge bg-success\'>Enabled</span>' : '<span class=\'badge bg-danger\'>Disabled</span>'; ?>
            </div>
            <button id="toggle2faBtn" class="btn btn-sm btn-outline-light mt-2" type="button">
              <span class="bi bi-shield-lock"></span> <?php echo $user_2fa ? 'Disable 2FA' : 'Enable 2FA'; ?>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="row g-4">
      <!-- Recent Crypto Transactions -->
      <div class="col-lg-6">
        <div class="dashboard-card">
          <h5 class="mb-3"><span class="bi bi-currency-bitcoin"></span> Recent Crypto Transactions</h5>
          <?php if (count($crypto_transactions) === 0): ?>
            <div class="text-muted text-center">No transactions found.</div>
          <?php else: ?>
            <div class="crypto-list">
              <?php foreach ($crypto_transactions as $tx): ?>
                <div class="crypto-row d-flex align-items-center gap-3 mb-3 p-2 rounded" style="background:#f8fafd;">
                  <div class="flex-grow-1">
                    <div class="fw-semibold" style="font-size:1.08rem;color:#19376d;">
                      <?php echo htmlspecialchars($tx['crypto_name']); ?> (<?php echo htmlspecialchars($tx['crypto_symbol']); ?>)
                    </div>
                    <div class="text-muted" style="font-size:0.9rem;">
                      <?php echo htmlspecialchars($tx['amount']); ?> <?php echo htmlspecialchars($tx['crypto_symbol']); ?> @ ₦<?php echo number_format($tx['rate'], 2); ?>
                    </div>
                  </div>
                  <span class="badge crypto-type-badge <?php echo ($tx['transaction_type'] === 'buy' ? 'buy' : 'sell'); ?>"> 
                    <?php echo strtoupper($tx['transaction_type']); ?> 
                  </span>
                  <span class="badge crypto-status-badge <?php
                    echo ($tx['status'] === 'Completed') ? 'paid' :
                         ($tx['status'] === 'Processing' ? 'pending' : 'declined');
                  ?>"> 
                    <?php echo strtoupper($tx['status']); ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <!-- Rates -->
      <div class="col-lg-6">
        <div class="dashboard-card">
          <h5 class="mb-3"><span class="bi bi-graph-up"></span> Rates</h5>
          <?php if (count($giftcard_rates) === 0): ?>
            <div class="text-muted text-center">No rates available.</div>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($giftcard_rates as $rate): ?>
                <div class="list-group-item d-flex align-items-center gap-3 px-0 py-3 border-0" style="background:transparent;">
                  <img src="<?php echo htmlspecialchars($rate['image_url']); ?>" alt="<?php echo htmlspecialchars($rate['card_name']); ?>" style="width:48px;height:48px;object-fit:cover;border-radius:0.7rem;box-shadow:0 2px 8px rgba(25,55,109,0.08);">
                  <div class="flex-grow-1">
                    <div class="fw-semibold" style="color:#19376d;font-size:1.08rem;line-height:1.1;">
                      <?php echo htmlspecialchars($rate['card_name']); ?>
                    </div>
                    <div class="text-muted" style="font-size:1.01rem;">₦<?php echo htmlspecialchars($rate['rate']); ?></div>
                  </div>
                  <a href="giftcard_trade.php?card_id=<?php echo $rate['id']; ?>" class="btn btn-primary px-4" style="border-radius:0.7rem;font-weight:600;">Trade</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <!-- Recent Giftcards Transactions -->
      <div class="col-12">
        <div class="dashboard-card">
          <h5 class="mb-3"><span class="bi bi-gift"></span> Recent Giftcards Transactions</h5>
          <?php if (count($giftcard_transactions) === 0): ?>
            <div class="text-muted text-center">No transactions found.</div>
          <?php else: ?>
            <div class="giftcard-list">
                          <?php foreach ($giftcard_transactions as $tx): ?>
              <div class="giftcard-row d-flex align-items-center gap-3 mb-3 p-2 rounded" style="background:#f8fafd;">
                <?php if (!empty($tx['card_image'])): ?>
                  <img src="<?php echo htmlspecialchars($tx['card_image']); ?>" alt="Card Image" style="width:40px;height:30px;object-fit:cover;border-radius:0.3rem;cursor:pointer;" onclick="openImageModal('<?php echo htmlspecialchars($tx['card_image']); ?>')" title="Click to view larger image">
                <?php endif; ?>
                <div class="fw-semibold flex-grow-1" style="font-size:1.08rem;min-width:120px;"> <?php echo htmlspecialchars($tx['card_type']); ?> </div>
                <span class="badge giftcard-status-badge <?php
                  echo ($tx['status'] === 'Completed' || $tx['status'] === 'Paid Out') ? 'paid' :
                       ($tx['status'] === 'Pending' ? 'pending' : 'declined');
                ?>"> 
                  <?php echo ($tx['status'] === 'Completed' ? 'PAID OUT' : strtoupper($tx['status'])); ?>
                </span>
                <span class="giftcard-amount ms-2 fw-bold" style="color:#19376d;">
                  <?php
                    $naira = number_format($tx['amount'] * 240, 0); // Simulate rate, replace with real
                    $usd = number_format($tx['amount'], 2);
                    echo 'AMOUNT: ₦' . $naira . ' ($' . $usd . ' @ ₦240)';
                  ?>
                </span>
              </div>
            <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <!-- Live Crypto Market Prices Widget -->
      <div class="col-12">
        <div class="dashboard-card">
          <h5 class="mb-3"><span class="bi bi-bar-chart"></span> Live Crypto Market Prices</h5>
          <div class="crypto-widget-embed" style="min-height:90px;">
            <!-- Removed CoinGecko Widget to fix Access Denied error -->
            <div style="color:#19376d;font-size:1.1rem;text-align:center;padding:24px 0;">
              Live crypto prices are temporarily unavailable.<br>
              Please check back later.
            </div>
          </div>
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
    sidebarToggler.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
      mainContent.classList.toggle('collapsed');
    });

    // 2FA toggle AJAX
    const toggle2faBtn = document.getElementById('toggle2faBtn');
    const twofaStatus = document.getElementById('twofaStatus');
    if (toggle2faBtn && twofaStatus) {
      toggle2faBtn.addEventListener('click', function() {
        toggle2faBtn.disabled = true;
        toggle2faBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Please wait...';
        fetch('update_2fa.php', { method: 'POST' })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              twofaStatus.innerHTML = data.enabled ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-danger">Disabled</span>';
              toggle2faBtn.innerHTML = '<span class="bi bi-shield-lock"></span> ' + (data.enabled ? 'Disable 2FA' : 'Enable 2FA');
            } else {
              toggle2faBtn.innerHTML = 'Try Again';
            }
            toggle2faBtn.disabled = false;
          })
          .catch(() => {
            toggle2faBtn.innerHTML = 'Error';
            toggle2faBtn.disabled = false;
          });
      });
    }

    // Wallet balance toggle
    const walletBalance = document.getElementById('walletBalance');
    const toggleBalanceBtn = document.getElementById('toggleBalanceBtn');
    const balanceEye = document.getElementById('balanceEye');
    let balanceVisible = false;
    const actualBalance = '<?php echo number_format($wallet_balance, 2); ?>';
    toggleBalanceBtn.addEventListener('click', function() {
      balanceVisible = !balanceVisible;
      if (balanceVisible) {
        walletBalance.textContent = '₦' + actualBalance;
        balanceEye.classList.remove('bi-eye-slash');
        balanceEye.classList.add('bi-eye');
      } else {
        walletBalance.textContent = '****';
        balanceEye.classList.remove('bi-eye');
        balanceEye.classList.add('bi-eye-slash');
      }
    });

    // Mobile sidebar toggle
    const mobileSidebarBtn = document.getElementById('mobileSidebarBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
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
    
    // Function to open image modal
    function openImageModal(imageSrc) {
      document.getElementById('modalImage').src = imageSrc;
      var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
      imageModal.show();
    }
  </script>
</body>
</html> 