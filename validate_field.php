<?php
require_once 'db.php';
header('Content-Type: application/json');
$type = $_GET['type'] ?? '';
$value = trim($_GET['value'] ?? '');
$valid = true;
if ($type === 'username' && $value) {
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $valid = false;
    $stmt->close();
} elseif ($type === 'phone' && $value) {
    $stmt = $db->prepare('SELECT id FROM users WHERE REPLACE(phone, \'+\', \'\') = ? OR phone = ?');
    $stmt->bind_param('ss', $value, $value);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $valid = false;
    $stmt->close();
} elseif ($type === 'email' && $value) {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $valid = false;
    $stmt->close();
}
echo json_encode(['valid' => $valid]); 