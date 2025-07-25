<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// For now, assume admin is logged in. Add proper admin authentication later.
// if (!isset($_SESSION['admin_id'])) {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit;
// }

try {
    // Fetch live statistics
    $total_users = $db->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
    $total_giftcards = $db->query('SELECT COUNT(*) FROM giftcard_rates')->fetch_row()[0];
    $total_bank_accounts = $db->query('SELECT COUNT(*) FROM bank_accounts')->fetch_row()[0];
    $total_btc_trades = $db->query('SELECT COUNT(*) FROM btc_transactions')->fetch_row()[0];
    $total_giftcard_trades = $db->query('SELECT COUNT(*) FROM giftcard_transactions')->fetch_row()[0];
    $total_trades = $total_btc_trades + $total_giftcard_trades;

    // Recent users
    $recent_users_html = '';
    $res = $db->query('SELECT name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5');
    while ($row = $res->fetch_assoc()) {
        $recent_users_html .= '<li class="list-group-item d-flex align-items-center justify-content-between">';
        $recent_users_html .= '<span><strong>' . htmlspecialchars($row['name']) . '</strong> <span class="text-muted">(' . htmlspecialchars($row['email']) . ')</span></span>';
        $recent_users_html .= '<span class="badge bg-primary">Joined ' . date('M d', strtotime($row['created_at'])) . '</span>';
        $recent_users_html .= '</li>';
    }
    $res->close();

    // Recent trades
    $recent_trades_html = '';
    
    // BTC trades
    $btc_res = $db->query('SELECT user_id, amount, status, date FROM btc_transactions ORDER BY date DESC LIMIT 5');
    while ($row = $btc_res->fetch_assoc()) {
        $recent_trades_html .= '<li class="list-group-item d-flex align-items-center justify-content-between">';
        $recent_trades_html .= '<span><span class="badge bg-info">BTC</span> User #' . $row['user_id'] . ': $' . $row['amount'] . ' (' . htmlspecialchars($row['status']) . ')</span>';
        $recent_trades_html .= '<span class="text-muted">' . date('M d', strtotime($row['date'])) . '</span>';
        $recent_trades_html .= '</li>';
    }
    $btc_res->close();
    
    // Giftcard trades
    $gc_res = $db->query('SELECT user_id, card_type, amount, status, date FROM giftcard_transactions ORDER BY date DESC LIMIT 5');
    while ($row = $gc_res->fetch_assoc()) {
        $recent_trades_html .= '<li class="list-group-item d-flex align-items-center justify-content-between">';
        $recent_trades_html .= '<span><span class="badge bg-warning text-dark">Giftcard</span> User #' . $row['user_id'] . ': ' . htmlspecialchars($row['card_type']) . ' ₦' . $row['amount'] . ' (' . htmlspecialchars($row['status']) . ')</span>';
        $recent_trades_html .= '<span class="text-muted">' . date('M d', strtotime($row['date'])) . '</span>';
        $recent_trades_html .= '</li>';
    }
    $gc_res->close();

    $response = [
        'success' => true,
        'stats' => [
            'total_users' => (int)$total_users,
            'total_trades' => (int)$total_trades,
            'total_giftcards' => (int)$total_giftcards,
            'total_bank_accounts' => (int)$total_bank_accounts,
            'total_btc_trades' => (int)$total_btc_trades,
            'total_giftcard_trades' => (int)$total_giftcard_trades
        ],
        'recent_users' => '<ul class="list-group list-group-flush">' . $recent_users_html . '</ul>',
        'recent_trades' => '<ul class="list-group list-group-flush">' . $recent_trades_html . '</ul>',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?> 