<?php
session_start();
require_once 'db.php';
$admin_name = 'Admin';
// TODO: Add real admin authentication check

// Handle edit or delete actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['delete_account_id'])) {
    $stmt = $db->prepare('DELETE FROM bank_accounts WHERE id=?');
    $stmt->bind_param('i', $_POST['delete_account_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: admin_bank_accounts.php');
    exit();
  } elseif (isset($_POST['edit_account_submit'])) {
    $type = $_POST['account_type'];
    $account_name = trim($_POST['account_name']);
    $account_number = trim($_POST['account_number']);
    $bank_name = trim($_POST['bank_name']);
    $iban = isset($_POST['iban']) ? trim($_POST['iban']) : null;
    $swift = isset($_POST['swift']) ? trim($_POST['swift']) : null;
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare('UPDATE bank_accounts SET account_type=?, account_name=?, account_number=?, bank_name=?, iban=?, swift=?, date_updated=? WHERE id=?');
    $stmt->bind_param('sssssssi', $type, $account_name, $account_number, $bank_name, $iban, $swift, $now, $_POST['account_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: admin_bank_accounts.php');
    exit();
  } elseif (isset($_POST['add_account_submit'])) {
    $user_id = intval($_POST['user_id']);
    $type = $_POST['account_type'];
    $account_name = trim($_POST['account_name']);
    $account_number = trim($_POST['account_number']);
    $bank_name = trim($_POST['bank_name']);
    $iban = isset($_POST['iban']) ? trim($_POST['iban']) : null;
    $swift = isset($_POST['swift']) ? trim($_POST['swift']) : null;
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare('INSERT INTO bank_accounts (user_id, account_type, account_name, account_number, bank_name, iban, swift, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssssss', $user_id, $type, $account_name, $account_number, $bank_name, $iban, $swift, $now);
    $stmt->execute();
    $stmt->close();
    header('Location: admin_bank_accounts.php');
    exit();
  }
}
// Fetch all bank accounts with user info
$accounts = [];
$res = $db->query('SELECT ba.*, u.name as user_name, u.email as user_email FROM bank_accounts ba LEFT JOIN users u ON ba.user_id = u.id ORDER BY ba.date_created DESC');
while ($row = $res->fetch_assoc()) {
  $accounts[] = $row;
}
$res->close();
// Fetch all users for add-account dropdown
$users = [];
$res = $db->query('SELECT id, name, email FROM users ORDER BY name ASC');
while ($row = $res->fetch_assoc()) {
  $users[] = $row;
}
$res->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin - Bank Accounts | Bitcoin Giftcards</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="css/style.css">
  <style>
    html, body { height: 100%; }
    body { min-height: 100vh; display: flex; flex-direction: column; background: linear-gradient(252deg, #f8fafd 0%, #e6f4ea 100.44%); color: #19376d; }
    .admin-header { background: #fff; border-bottom: 1px solid #e9ecef; padding: 0.7rem 2rem; display: flex; align-items: center; justify-content: space-between; position: fixed; top: 0; left: 0; width: 100vw; z-index: 110; min-height: 64px; box-shadow: 0 2px 12px rgba(26,147,138,0.04); }
    .admin-logo { font-weight: 700; font-size: 1.3rem; color: #1a938a; letter-spacing: 1px; display: flex; align-items: center; gap: 0.5rem; }
    .admin-sidebar { background: linear-gradient(180deg, #1a938a 0%, #19376d 100%); color: #fff; min-height: 100vh; padding: 2rem 0.5rem 2rem 0.5rem; width: 200px; position: fixed; top: 0; left: 0; z-index: 120; transition: width 0.2s, left 0.2s; box-shadow: 2px 0 16px rgba(26,147,138,0.07); display: flex; flex-direction: column; align-items: stretch; }
    .admin-sidebar.collapsed { width: 64px; }
    .admin-sidebar .nav-link { color: #fff; font-weight: 500; border-radius: 0.7rem; margin-bottom: 0.3rem; display: flex; align-items: center; gap: 0.7rem; padding: 0.7rem 1rem; transition: background 0.15s, color 0.15s; font-size: 1.08rem; }
    .admin-sidebar .nav-link.active, .admin-sidebar .nav-link:hover { background: #ffbf3f; color: #19376d; }
    .admin-sidebar .nav-link .bi { font-size: 1.3rem; }
    .admin-sidebar .sidebar-label { transition: opacity 0.2s; }
    .admin-sidebar.collapsed .sidebar-label { opacity: 0; width: 0; overflow: hidden; }
    .admin-sidebar-toggler { background: none; border: none; color: #fff; font-size: 1.5rem; margin-bottom: 2rem; margin-left: 0.5rem; cursor: pointer; align-self: flex-end; transition: color 0.2s; }
    .admin-main-content { margin-left: 230px; padding: 2rem 2rem 1.5rem 2rem; min-height: 100vh; transition: margin-left 0.2s; flex: 1 0 auto; }
    .admin-sidebar.collapsed ~ .admin-main-content { margin-left: 64px; }
    @media (max-width: 991px) { .admin-header { position: fixed; top: 0; left: 0; width: 100vw; z-index: 110; } .admin-sidebar { position: fixed; left: -230px; top: 0; height: 100vh; z-index: 120; } .admin-sidebar.open { left: 0; } #adminSidebarOverlay { display: none; } #adminSidebarOverlay.active { display: block; } .admin-main-content { margin-left: 0; padding: 1.2rem 0.5rem; padding-top: 60px; } .admin-sidebar.collapsed ~ .admin-main-content { margin-left: 0; } }
    .dashboard-card { background: #fff; border-radius: 1.2rem; box-shadow: 0 4px 24px rgba(26,147,138,0.08); padding: 1.5rem 1.2rem; margin-bottom: 2rem; transition: box-shadow 0.2s; }
    .dashboard-card:hover { box-shadow: 0 6px 32px rgba(26,147,138,0.12); }
    footer { background: #fff; border-top: 1px solid #e9ecef; color: #888; font-size: 1rem; text-align: center; padding: 1.2rem 0 0.7rem 0; margin-top: 2rem; flex-shrink: 0; width: 100%; }
    .admin-table th { background: #1a938a; color: #fff; font-weight: 600; }
    .admin-table td { background: #f9f9f9; }
    .admin-table th, .admin-table td { vertical-align: middle; }
    .admin-table .badge { font-size: 0.95em; }
    .admin-table .btn { border-radius: 2rem; font-weight: 600; }
    .admin-table .btn-danger { background: #dc3545; border: none; }
    .admin-table .btn-danger:hover { background: #b52a37; }
    .admin-table .btn-primary { background: #1a938a; border: none; }
    .admin-table .btn-primary:hover { background: #0a174e; }
    .admin-table .btn-outline-primary { border-color: #1a938a; color: #1a938a; }
    .admin-table .btn-outline-primary:hover { background: #1a938a; color: #fff; }
    .add-account-btn { margin-bottom: 1.5rem; }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <nav class="admin-sidebar" id="adminSidebar">
    <button class="admin-sidebar-toggler d-none d-lg-block" id="adminSidebarToggler" title="Toggle Sidebar"><span class="bi bi-list"></span></button>
    <ul class="nav flex-column">
      <li><a class="nav-link" href="admin_dashboard.php"><span class="bi bi-speedometer2"></span> <span class="sidebar-label">Dashboard Overview</span></a></li>
      <li><a class="nav-link active" href="admin_bank_accounts.php"><span class="bi bi-bank"></span> <span class="sidebar-label">Bank Accounts</span></a></li>
      <li><a class="nav-link" href="#"><span class="bi bi-card-image"></span> <span class="sidebar-label">Gift Cards</span></a></li>
      <li><a class="nav-link" href="#"><span class="bi bi-people"></span> <span class="sidebar-label">Users</span></a></li>
      <li><a class="nav-link" href="#"><span class="bi bi-arrow-left-right"></span> <span class="sidebar-label">Trades</span></a></li>
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
    <div class="container-fluid px-0 widgets-container" style="margin-top: 4rem;">
      <div class="dashboard-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0"><span class="bi bi-bank"></span> Bank Accounts</h4>
          <button class="btn btn-primary add-account-btn" data-bs-toggle="modal" data-bs-target="#addAccountModal"><span class="bi bi-plus"></span> Add Account</button>
        </div>
        <div class="table-responsive">
          <!-- Table for desktop -->
          <table class="table admin-table align-middle d-none d-md-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Account Name</th>
                <th>Account Number</th>
                <th>Bank Name</th>
                <th>Type</th>
                <th>IBAN</th>
                <th>Swift</th>
                <th>Date Created</th>
                <th>Date Updated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (
                $accounts as $acc): ?>
              <tr>
                <td><strong><?= htmlspecialchars($acc['user_name']) ?></strong><br><span class="text-muted" style="font-size:0.95em;">(<?= htmlspecialchars($acc['user_email']) ?>)</span></td>
                <td><?= htmlspecialchars($acc['account_name']) ?></td>
                <td><?= htmlspecialchars($acc['account_number']) ?></td>
                <td><?= htmlspecialchars($acc['bank_name']) ?></td>
                <td><span class="badge bg-info text-dark"><?= htmlspecialchars(ucfirst($acc['account_type'])) ?></span></td>
                <td><?= htmlspecialchars($acc['iban']) ?></td>
                <td><?= htmlspecialchars($acc['swift']) ?></td>
                <td><?= htmlspecialchars($acc['date_created']) ?></td>
                <td><?= $acc['date_updated'] ? htmlspecialchars($acc['date_updated']) : '-' ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary" onclick="editAccount(<?= $acc['id'] ?>, '<?= htmlspecialchars(addslashes($acc['account_type'])) ?>', '<?= htmlspecialchars(addslashes($acc['account_name'])) ?>', '<?= htmlspecialchars(addslashes($acc['account_number'])) ?>', '<?= htmlspecialchars(addslashes($acc['bank_name'])) ?>', '<?= htmlspecialchars(addslashes($acc['iban'])) ?>', '<?= htmlspecialchars(addslashes($acc['swift'])) ?>')"><span class="bi bi-pencil"></span></button>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Delete this account?');">
                    <input type="hidden" name="delete_account_id" value="<?= $acc['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"><span class="bi bi-trash"></span></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <!-- Card view for mobile -->
          <div class="d-block d-md-none">
            <style>
              .bank-account-card {
                background: linear-gradient(252deg, #f8fafd 0%, #e6f4ea 100.44%);
                border-radius: 1.1rem;
                box-shadow: 0 2px 16px rgba(26,147,138,0.10);
                border: none;
                margin-bottom: 1.2rem;
              }
              .bank-account-card .card-body {
                padding: 1.2rem 1rem;
              }
              .bank-account-card .card-title {
                color: #1a938a;
                font-weight: 700;
                font-size: 1.08rem;
                margin-bottom: 0.4rem;
              }
              .bank-account-card .badge {
                background: #ffbf3f;
                color: #19376d;
                font-weight: 600;
              }
              .bank-account-card .btn-outline-primary {
                border-color: #1a938a;
                color: #1a938a;
              }
              .bank-account-card .btn-outline-primary:hover {
                background: #1a938a;
                color: #fff;
              }
              .bank-account-card .btn-danger {
                background: #dc3545;
                border: none;
              }
              .bank-account-card .btn-danger:hover {
                background: #b52a37;
              }
            </style>
            <?php foreach ($accounts as $acc): ?>
              <div class="card bank-account-card">
                <div class="card-body">
                  <div class="card-title mb-2"><strong><?= htmlspecialchars($acc['user_name']) ?></strong> <span class="text-muted" style="font-size:0.95em;">(<?= htmlspecialchars($acc['user_email']) ?>)</span></div>
                  <div><b>Account Name:</b> <?= htmlspecialchars($acc['account_name']) ?></div>
                  <div><b>Account Number:</b> <?= htmlspecialchars($acc['account_number']) ?></div>
                  <div><b>Bank Name:</b> <?= htmlspecialchars($acc['bank_name']) ?></div>
                  <div><b>Type:</b> <span class="badge"><?= htmlspecialchars(ucfirst($acc['account_type'])) ?></span></div>
                  <?php if ($acc['iban']): ?><div><b>IBAN:</b> <?= htmlspecialchars($acc['iban']) ?></div><?php endif; ?>
                  <?php if ($acc['swift']): ?><div><b>Swift:</b> <?= htmlspecialchars($acc['swift']) ?></div><?php endif; ?>
                  <div><b>Date Created:</b> <?= htmlspecialchars($acc['date_created']) ?></div>
                  <div><b>Date Updated:</b> <?= $acc['date_updated'] ? htmlspecialchars($acc['date_updated']) : '-' ?></div>
                  <div class="mt-2">
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editAccount(<?= $acc['id'] ?>, '<?= htmlspecialchars(addslashes($acc['account_type'])) ?>', '<?= htmlspecialchars(addslashes($acc['account_name'])) ?>', '<?= htmlspecialchars(addslashes($acc['account_number'])) ?>', '<?= htmlspecialchars(addslashes($acc['bank_name'])) ?>', '<?= htmlspecialchars(addslashes($acc['iban'])) ?>', '<?= htmlspecialchars(addslashes($acc['swift'])) ?>')"><span class="bi bi-pencil"></span></button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this account?');">
                      <input type="hidden" name="delete_account_id" value="<?= $acc['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger"><span class="bi bi-trash"></span></button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <!-- Add Account Modal -->
    <div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" id="addAccountForm">
            <div class="modal-header">
              <h5 class="modal-title" id="addAccountModalLabel">Add Bank Account</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="form-group mb-2">
                <label for="add_user_id">User</label>
                <select name="user_id" id="add_user_id" class="form-control" required>
                  <option value="">Select User</option>
                  <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="toggle-type mb-2">
                <label><input type="radio" name="account_type" value="local" checked onchange="toggleAddAccountType()"> Local Account</label>
                <label style="margin-left:18px;"><input type="radio" name="account_type" value="international" onchange="toggleAddAccountType()"> International Bank Transfer</label>
              </div>
              <div class="form-group mb-2">
                <label for="add_account_name">Account Name</label>
                <input type="text" name="account_name" id="add_account_name" class="form-control" required>
              </div>
              <div class="form-group mb-2">
                <label for="add_account_number">Account Number</label>
                <input type="text" name="account_number" id="add_account_number" class="form-control" required>
              </div>
              <div class="form-group mb-2">
                <label for="add_bank_name">Bank Name</label>
                <input type="text" name="bank_name" id="add_bank_name" class="form-control" required>
              </div>
              <div class="form-group mb-2" id="add_iban_group" style="display:none;">
                <label for="add_iban">IBAN Number</label>
                <input type="text" name="iban" id="add_iban" class="form-control">
              </div>
              <div class="form-group mb-2" id="add_swift_group" style="display:none;">
                <label for="add_swift">Swift Code</label>
                <input type="text" name="swift" id="add_swift" class="form-control">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" name="add_account_submit" value="1">Add Account</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Edit Account Modal -->
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
              <div class="form-group mb-2">
                <label for="edit_account_name">Account Name</label>
                <input type="text" name="account_name" id="edit_account_name" class="form-control" required>
              </div>
              <div class="form-group mb-2">
                <label for="edit_account_number">Account Number</label>
                <input type="text" name="account_number" id="edit_account_number" class="form-control" required>
              </div>
              <div class="form-group mb-2">
                <label for="edit_bank_name">Bank Name</label>
                <input type="text" name="bank_name" id="edit_bank_name" class="form-control" required>
              </div>
              <div class="form-group mb-2" id="edit_iban_group" style="display:none;">
                <label for="edit_iban">IBAN Number</label>
                <input type="text" name="iban" id="edit_iban" class="form-control">
              </div>
              <div class="form-group mb-2" id="edit_swift_group" style="display:none;">
                <label for="edit_swift">Swift Code</label>
                <input type="text" name="swift" id="edit_swift" class="form-control">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" name="edit_account_submit" value="1">Save Changes</button>
            </div>
          </form>
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
    // Sidebar toggle (same as admin_dashboard.php)
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
    if (adminMainContent) {
      adminMainContent.addEventListener('click', function() {
        if (window.innerWidth < 992 && adminSidebar.classList.contains('open')) {
          adminSidebar.classList.remove('open');
          adminSidebarOverlay.classList.remove('active');
        }
      });
    }
    // Add Account Modal logic
    function toggleAddAccountType() {
      var type = document.querySelector('#addAccountForm input[name="account_type"]:checked').value;
      document.getElementById('add_iban_group').style.display = (type === 'international') ? '' : 'none';
      document.getElementById('add_swift_group').style.display = (type === 'international') ? '' : 'none';
    }
    // Edit Account Modal logic
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
      var type = document.querySelector('#editAccountForm input[name="account_type"]:checked').value;
      document.getElementById('edit_iban_group').style.display = (type === 'international') ? '' : 'none';
      document.getElementById('edit_swift_group').style.display = (type === 'international') ? '' : 'none';
    }
  </script>
</body>
</html> 