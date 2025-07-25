<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}
require_once 'db.php';
$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : '';
// Fetch wallet balance for the user
$wallet_balance = 0.00;
$stmt = $db->prepare('SELECT balance FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($wallet_balance);
$stmt->fetch();
$stmt->close();
// Handle form submission (insert/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $type = $_POST['account_type'];
  $account_name = trim($_POST['account_name']);
  $account_number = trim($_POST['account_number']);
  $bank_name = trim($_POST['bank_name']);
  $iban = isset($_POST['iban']) ? trim($_POST['iban']) : null;
  $swift = isset($_POST['swift']) ? trim($_POST['swift']) : null;
  $now = date('Y-m-d H:i:s');
  if (isset($_POST['account_id']) && $_POST['account_id']) {
    // Update
    $stmt = $db->prepare('UPDATE bank_accounts SET account_type=?, account_name=?, account_number=?, bank_name=?, iban=?, swift=?, date_updated=? WHERE id=? AND user_id=?');
    $stmt->bind_param('sssssssii', $type, $account_name, $account_number, $bank_name, $iban, $swift, $now, $_POST['account_id'], $user_id);
    $stmt->execute();
    $stmt->close();
  } else {
    // Insert
    $stmt = $db->prepare('INSERT INTO bank_accounts (user_id, account_type, account_name, account_number, bank_name, iban, swift, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssssss', $user_id, $type, $account_name, $account_number, $bank_name, $iban, $swift, $now);
    $stmt->execute();
    $stmt->close();
  }
  header('Location: bank_account.php');
  exit();
}
// Fetch accounts
$accounts = [];
$res = $db->prepare('SELECT * FROM bank_accounts WHERE user_id=? ORDER BY date_created DESC');
$res->bind_param('i', $user_id);
$res->execute();
$result = $res->get_result();
while ($row = $result->fetch_assoc()) {
  $accounts[] = $row;
}
$res->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bank Account | Bitcoin Giftcards</title>
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
    }
    @media (max-width: 991px) {
      .account-table th, .account-table td { font-size: 0.97em; padding: 7px; }
    }
    @media (max-width: 600px) {
      .account-table th, .account-table td { font-size: 0.93em; padding: 6px; }
    }
    .table-responsive { width: 100%; overflow-x: auto; }
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
    .container { max-width: 600px; margin: 48px auto; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px rgba(26,147,138,0.08); padding: 32px 24px; }
    h2 { color: #1a938a; margin-bottom: 24px; }
    .btn-primary { background: linear-gradient(90deg,#ffbf3f 0%,#1a938a 100%); color: #19376d; border: none; border-radius: 8px; padding: 10px 28px; font-weight: 600; font-size: 1rem; margin-bottom: 18px; cursor: pointer; transition: background 0.2s; }
    .btn-primary:hover { background: linear-gradient(90deg,#1a938a 0%,#ffbf3f 100%); }
    .form-group { margin-bottom: 18px; }
    label { font-weight: 500; color: #19376d; }
    input, select { width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; margin-top: 6px; font-size: 1rem; }
    .account-table { width: 100%; border-collapse: collapse; margin-top: 32px; }
    .account-table th, .account-table td { border: 1px solid #e0e0e0; padding: 10px; text-align: left; }
    .account-table th { background: #1a938a; color: #fff; }
    .account-table td { background: #f9f9f9; }
    .date-label { font-size: 0.95em; color: #888; }
    .toggle-type { margin-bottom: 18px; }
    .hidden { display: none; }
    .account-card {
      background: #fff;
      border-radius: 1.2rem;
      box-shadow: 0 4px 24px rgba(26,147,138,0.08);
      border: none;
      margin-bottom: 1.2rem;
      transition: box-shadow 0.18s, transform 0.18s;
    }
    .account-card:hover {
      box-shadow: 0 6px 32px rgba(26,147,138,0.13);
      transform: translateY(-2px) scale(1.01);
    }
    .account-card .badge {
      background: #1a938a;
      color: #fff;
      font-size: 1em;
      border-radius: 0.7rem;
      padding: 0.5em 1.1em;
    }
    .account-card .text-muted {
      color: #ffbf3f !important;
      font-weight: 600;
    }
    .account-card strong {
      color: #1a938a;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
    <button class="sidebar-toggler" id="sidebarToggler" title="Toggle Sidebar"><span class="bi bi-list"></span></button>
    <ul class="nav flex-column">
      <li><a class="nav-link" href="dashboard.php"><span class="bi bi-house"></span> <span class="sidebar-label">Dashboard</span></a></li>
      <li><a class="nav-link active" href="bank_account.php"><span class="bi bi-bank"></span> <span class="sidebar-label">Bank Account</span></a></li>
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
      <h2><i class="fas fa-university"></i> Bank Account</h2>
      <button class="btn-primary" onclick="document.getElementById('accountForm').classList.toggle('hidden')"><i class="fas fa-plus"></i> Add Account</button>
      <form id="accountForm" class="hidden" method="post" autocomplete="off">
        <div class="toggle-type">
          <label><input type="radio" name="account_type" value="local" checked onchange="toggleAccountType()"> Local Account</label>
          <label style="margin-left:18px;"><input type="radio" name="account_type" value="international" onchange="toggleAccountType()"> International Bank Transfer</label>
        </div>
        <div class="form-group">
          <label for="account_name">Account Name</label>
          <input type="text" name="account_name" id="account_name" required>
        </div>
        <div class="form-group">
          <label for="account_number">Account Number</label>
          <input type="text" name="account_number" id="account_number" required>
        </div>
        <div class="form-group">
          <label for="bank_name">Bank Name</label>
          <input type="text" name="bank_name" id="bank_name" required>
        </div>
        <div class="form-group" id="iban_group" style="display:none;">
          <label for="iban">IBAN Number</label>
          <input type="text" name="iban" id="iban">
        </div>
        <div class="form-group" id="swift_group" style="display:none;">
          <label for="swift">Swift Code</label>
          <input type="text" name="swift" id="swift">
        </div>
        <button type="submit" class="btn-primary">Save Account</button>
      </form>
      <div class="row g-3 mt-4">
        <?php foreach ($accounts as $acc): ?>
        <div class="col-12">
          <div class="card account-card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge">Type: <?= htmlspecialchars(ucfirst($acc['account_type'])) ?></span>
                <span class="text-muted" style="font-size:0.95em;">Created: <?= htmlspecialchars($acc['date_created']) ?></span>
              </div>
              <div class="row g-2">
                <div class="col-12 col-md-4"><strong>Account Name:</strong> <?= htmlspecialchars($acc['account_name']) ?></div>
                <div class="col-12 col-md-4"><strong>Account Number:</strong> <?= htmlspecialchars($acc['account_number']) ?></div>
                <div class="col-12 col-md-4"><strong>Bank Name:</strong> <?= htmlspecialchars($acc['bank_name']) ?></div>
              </div>
              <div class="row g-2 mt-1">
                <?php if ($acc['account_type'] === 'international'): ?>
                  <div class="col-12 col-md-4"><strong>IBAN:</strong> <?= htmlspecialchars($acc['iban']) ?></div>
                  <div class="col-12 col-md-4"><strong>Swift:</strong> <?= htmlspecialchars($acc['swift']) ?></div>
                <?php endif; ?>
                <div class="col-12 col-md-4"><strong>Date Updated:</strong> <?= $acc['date_updated'] ? htmlspecialchars($acc['date_updated']) : '-' ?></div>
              </div>
              <button class="btn btn-sm btn-outline-primary mt-3" onclick="editAccount(<?= $acc['id'] ?>, '<?= htmlspecialchars(addslashes($acc['account_type'])) ?>', '<?= htmlspecialchars(addslashes($acc['account_name'])) ?>', '<?= htmlspecialchars(addslashes($acc['account_number'])) ?>', '<?= htmlspecialchars(addslashes($acc['bank_name'])) ?>', '<?= htmlspecialchars(addslashes($acc['iban'])) ?>', '<?= htmlspecialchars(addslashes($acc['swift'])) ?>')">Edit</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- Edit Modal -->
      <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="post" id="editAccountForm">
              <div class="modal-header">
                <h5 class="modal-title" id="editAccountModalLabel">Edit Bank Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="account_id" id="edit_account_id">
                <div class="toggle-type mb-2">
                  <label><input type="radio" name="account_type" value="local" id="edit_type_local" onchange="toggleEditAccountType()"> Local Account</label>
                  <label style="margin-left:18px;"><input type="radio" name="account_type" value="international" id="edit_type_international" onchange="toggleEditAccountType()"> International Bank Transfer</label>
                </div>
                <div class="form-group">
                  <label for="edit_account_name">Account Name</label>
                  <input type="text" name="account_name" id="edit_account_name" class="form-control" required>
                </div>
                <div class="form-group">
                  <label for="edit_account_number">Account Number</label>
                  <input type="text" name="account_number" id="edit_account_number" class="form-control" required>
                </div>
                <div class="form-group">
                  <label for="edit_bank_name">Bank Name</label>
                  <input type="text" name="bank_name" id="edit_bank_name" class="form-control" required>
                </div>
                <div class="form-group" id="edit_iban_group" style="display:none;">
                  <label for="edit_iban">IBAN Number</label>
                  <input type="text" name="iban" id="edit_iban" class="form-control">
                </div>
                <div class="form-group" id="edit_swift_group" style="display:none;">
                  <label for="edit_swift">Swift Code</label>
                  <input type="text" name="swift" id="edit_swift" class="form-control">
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
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
    function toggleAccountType() {
      var type = document.querySelector('input[name="account_type"]:checked').value;
      document.getElementById('iban_group').style.display = (type === 'international') ? '' : 'none';
      document.getElementById('swift_group').style.display = (type === 'international') ? '' : 'none';
    }
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
    // Edit account modal logic
    function editAccount(id, type, name, number, bank, iban, swift) {
      document.getElementById('edit_account_id').value = id;
      document.getElementById('edit_account_name').value = name;
      document.getElementById('edit_account_number').value = number;
      document.getElementById('edit_bank_name').value = bank;
      document.getElementById('edit_iban').value = iban;
      document.getElementById('edit_swift').value = swift;
      if (type === 'international') {
        document.getElementById('edit_type_international').checked = true;
        document.getElementById('edit_iban_group').style.display = '';
        document.getElementById('edit_swift_group').style.display = '';
      } else {
        document.getElementById('edit_type_local').checked = true;
        document.getElementById('edit_iban_group').style.display = 'none';
        document.getElementById('edit_swift_group').style.display = 'none';
      }
      var editModal = new bootstrap.Modal(document.getElementById('editAccountModal'));
      editModal.show();
    }
    function toggleEditAccountType() {
      var type = document.querySelector('input[name="account_type"]:checked').value;
      document.getElementById('edit_iban_group').style.display = (type === 'international') ? '' : 'none';
      document.getElementById('edit_swift_group').style.display = (type === 'international') ? '' : 'none';
    }
    // Handle edit form submit
    document.getElementById('editAccountForm').addEventListener('submit', function(e) {
      // On submit, set a hidden field to indicate update
      var form = this;
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'edit_account_submit';
      input.value = '1';
      form.appendChild(input);
    });
  </script>
</body>
</html> 