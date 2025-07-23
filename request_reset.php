<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'smtp_config.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request.');
}
$email = trim($_POST['email'] ?? '');
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.');
}
// Check if user exists
$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    respond(true, 'If this email is registered, a reset link will be sent.'); // Don't reveal if email exists
}
$stmt->close();
// Generate token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
// Remove old tokens for this email
$db->query("DELETE FROM password_resets WHERE email = '" . $db->real_escape_string($email) . "'");
// Insert new token
$stmt = $db->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $email, $token, $expires);
$stmt->execute();
$stmt->close();
// Send email
$reset_link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;
    $mail->SMTPSecure = $smtp_secure;
    $mail->Port = $smtp_port;
    $mail->setFrom($smtp_from, $smtp_from_name);
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request';
    $mail->Body = '<p>You requested a password reset. Click the link below to set a new password:</p>' .
        '<p><a href="' . $reset_link . '">' . $reset_link . '</a></p>' .
        '<p>If you did not request this, you can ignore this email.</p>';
    $mail->send();
    respond(true, 'If this email is registered, a reset link will be sent.');
} catch (Exception $e) {
    respond(false, 'Failed to send reset email. Please try again later.');
} 