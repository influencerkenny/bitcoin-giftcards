<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}
require_once 'db.php';
$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : '';

// Fetch all active giftcards
$giftcards = [];
$res = $db->query("SELECT id, card_name, rate, image_url, value_range, value FROM giftcard_rates WHERE status='active' ORDER BY card_name ASC");
while ($row = $res->fetch_assoc()) {
  $giftcards[] = $row;
}
$res->close();

// Handle trade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trade_giftcard'])) {
  $card_id = intval($_POST['card_id']);
  $amount = floatval($_POST['amount']);
  // Get card name for record
  $stmt = $db->prepare('SELECT card_name FROM giftcard_rates WHERE id=?');
  if (!$stmt) {
    die('Database error: Failed to prepare statement for card name. Error: ' . $db->error);
  }
  $stmt->bind_param('i', $card_id);
  $stmt->execute();
  $stmt->bind_result($card_name);
  $stmt->fetch();
  $stmt->close();
  // Insert trade (status: Processing)
  $stmt = $db->prepare('INSERT INTO giftcard_transactions (user_id, card_type, amount, status, date) VALUES (?, ?, ?, "Processing", NOW())');
  if (!$stmt) {
    die('Database error: Failed to prepare statement for trade insert. Error: ' . $db->error);
  }
  $stmt->bind_param('isd', $user_id, $card_name, $amount);
  $stmt->execute();
  $stmt->close();
  header('Location: giftcard_trade.php');
  exit();
}
// Fetch user trade history
$trade_history = [];
// Also fetch the rate for each card_type
$stmt = $db->prepare('SELECT t.card_type, t.amount, t.date, t.status, r.rate FROM giftcard_transactions t LEFT JOIN giftcard_rates r ON t.card_type = r.card_name WHERE t.user_id = ? ORDER BY t.date DESC');
if (!$stmt) {
  die('Database error: Failed to prepare statement for trade history. Error: ' . $db->error);
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($card_type, $amount, $date, $status, $rate);
while ($stmt->fetch()) {
  $trade_history[] = [
    'card_type' => $card_type,
    'amount' => $amount,
    'date' => $date,
    'status' => $status,
    'rate' => $rate
  ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Giftcard Trade | Bitcoin Giftcards</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
  <style>
    html, body {
      height: 100%;
      min-height: 100vh;
    }
    body {
      display: flex !important;
      flex-direction: column;
      min-height: 100vh;
      align-items: stretch !important;
      justify-content: flex-start !important;
      background: linear-gradient(252deg, #1a938a 0%, rgba(26, 147, 138, 0) 100.44%);
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
      flex: 1 0 auto;
    }
    .sidebar.collapsed ~ .main-content { margin-left: 60px; }
    @media (max-width: 991px) {
      .dashboard-header { position: fixed; top: 0; left: 0; width: 100vw; z-index: 110; }
      .sidebar { position: fixed; left: -220px; top: 64px; height: 100vh; z-index: 100; transition: left 0.2s; }
      .sidebar.open { left: 0; }
      #sidebarOverlay { display: none; }
      #sidebarOverlay.active { display: block; }
      .main-content { margin-left: 0; padding: 1.2rem 0.5rem; padding-top: 80px; }
      .sidebar.collapsed ~ .main-content { margin-left: 0; }
    }
    @media (max-width: 600px) {
      .dashboard-header { padding: 0.5rem 0.7rem; }
      .container { padding: 12px 2px; margin: 16px 2px; }
      .btn-primary { width: 100%; font-size: 1.05rem; }
      .form-group input, .form-group select { font-size: 1.05rem; }
      .trade-history-table { display: none; }
      .trade-history-cards { display: block; }
      .trade-history-card {
        background: linear-gradient(252deg, #f8fafd 0%, #e6f4ea 100.44%);
        border-radius: 1.1rem;
        box-shadow: 0 2px 12px rgba(26,147,138,0.08);
        margin-bottom: 1.1rem;
        padding: 1.1rem 1.2rem 0.9rem 1.2rem;
        color: #19376d;
      }
      .trade-history-card .card-title {
        font-weight: 700;
        color: #1a938a;
        font-size: 1.08rem;
        margin-bottom: 0.3rem;
      }
      .trade-history-card .trade-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.3rem;
        font-size: 0.99rem;
      }
      .trade-history-card .badge {
        font-size: 1em;
        min-width: 110px;
      }
    }
    @media (min-width: 601px) {
      .trade-history-cards { display: none; }
      .trade-history-table { display: block; }
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
    }
    .container { max-width: 900px; margin: 48px auto; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px rgba(26,147,138,0.08); padding: 32px 24px; }
    h2 { color: #1a938a; margin-bottom: 24px; }
    .btn-primary { background: linear-gradient(90deg,#ffbf3f 0%,#1a938a 100%); color: #19376d; border: none; border-radius: 8px; padding: 10px 28px; font-weight: 600; font-size: 1rem; margin-bottom: 18px; cursor: pointer; transition: background 0.2s; }
    .btn-primary:hover { background: linear-gradient(90deg,#1a938a 0%,#ffbf3f 100%); }
    .form-group { margin-bottom: 18px; }
    label { font-weight: 500; color: #19376d; }
    input, select { width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; margin-top: 6px; font-size: 1rem; }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
    <button class="sidebar-toggler" id="sidebarToggler" title="Toggle Sidebar"><span class="bi bi-list"></span></button>
    <ul class="nav flex-column">
      <li><a class="nav-link" href="dashboard.php"><span class="bi bi-house"></span> <span class="sidebar-label">Dashboard</span></a></li>
      <li><a class="nav-link" href="bank_account.php"><span class="bi bi-bank"></span> <span class="sidebar-label">Bank Account</span></a></li>
      <li><a class="nav-link active" href="giftcard_trade.php"><span class="bi bi-gift"></span> <span class="sidebar-label">Sell Giftcard</span></a></li>
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
      <button class="btn btn-outline-primary d-lg-none me-2" id="mobileSidebarBtn" style="font-size:1.5rem;"><span class="bi bi-list"></span></button>
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
    <div class="container">
      <h2 class="mb-4" style="color:#1a938a;font-weight:800;"><i class="bi bi-gift"></i> Sell Giftcards</h2>
      <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
        <?php foreach ($giftcards as $card): ?>
        <div class="col">
          <div class="card h-100 shadow-sm border-0 giftcard-tile">
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-4">
              <img src="<?php echo htmlspecialchars($card['image_url']); ?>" alt="<?php echo htmlspecialchars($card['card_name']); ?>" style="width:64px;height:64px;object-fit:cover;border-radius:0.7rem;box-shadow:0 2px 8px rgba(25,55,109,0.08);margin-bottom:1rem;">
              <h5 class="card-title mb-1" style="font-weight:700;color:#19376d;font-size:1.15rem;"> <?php echo htmlspecialchars($card['card_name']); ?> </h5>
              <div class="mb-1 text-muted" style="font-size:1.08rem;">₦<?php echo htmlspecialchars($card['rate']); ?> <span style="font-size:0.97rem;">per USD</span></div>
              <?php if (!empty($card['value_range'])): ?>
                <div class="mb-2 text-secondary" style="font-size:1.01rem;">Value: <?php echo htmlspecialchars($card['value_range']); ?></div>
              <?php elseif (!empty($card['value'])): ?>
                <div class="mb-2 text-secondary" style="font-size:1.01rem;">Value: $<?php echo htmlspecialchars($card['value']); ?></div>
              <?php endif; ?>
              <button class="btn btn-primary px-4 mt-auto" style="border-radius:1.5rem;font-weight:700;box-shadow:0 2px 8px rgba(26,147,138,0.10);" data-bs-toggle="modal" data-bs-target="#tradeModal" data-card-id="<?php echo $card['id']; ?>" data-card-name="<?php echo htmlspecialchars($card['card_name']); ?>" data-card-rate="<?php echo htmlspecialchars($card['rate']); ?>">Trade</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- Trade Modal -->
      <div class="modal fade" id="tradeModal" tabindex="-1" aria-labelledby="tradeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content" style="border-radius:1.2rem;">
            <form method="post" autocomplete="off">
              <div class="modal-header" style="border-radius:1.2rem 1.2rem 0 0;">
                <h5 class="modal-title" id="tradeModalLabel">Trade Giftcard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-4">
                <input type="hidden" name="card_id" id="modal_card_id">
                <div class="mb-3">
                  <label for="modal_card_name" class="form-label">Giftcard</label>
                  <input type="text" class="form-control" id="modal_card_name" readonly>
                </div>
                <div class="mb-3">
                  <label for="modal_card_rate" class="form-label">Rate (₦)</label>
                  <input type="text" class="form-control" id="modal_card_rate" readonly>
                </div>
                <div class="mb-3">
                  <label for="amount" class="form-label">Amount (USD)</label>
                  <input type="number" class="form-control" name="amount" id="modal_amount" min="1" step="0.01" required>
                </div>
                <div class="mb-3">
                  
                  <div id="youGetValue" style="font-weight:700;color:#1a938a;font-size:1.1rem;">You Get: ₦0</div>
                </div>
              </div>
              <div class="modal-footer" style="border-radius:0 0 1.2rem 1.2rem;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" name="trade_giftcard" value="1">Place Trade</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <!-- Trade History -->
      <div class="mt-5">
        <h5 class="mb-3" style="font-weight:700;color:#1a938a;"><span class="bi bi-clock-history"></span> Trade History</h5>
        <?php if (count($trade_history) === 0): ?>
          <div class="text-muted text-center">No trades yet.</div>
        <?php else: ?>
          <!-- Card view for mobile -->
          <div class="trade-history-cards">
            <?php foreach ($trade_history as $trade): ?>
            <div class="trade-history-card">
              <div class="card-title"><?php echo htmlspecialchars($trade['card_type']); ?></div>
              <div class="trade-row"><span>Date:</span> <span class="text-muted"><?php echo htmlspecialchars($trade['date']); ?></span></div>
              <div class="trade-row"><span>Amount:</span> <span class="fw-bold">$<?php echo number_format($trade['amount'],2); ?></span></div>
              <div class="trade-row"><span>You Get:</span> <span class="fw-bold text-success">₦<?php echo ($trade['rate'] ? number_format($trade['amount'] * $trade['rate'],2) : '0.00'); ?></span></div>
              <div class="trade-row"><span>Status:</span> <span class="badge" style="<?php
                if ($trade['status'] === 'Processing') echo 'background:#fff3cd;color:#856404;';
                elseif ($trade['status'] === 'Completed' || $trade['status'] === 'Paid Out') echo 'background:#d4edda;color:#155724;';
                elseif ($trade['status'] === 'Declined') echo 'background:#f8d7da;color:#721c24;';
              ?>"><?php if ($trade['status'] === 'Processing'): ?><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span><?php endif; ?><?php echo htmlspecialchars($trade['status']); ?></span></div>
            </div>
            <?php endforeach; ?>
          </div>
          <!-- Table view for desktop -->
          <div class="trade-history-table">
            <div class="table-responsive">
              <table class="table table-bordered align-middle bg-white" style="border-radius:1.1rem;overflow:hidden;">
                <thead class="table-light">
                  <tr>
                    <th>Giftcard</th>
                    <th>Date</th>
                    <th>Amount (USD)</th>
                    <th>You Get (₦)</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($trade_history as $trade): ?>
                  <tr>
                    <td style="font-weight:600;min-width:120px;"> <?php echo htmlspecialchars($trade['card_type']); ?> </td>
                    <td class="text-muted" style="font-size:0.97rem;"> <?php echo htmlspecialchars($trade['date']); ?> </td>
                    <td class="fw-bold" style="color:#19376d;">$<?php echo number_format($trade['amount'],2); ?></td>
                    <td class="fw-bold text-success">₦<?php echo ($trade['rate'] ? number_format($trade['amount'] * $trade['rate'],2) : '0.00'); ?></td>
                    <td>
                      <span class="badge" style="font-size:1em;min-width:110px;
                        <?php if ($trade['status'] === 'Processing') echo 'background:#fff3cd;color:#856404;';
                              elseif ($trade['status'] === 'Completed' || $trade['status'] === 'Paid Out') echo 'background:#d4edda;color:#155724;';
                              elseif ($trade['status'] === 'Declined') echo 'background:#f8d7da;color:#721c24;';
                      ?>">
                        <?php if ($trade['status'] === 'Processing'): ?>
                          <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($trade['status']); ?>
                      </span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      </div>
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
    sidebarToggler.addEventListener('click', function() {
      if (window.innerWidth < 992) {
        sidebar.classList.add('open');
        document.getElementById('sidebarOverlay').classList.add('active');
      } else {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
      }
    });
    // Mobile sidebar toggle
    const mobileSidebarBtn = document.getElementById('mobileSidebarBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    function openSidebar() {
      sidebar.classList.add('open');
      sidebarOverlay.classList.add('active');
    }
    function closeSidebar() {
      sidebar.classList.remove('open');
      sidebarOverlay.classList.remove('active');
    }
    if (mobileSidebarBtn) {
      mobileSidebarBtn.addEventListener('click', openSidebar);
    }
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', closeSidebar);
    }
    // Also close sidebar on nav link click (mobile)
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
      link.addEventListener('click', () => { if (window.innerWidth < 992) closeSidebar(); });
    });
    // Close sidebar when clicking outside (on overlay or main content)
    if (mainContent) {
      mainContent.addEventListener('click', function() {
        if (window.innerWidth < 992 && sidebar.classList.contains('open')) {
          closeSidebar();
        }
      });
    }
  </script>
  <script>
    // Populate trade modal with card info
    var tradeModal = document.getElementById('tradeModal');
    if (tradeModal) {
      tradeModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var cardId = button.getAttribute('data-card-id');
        var cardName = button.getAttribute('data-card-name');
        var cardRate = button.getAttribute('data-card-rate');
        document.getElementById('modal_card_id').value = cardId;
        document.getElementById('modal_card_name').value = cardName;
        document.getElementById('modal_card_rate').value = cardRate;
        document.getElementById('modal_amount').value = '';
        document.getElementById('youGetValue').textContent = 'You Get: ₦0';
        document.getElementById('modal_amount').setAttribute('data-rate', cardRate);
      });
    }
    // Calculate and display 'You Get' value
    var modalAmount = document.getElementById('modal_amount');
    if (modalAmount) {
      modalAmount.addEventListener('input', function() {
        var rate = parseFloat(modalAmount.getAttribute('data-rate'));
        var amount = parseFloat(modalAmount.value);
        var youGet = 0;
        if (!isNaN(rate) && !isNaN(amount)) {
          youGet = rate * amount;
        }
        document.getElementById('youGetValue').textContent = 'You Get: ₦' + youGet.toLocaleString('en-NG', {maximumFractionDigits:2});
      });
    }
  </script>
</body>
</html> 