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
            width: 220px;
            position: fixed;
            top: 64px;
            left: 0;
            z-index: 10;
            transition: width 0.2s;
        }
        .admin-sidebar .nav-link {
            color: #fff;
            padding: 0.8rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.3rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
        }
        .admin-sidebar .nav-link:hover, .admin-sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
        .admin-sidebar .nav-link span:first-child {
            font-size: 1.2rem;
            min-width: 20px;
        }
        .main-content {
            margin-left: 220px;
            margin-top: 64px;
            padding: 2rem;
            flex: 1;
        }
        
        /* Add extra top margin for desktop */
        @media (min-width: 992px) {
            .main-content {
                margin-top: 40%;
            }
        }
        .dashboard-card {
            background: #fff;
            border-radius: 1.1rem;
            box-shadow: 0 2px 16px rgba(26,147,138,0.10);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #f1f3f4;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1a938a, #19376d);
            border: none;
            border-radius: 0.8rem;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #19376d, #1a938a);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26,147,138,0.2);
        }
        .btn-outline-primary {
            border-color: #1a938a;
            color: #1a938a;
            border-radius: 0.8rem;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-primary:hover {
            background: #1a938a;
            border-color: #1a938a;
            transform: translateY(-2px);
        }
        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
            border-radius: 0.8rem;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-danger:hover {
            background: #dc3545;
            border-color: #dc3545;
            transform: translateY(-2px);
        }
        .table {
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(26,147,138,0.05);
        }
        .table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: #495057;
        }
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        .badge {
            padding: 0.5rem 0.8rem;
            border-radius: 2rem;
            font-weight: 600;
        }
        .badge.bg-primary {
            background: linear-gradient(135deg, #1a938a, #19376d) !important;
        }
        .badge.bg-success {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
        }
        .badge.bg-secondary {
            background: linear-gradient(135deg, #6c757d, #495057) !important;
        }
        .modal-content {
            border-radius: 1.1rem;
            border: none;
            box-shadow: 0 20px 60px rgba(26, 147, 138, 0.2);
        }
        .modal-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 1px solid #f1f3f4;
            border-radius: 1.1rem 1.1rem 0 0;
        }
        .form-control, .form-select {
            border-radius: 0.8rem;
            border: 2px solid #e9ecef;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #1a938a;
            box-shadow: 0 0 0 0.2rem rgba(26, 147, 138, 0.25);
        }
        .stats-card {
            background: linear-gradient(135deg, #1a938a, #19376d);
            color: white;
            border-radius: 1.1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(26,147,138,0.15);
        }
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 991px) {
            .admin-sidebar { left: -220px; transition: left 0.2s; }
            .admin-sidebar.show { left: 0; }
            .main-content { margin-left: 0; margin-top: 400%; }
            
            /* Hide desktop tables on mobile */
            .table-responsive {
                display: none;
            }
            
            /* Show mobile cards */
            .mobile-cards {
                display: block;
            }
            
            /* Mobile card styles */
            .crypto-card {
                background: #fff;
                border-radius: 1rem;
                padding: 1.2rem;
                margin-bottom: 1rem;
                box-shadow: 0 2px 12px rgba(26,147,138,0.08);
                border: 1px solid #f1f3f4;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .crypto-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 20px rgba(26,147,138,0.12);
            }
            
            .crypto-card-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 1rem;
                padding-bottom: 0.8rem;
                border-bottom: 1px solid #f1f3f4;
            }
            
            .crypto-card-body {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
                margin-bottom: 1rem;
            }
            
            .crypto-card-item {
                display: flex;
                flex-direction: column;
                gap: 0.3rem;
            }
            
            .crypto-card-label {
                font-size: 0.8rem;
                color: #666;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .crypto-card-value {
                font-weight: 600;
                color: #19376d;
                font-size: 0.95rem;
            }
            
            .crypto-card-actions {
                display: flex;
                gap: 0.5rem;
                justify-content: flex-end;
            }
        }
        
        @media (min-width: 992px) {
            .mobile-cards {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
            <div class="admin-logo flex-grow-1">
                <i class="bi bi-currency-bitcoin"></i>
                Admin Dashboard
            </div>
            <span class="bi bi-bell" style="font-size:1.3rem;cursor:pointer;" title="Notifications"></span>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="bi bi-person-circle" style="font-size:1.7rem;color:#19376d;"></span>
                    <span class="ms-2 d-none d-md-inline" style="color:#19376d;font-weight:600;">Hi, <?php echo $admin_name; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="admin_dashboard.php">Dashboard</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="admin_logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="admin-sidebar" id="sidebar">
        <ul class="nav flex-column">
            <li><a class="nav-link" href="admin_dashboard.php"><span class="bi bi-speedometer2"></span> <span>Dashboard Overview</span></a></li>
            <li><a class="nav-link" href="admin_giftcard_types.php"><span class="bi bi-gift"></span> <span>Giftcard Types</span></a></li>
            <li><a class="nav-link active" href="admin_cryptocurrencies.php"><span class="bi bi-currency-bitcoin"></span> <span>Cryptocurrencies</span></a></li>
            <li><a class="nav-link" href="admin_trades.php"><span class="bi bi-arrow-left-right"></span> <span>Trades</span></a></li>
            <li><a class="nav-link" href="admin_bank_accounts.php"><span class="bi bi-bank"></span> <span>Bank Accounts</span></a></li>
            <li><a class="nav-link" href="admin_stats.php"><span class="bi bi-graph-up"></span> <span>Statistics</span></a></li>
            <li><a class="nav-link" href="admin_logout.php"><span class="bi bi-box-arrow-right"></span> <span>Logout</span></a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
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

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stats-number"><?php echo count($cryptocurrencies); ?></div>
                                <div class="stats-label">Total Cryptocurrencies</div>
                            </div>
                            <div class="stats-icon">
                                <i class="bi bi-currency-bitcoin"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stats-number"><?php echo count(array_filter($cryptocurrencies, function($c) { return $c['status'] === 'active'; })); ?></div>
                                <div class="stats-label">Active Cryptocurrencies</div>
                            </div>
                            <div class="stats-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stats-number"><?php echo count(array_filter($cryptocurrencies, function($c) { return $c['status'] === 'inactive'; })); ?></div>
                                <div class="stats-label">Inactive Cryptocurrencies</div>
                            </div>
                            <div class="stats-icon">
                                <i class="bi bi-pause-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stats-number">₦<?php echo number_format(array_sum(array_column($cryptocurrencies, 'buy_rate'))); ?></div>
                                <div class="stats-label">Total Buy Rate Value</div>
                            </div>
                            <div class="stats-icon">
                                <i class="bi bi-arrow-up-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">
                        <i class="bi bi-currency-bitcoin text-warning"></i>
                        Manage Cryptocurrencies
                    </h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCryptoModal">
                        <i class="bi bi-plus-circle"></i>
                        Add Cryptocurrency
                    </button>
                </div>

                <!-- Desktop Table View -->
                <div class="table-responsive">
                    <table class="table">
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
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <span class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                                        No cryptocurrencies found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cryptocurrencies as $crypto): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="bi bi-currency-bitcoin me-2 text-warning"></span>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($crypto['crypto_name']); ?></div>
                                                    <small class="text-muted">ID: <?php echo $crypto['id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($crypto['crypto_symbol']); ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success">₦<?php echo number_format($crypto['buy_rate'], 2); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-danger">₦<?php echo number_format($crypto['sell_rate'], 2); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $crypto['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($crypto['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-outline-primary btn-sm" 
                                                        onclick="editCrypto(<?php echo htmlspecialchars(json_encode($crypto)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteCrypto(<?php echo $crypto['id']; ?>, '<?php echo htmlspecialchars($crypto['crypto_name']); ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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
                    <?php if (empty($cryptocurrencies)): ?>
                        <div class="text-center py-4 text-muted">
                            <span class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></span>
                            No cryptocurrencies found
                        </div>
                    <?php else: ?>
                        <?php foreach ($cryptocurrencies as $crypto): ?>
                            <div class="crypto-card">
                                <div class="crypto-card-header">
                                    <div class="d-flex align-items-center">
                                        <span class="bi bi-currency-bitcoin me-2 text-warning"></span>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($crypto['crypto_name']); ?></div>
                                            <small class="text-muted">ID: <?php echo $crypto['id']; ?></small>
                                        </div>
                                    </div>
                                    <span class="badge bg-<?php echo $crypto['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($crypto['status']); ?>
                                    </span>
                                </div>
                                <div class="crypto-card-body">
                                    <div class="crypto-card-item">
                                        <div class="crypto-card-label">Symbol</div>
                                        <div class="crypto-card-value">
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($crypto['crypto_symbol']); ?></span>
                                        </div>
                                    </div>
                                    <div class="crypto-card-item">
                                        <div class="crypto-card-label">Buy Rate (₦)</div>
                                        <div class="crypto-card-value text-success">₦<?php echo number_format($crypto['buy_rate'], 2); ?></div>
                                    </div>
                                    <div class="crypto-card-item">
                                        <div class="crypto-card-label">Sell Rate (₦)</div>
                                        <div class="crypto-card-value text-danger">₦<?php echo number_format($crypto['sell_rate'], 2); ?></div>
                                    </div>
                                    <div class="crypto-card-item">
                                        <div class="crypto-card-label">Created</div>
                                        <div class="crypto-card-value">
                                            <?php echo date('M j, Y', strtotime($crypto['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="crypto-card-actions">
                                    <button class="btn btn-outline-primary btn-sm" 
                                            onclick="editCrypto(<?php echo htmlspecialchars(json_encode($crypto)); ?>)">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="deleteCrypto(<?php echo $crypto['id']; ?>, '<?php echo htmlspecialchars($crypto['crypto_name']); ?>')">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Cryptocurrency Modal -->
    <div class="modal fade" id="addCryptoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle text-success"></i>
                        Add New Cryptocurrency
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="crypto_name" class="form-label">Cryptocurrency Name</label>
                                    <input type="text" class="form-control" id="crypto_name" name="crypto_name" required>
                                    <div class="form-text">Enter the full name (e.g., Bitcoin, Ethereum)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="crypto_symbol" class="form-label">Symbol</label>
                                    <input type="text" class="form-control" id="crypto_symbol" name="crypto_symbol" required>
                                    <div class="form-text">Enter the symbol (e.g., BTC, ETH)</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="buy_rate" class="form-label">Buy Rate (₦)</label>
                                    <input type="number" step="0.01" class="form-control" id="buy_rate" name="buy_rate" required>
                                    <div class="form-text">Rate at which users can buy this cryptocurrency</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sell_rate" class="form-label">Sell Rate (₦)</label>
                                    <input type="number" step="0.01" class="form-control" id="sell_rate" name="sell_rate" required>
                                    <div class="form-text">Rate at which users can sell this cryptocurrency</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_crypto" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i>
                            Add Cryptocurrency
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Cryptocurrency Modal -->
    <div class="modal fade" id="editCryptoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil text-primary"></i>
                        Edit Cryptocurrency
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_crypto_id" name="crypto_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_crypto_name" class="form-label">Cryptocurrency Name</label>
                                    <input type="text" class="form-control" id="edit_crypto_name" name="crypto_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_crypto_symbol" class="form-label">Symbol</label>
                                    <input type="text" class="form-control" id="edit_crypto_symbol" name="crypto_symbol" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_buy_rate" class="form-label">Buy Rate (₦)</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_buy_rate" name="buy_rate" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_sell_rate" class="form-label">Sell Rate (₦)</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_sell_rate" name="sell_rate" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status</label>
                                    <select class="form-select" id="edit_status" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_crypto" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i>
                            Update Cryptocurrency
                        </button>
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
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle text-danger"></i>
                        Delete Cryptocurrency
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action cannot be undone.
                    </div>
                    <p>Are you sure you want to delete <strong id="deleteCryptoName" class="text-danger"></strong>?</p>
                    <p class="text-muted">This will permanently remove the cryptocurrency from the system.</p>
                </div>
                <form method="POST">
                    <input type="hidden" id="delete_crypto_id" name="crypto_id">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_crypto" class="btn btn-danger">
                            <i class="bi bi-trash"></i>
                            Delete Cryptocurrency
                        </button>
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