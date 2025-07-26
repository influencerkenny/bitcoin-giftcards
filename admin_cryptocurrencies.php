<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_crypto'])) {
        $crypto_name = trim($_POST['crypto_name']);
        $crypto_symbol = strtoupper(trim($_POST['crypto_symbol']));
        $buy_rate = floatval($_POST['buy_rate']);
        $sell_rate = floatval($_POST['sell_rate']);
        
        $stmt = $db->prepare('INSERT INTO cryptocurrency_rates (crypto_name, crypto_symbol, buy_rate, sell_rate) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssdd', $crypto_name, $crypto_symbol, $buy_rate, $sell_rate);
        
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Cryptocurrency added successfully!";
        } else {
            $_SESSION['admin_error'] = "Error adding cryptocurrency: " . $db->error;
        }
        $stmt->close();
        header('Location: admin_cryptocurrencies.php');
        exit();
    }
    
    if (isset($_POST['update_crypto'])) {
        $crypto_id = intval($_POST['crypto_id']);
        $crypto_name = trim($_POST['crypto_name']);
        $crypto_symbol = strtoupper(trim($_POST['crypto_symbol']));
        $buy_rate = floatval($_POST['buy_rate']);
        $sell_rate = floatval($_POST['sell_rate']);
        $status = $_POST['status'];
        
        $stmt = $db->prepare('UPDATE cryptocurrency_rates SET crypto_name=?, crypto_symbol=?, buy_rate=?, sell_rate=?, status=? WHERE id=?');
        $stmt->bind_param('ssddsi', $crypto_name, $crypto_symbol, $buy_rate, $sell_rate, $status, $crypto_id);
        
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Cryptocurrency updated successfully!";
        } else {
            $_SESSION['admin_error'] = "Error updating cryptocurrency: " . $db->error;
        }
        $stmt->close();
        header('Location: admin_cryptocurrencies.php');
        exit();
    }
    
    if (isset($_POST['delete_crypto'])) {
        $crypto_id = intval($_POST['crypto_id']);
        
        $stmt = $db->prepare('DELETE FROM cryptocurrency_rates WHERE id=?');
        $stmt->bind_param('i', $crypto_id);
        
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Cryptocurrency deleted successfully!";
        } else {
            $_SESSION['admin_error'] = "Error deleting cryptocurrency: " . $db->error;
        }
        $stmt->close();
        header('Location: admin_cryptocurrencies.php');
        exit();
    }
}

// Fetch all cryptocurrencies
$cryptocurrencies = [];
$res = $db->query('SELECT * FROM cryptocurrency_rates ORDER BY crypto_name ASC');
while ($row = $res->fetch_assoc()) {
    $cryptocurrencies[] = $row;
}
$res->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cryptocurrencies | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(252deg, #f8fafd 0%, #e6f4ea 100.44%);
            min-height: 100vh;
        }
        .admin-header {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 2rem;
            box-shadow: 0 2px 12px rgba(26,147,138,0.04);
        }
        .admin-sidebar {
            background: linear-gradient(180deg, #1a938a 0%, #19376d 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 24px rgba(26,147,138,0.08);
        }
        .btn-primary {
            background: #1a938a;
            border-color: #1a938a;
        }
        .btn-primary:hover {
            background: #19376d;
            border-color: #19376d;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="admin-logo">
                <i class="bi bi-currency-bitcoin"></i>
                Cryptocurrency Management
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="admin_dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-4"></i>
                        <span class="ms-2"><?php echo $admin_name; ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="admin_logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="admin-sidebar position-fixed" style="width: 250px; top: 0; left: 0;">
        <div class="px-3">
            <h5 class="text-white mb-4">Admin Panel</h5>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white-50" href="admin_dashboard.php">
                        <i class="bi bi-house"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50" href="admin_giftcard_types.php">
                        <i class="bi bi-gift"></i> Giftcard Types
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white active" href="admin_cryptocurrencies.php">
                        <i class="bi bi-currency-bitcoin"></i> Cryptocurrencies
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50" href="admin_trades.php">
                        <i class="bi bi-arrow-left-right"></i> Trades
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50" href="admin_bank_accounts.php">
                        <i class="bi bi-bank"></i> Bank Accounts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50" href="admin_stats.php">
                        <i class="bi bi-graph-up"></i> Statistics
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Messages -->
            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['admin_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['admin_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['admin_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['admin_error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['admin_error']); ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-currency-bitcoin"></i> Manage Cryptocurrencies
                            </h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCryptoModal">
                                <i class="bi bi-plus"></i> Add Cryptocurrency
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cryptocurrency</th>
                                            <th>Symbol</th>
                                            <th>Buy Rate (₦)</th>
                                            <th>Sell Rate (₦)</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($cryptocurrencies)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No cryptocurrencies found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($cryptocurrencies as $crypto): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($crypto['crypto_name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($crypto['crypto_symbol']); ?></span>
                                                    </td>
                                                    <td>₦<?php echo number_format($crypto['buy_rate'], 2); ?></td>
                                                    <td>₦<?php echo number_format($crypto['sell_rate'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $crypto['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                            <?php echo ucfirst($crypto['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                onclick="editCrypto(<?php echo htmlspecialchars(json_encode($crypto)); ?>)">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteCrypto(<?php echo $crypto['id']; ?>, '<?php echo htmlspecialchars($crypto['crypto_name']); ?>')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Cryptocurrency Modal -->
    <div class="modal fade" id="addCryptoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Cryptocurrency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="crypto_name" class="form-label">Cryptocurrency Name</label>
                            <input type="text" class="form-control" id="crypto_name" name="crypto_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="crypto_symbol" class="form-label">Symbol</label>
                            <input type="text" class="form-control" id="crypto_symbol" name="crypto_symbol" required>
                        </div>
                        <div class="mb-3">
                            <label for="buy_rate" class="form-label">Buy Rate (₦)</label>
                            <input type="number" step="0.01" class="form-control" id="buy_rate" name="buy_rate" required>
                        </div>
                        <div class="mb-3">
                            <label for="sell_rate" class="form-label">Sell Rate (₦)</label>
                            <input type="number" step="0.01" class="form-control" id="sell_rate" name="sell_rate" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_crypto" class="btn btn-primary">Add Cryptocurrency</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Cryptocurrency Modal -->
    <div class="modal fade" id="editCryptoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Cryptocurrency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_crypto_id" name="crypto_id">
                        <div class="mb-3">
                            <label for="edit_crypto_name" class="form-label">Cryptocurrency Name</label>
                            <input type="text" class="form-control" id="edit_crypto_name" name="crypto_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_crypto_symbol" class="form-label">Symbol</label>
                            <input type="text" class="form-control" id="edit_crypto_symbol" name="crypto_symbol" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_buy_rate" class="form-label">Buy Rate (₦)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_buy_rate" name="buy_rate" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_sell_rate" class="form-label">Sell Rate (₦)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_sell_rate" name="sell_rate" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_crypto" class="btn btn-primary">Update Cryptocurrency</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCryptoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Cryptocurrency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteCryptoName"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <form method="POST">
                    <input type="hidden" id="delete_crypto_id" name="crypto_id">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_crypto" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCrypto(crypto) {
            document.getElementById('edit_crypto_id').value = crypto.id;
            document.getElementById('edit_crypto_name').value = crypto.crypto_name;
            document.getElementById('edit_crypto_symbol').value = crypto.crypto_symbol;
            document.getElementById('edit_buy_rate').value = crypto.buy_rate;
            document.getElementById('edit_sell_rate').value = crypto.sell_rate;
            document.getElementById('edit_status').value = crypto.status;
            
            new bootstrap.Modal(document.getElementById('editCryptoModal')).show();
        }

        function deleteCrypto(id, name) {
            document.getElementById('delete_crypto_id').value = id;
            document.getElementById('deleteCryptoName').textContent = name;
            
            new bootstrap.Modal(document.getElementById('deleteCryptoModal')).show();
        }
    </script>
</body>
</html> 