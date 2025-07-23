<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

function respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request.');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    respond(false, 'All fields are required.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Invalid email address.');
}

$stmt = $db->prepare('SELECT id, name, password FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    respond(false, 'Invalid email or password.');
}
$stmt->bind_result($id, $name, $hash);
$stmt->fetch();
if (!password_verify($password, $hash)) {
    respond(false, 'Invalid email or password.');
}
// Set session
$_SESSION['user_id'] = $id;
$_SESSION['user_name'] = $name;
respond(true, 'Login successful!'); 