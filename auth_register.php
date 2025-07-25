<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once 'db.php';

function respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Validate POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request.');
}

$name = trim($_POST['name'] ?? $_POST['fullname'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$country = trim($_POST['country'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$name || !$username || !$email || !$phone || !$country || !$password || !$confirm) {
    respond(false, 'All fields are required.');
}
if (!preg_match('/^[a-zA-Z0-9_]{3,}$/', $username)) {
    respond(false, 'Username must be at least 3 characters and contain only letters, numbers, and underscores.');
}
if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
    respond(false, 'Please enter a valid phone number (7-15 digits, optional +).');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Invalid email address.');
}
if (strlen($password) < 6) {
    respond(false, 'Password must be at least 6 characters.');
}
if ($password !== $confirm) {
    respond(false, 'Passwords do not match.');
}
// Check for duplicate email
$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    respond(false, 'Email already registered.');
}
$stmt->close();
// Check for duplicate username
$stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    respond(false, 'Username already taken.');
}
$stmt->close();
// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);
// Insert user
$stmt = $db->prepare('INSERT INTO users (name, username, email, phone, country, password) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->bind_param('ssssss', $name, $username, $email, $phone, $country, $hash);
if ($stmt->execute()) {
    require_once 'mailer.php';
    $welcome_subject = 'Welcome to Giftcard & Bitcoin Trading!';
    $welcome_body = '<h2>Welcome, ' . htmlspecialchars($name) . '!</h2><p>Thank you for registering. You can now log in and start trading.</p>';
    send_email($email, $welcome_subject, $welcome_body);
    respond(true, 'Registration successful! You can now log in.');
} else {
    respond(false, 'Registration failed: ' . $stmt->error);
} 