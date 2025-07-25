<?php
session_start();
require_once 'db.php';
$admin_name = 'Admin';
// Handle add, edit, delete actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['delete_card_id'])) {
    $stmt = $db->prepare('DELETE FROM giftcard_rates WHERE id=?');
    $stmt->bind_param('i', $_POST['delete_card_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: admin_giftcard_types.php');
    exit();
  } elseif (isset($_POST['edit_card_submit'])) {
    $card_name = trim($_POST['card_name']);
    $rate = trim($_POST['rate']);
    $value_range = trim($_POST['value_range']);
    $value = trim($_POST['value']);
    $image_url = $_POST['existing_image_url'];
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
      $ext = pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION);
      $filename = 'giftcard_' . time() . '_' . rand(1000,9999) . '.' . $ext;
      move_uploaded_file($_FILES['edit_image']['tmp_name'], 'assets/images/' . $filename);
      $image_url = 'assets/images/' . $filename;
    }
    $stmt = $db->prepare('UPDATE giftcard_rates SET card_name=?, rate=?, image_url=?, value_range=?, value=? WHERE id=?');
    $stmt->bind_param('sssssi', $card_name, $rate, $image_url, $value_range, $value, $_POST['card_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: admin_giftcard_types.php');
    exit();
  } elseif (isset($_POST['add_card_submit'])) {
    $card_name = trim($_POST['card_name']);
    $rate = trim($_POST['rate']);
    $value_range = trim($_POST['value_range']);
    $value = trim($_POST['value']);
    $image_url = '';
    if (isset($_FILES['add_image']) && $_FILES['add_image']['error'] === UPLOAD_ERR_OK) {
      $ext = pathinfo($_FILES['add_image']['name'], PATHINFO_EXTENSION);
      $filename = 'giftcard_' . time() . '_' . rand(1000,9999) . '.' . $ext;
      move_uploaded_file($_FILES['add_image']['tmp_name'], 'assets/images/' . $filename);
      $image_url = 'assets/images/' . $filename;
    }
    $stmt = $db->prepare('INSERT INTO giftcard_rates (card_name, rate, image_url, value_range, value, status) VALUES (?, ?, ?, ?, ?, "active")');
    $stmt->bind_param('sssss', $card_name, $rate, $image_url, $value_range, $value);
    $stmt->execute();
    $stmt->close();
    header('Location: admin_giftcard_types.php');
    exit();
  }
}
// Fetch all gift card types
$giftcards = [];
$res = $db->query('SELECT * FROM giftcard_rates ORDER BY card_name ASC');
while ($row = $res->fetch_assoc()) {
  $giftcards[] = $row;
}
$res->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin - Gift Card Types | Bitcoin Giftcards</title>
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
      <li><a class="nav-link" href="admin_bank_accounts.php"><span class="bi bi-bank"></span> <span class="sidebar-label">Bank Accounts</span></a></li>
      <li><a class="nav-link active" href="admin_giftcard_types.php"><span class="bi bi-card-image"></span> <span class="sidebar-label">Gift Card Types</span></a></li>
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
          <h4 class="mb-0"><span class="bi bi-card-image"></span> Gift Card Types</h4>
          <button class="btn btn-primary add-account-btn" data-bs-toggle="modal" data-bs-target="#addCardModal"><span class="bi bi-plus"></span> Add Gift Card Type</button>
        </div>
        <div class="table-responsive">
          <!-- Table for desktop -->
          <table class="table admin-table align-middle d-none d-md-table">
            <thead>
              <tr>
                <th>Card Name</th>
                <th>Rate</th>
                <th>Image</th>
                <th>Value Range</th>
                <th>Value</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($giftcards as $card): ?>
              <tr>
                <td><?= htmlspecialchars($card['card_name']) ?></td>
                <td>₦<?= htmlspecialchars($card['rate']) ?></td>
                <td><img src="<?= htmlspecialchars($card['image_url']) ?>" alt="<?= htmlspecialchars($card['card_name']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:0.7rem;box-shadow:0 2px 8px rgba(25,55,109,0.08);"></td>
                <td><?= htmlspecialchars($card['value_range']) ?></td>
                <td>$<?= htmlspecialchars($card['value']) ?></td>
                <td><span class="badge bg-info text-dark"><?= htmlspecialchars(ucfirst($card['status'])) ?></span></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary" onclick="editCard(<?= $card['id'] ?>, '<?= htmlspecialchars(addslashes($card['card_name'])) ?>', '<?= htmlspecialchars(addslashes($card['rate'])) ?>', '<?= htmlspecialchars(addslashes($card['image_url'])) ?>', '<?= htmlspecialchars(addslashes($card['value_range'])) ?>', '<?= htmlspecialchars($card['value']) ?>')"><span class="bi bi-pencil"></span></button>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Delete this card type?');">
                    <input type="hidden" name="delete_card_id" value="<?= $card['id'] ?>">
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
              .giftcard-type-card {
                background: linear-gradient(252deg, #f8fafd 0%, #e6f4ea 100.44%);
                border-radius: 1.1rem;
                box-shadow: 0 2px 16px rgba(26,147,138,0.10);
                border: none;
                margin-bottom: 1.2rem;
              }
              .giftcard-type-card .card-body {
                padding: 1.2rem 1rem;
              }
              .giftcard-type-card .card-title {
                color: #1a938a;
                font-weight: 700;
                font-size: 1.08rem;
                margin-bottom: 0.4rem;
              }
              .giftcard-type-card .badge {
                background: #ffbf3f;
                color: #19376d;
                font-weight: 600;
              }
              .giftcard-type-card .btn-outline-primary {
                border-color: #1a938a;
                color: #1a938a;
              }
              .giftcard-type-card .btn-outline-primary:hover {
                background: #1a938a;
                color: #fff;
              }
              .giftcard-type-card .btn-danger {
                background: #dc3545;
                border: none;
              }
              .giftcard-type-card .btn-danger:hover {
                background: #b52a37;
              }
            </style>
            <?php foreach ($giftcards as $card): ?>
              <div class="card giftcard-type-card">
                <div class="card-body">
                  <div class="card-title mb-2"><strong><?= htmlspecialchars($card['card_name']) ?></strong></div>
                  <div class="mb-2"><img src="<?= htmlspecialchars($card['image_url']) ?>" alt="<?= htmlspecialchars($card['card_name']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:0.7rem;box-shadow:0 2px 8px rgba(25,55,109,0.08);"></div>
                  <div><b>Rate:</b> ₦<?= htmlspecialchars($card['rate']) ?></div>
                  <div><b>Value Range:</b> <?= htmlspecialchars($card['value_range']) ?></div>
                  <div><b>Value:</b> $<?= htmlspecialchars($card['value']) ?></div>
                  <div><b>Status:</b> <span class="badge"><?= htmlspecialchars(ucfirst($card['status'])) ?></span></div>
                  <div class="mt-2">
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editCard(<?= $card['id'] ?>, '<?= htmlspecialchars(addslashes($card['card_name'])) ?>', '<?= htmlspecialchars(addslashes($card['rate'])) ?>', '<?= htmlspecialchars(addslashes($card['image_url'])) ?>', '<?= htmlspecialchars(addslashes($card['value_range'])) ?>', '<?= htmlspecialchars($card['value']) ?>')"><span class="bi bi-pencil"></span></button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this card type?');">
                      <input type="hidden" name="delete_card_id" value="<?= $card['id'] ?>">
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
    <!-- Add Card Modal -->
    <div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" id="addCardForm" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title" id="addCardModalLabel">Add Gift Card Type</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="form-group mb-2">
                <label for="add_card_name">Card Name</label>
                <input type="text" name="card_name" id="add_card_name" class="form-control" required>
              </div>
              <div class="form-group mb-2">
                <label for="add_rate">Rate (₦)</label>
                <input type="number" name="rate" id="add_rate" class="form-control" required>
              </div>
              <div class="form-group mb-2">
                <label for="add_image">Image</label>
                <input type="file" name="add_image" id="add_image" class="form-control" accept="image/*" required>
              </div>
              <div class="form-group mb-2">
                <label for="add_value_range">Value Range</label>
                <input type="text" name="value_range" id="add_value_range" class="form-control" required>
              </div>
              <div class="form-group mb-2">
                <label for="add_value">Value (USD)</label>
                <input type="number" name="value" id="add_value" class="form-control" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" name="add_card_submit" value="1">Add Gift Card Type</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Edit Card Modal -->
    <div class="modal fade" id="editCardModal" tabindex="-1" aria-labelledby="editCardModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" id="editCardForm" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title" id="editCardModalLabel">Edit Gift Card Type</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="card_id" id="edit_card_id">
              <input type="hidden" name="existing_image_url" id="edit_existing_image_url">
              <div class="form-group mb-2">
                <label for="edit_card_name">Card Name</label>
                <input type="text" name="card_name" id="edit_card_name" class="form-control" required>
              </div>
              <div class="form-group mb-2">
                <label for="edit_rate">Rate (₦)</label>
                <input type="number" name="rate" id="edit_rate" class="form-control" required>
              </div>
              <div class="form-group mb-2">
                <label for="edit_image">Image</label>
                <input type="file" name="edit_image" id="edit_image" class="form-control" accept="image/*">
                <img id="edit_image_preview" src="" alt="Current Image" style="max-width:80px;max-height:80px;margin-top:8px;display:none;" />
              </div>
              <div class="form-group mb-2">
                <label for="edit_value_range">Value Range</label>
                <input type="text" name="value_range" id="edit_value_range" class="form-control" required>
              </div>
              <div class="form-group mb-2">
                <label for="edit_value">Value (USD)</label>
                <input type="number" name="value" id="edit_value" class="form-control" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" name="edit_card_submit" value="1">Save Changes</button>
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
    // Add Card Modal logic
    function editCard(id, name, rate, image, range, value) {
      document.getElementById('edit_card_id').value = id;
      document.getElementById('edit_card_name').value = name;
      document.getElementById('edit_rate').value = rate;
      document.getElementById('edit_image_preview').src = image;
      document.getElementById('edit_image_preview').style.display = image ? 'block' : 'none';
      document.getElementById('edit_existing_image_url').value = image;
      document.getElementById('edit_value_range').value = range;
      document.getElementById('edit_value').value = value;
      var editModal = new bootstrap.Modal(document.getElementById('editCardModal'));
      editModal.show();
    }
  </script>
</body>
</html> 