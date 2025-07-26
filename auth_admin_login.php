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

// Check if admin table exists, if not create it
$checkTable = $db->query("SHOW TABLES LIKE 'admins'");
if ($checkTable->num_rows === 0) {
    // Create admin table
    $createTable = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'super_admin') DEFAULT 'admin',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!$db->query($createTable)) {
        respond(false, 'Database setup error. Please contact administrator.');
    }
    
    // Insert default admin user (password: admin123)
    $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $insertAdmin = "INSERT INTO admins (name, email, password, role) VALUES 
                   ('Admin User', 'admin@bitcoingiftcards.com', '$defaultPassword', 'super_admin')";
    
    if (!$db->query($insertAdmin)) {
        respond(false, 'Default admin creation failed. Please contact administrator.');
    }
}

// Query admin table
$stmt = $db->prepare('SELECT id, name, password, role, is_active FROM admins WHERE email = ?');
if (!$stmt) {
    respond(false, 'Database error. Please try again.');
}

$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    respond(false, 'Invalid email or password.');
}

$stmt->bind_result($id, $name, $hash, $role, $is_active);
$stmt->fetch();

if (!$is_active) {
    respond(false, 'Admin account is deactivated. Please contact administrator.');
}

if (!password_verify($password, $hash)) {
    respond(false, 'Invalid email or password.');
}

// Set admin session variables
$_SESSION['admin_id'] = $id;
$_SESSION['admin_name'] = $name;
$_SESSION['admin_email'] = $email;
$_SESSION['admin_role'] = $role;

// Clear any user session variables to avoid conflicts
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);

respond(true, 'Admin login successful!'); 