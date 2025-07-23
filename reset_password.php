<?php
require_once 'db.php';
$token = $_GET['token'] ?? '';
$show_form = false;
$error = '';
if ($token) {
    $stmt = $db->prepare('SELECT email, expires_at FROM password_resets WHERE token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($email, $expires_at);
        $stmt->fetch();
        if (strtotime($expires_at) > time()) {
            $show_form = true;
        } else {
            $error = 'This reset link has expired.';
        }
    } else {
        $error = 'Invalid or expired reset link.';
    }
    $stmt->close();
} else {
    $error = 'Invalid reset link.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$password || strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
        $show_form = true;
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
        $show_form = true;
    } else {
        // Get email from token
        $stmt = $db->prepare('SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($email);
            $stmt->fetch();
            $stmt->close();
            // Update password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE users SET password = ? WHERE email = ?');
            $stmt->bind_param('ss', $hash, $email);
            $stmt->execute();
            $stmt->close();
            // Invalidate token
            $db->query("DELETE FROM password_resets WHERE token = '" . $db->real_escape_string($token) . "'");
            $success = 'Your password has been reset. You can now log in.';
            $show_form = false;
        } else {
            $error = 'Invalid or expired reset link.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password | Giftcard & Bitcoin Trading</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body style="background:#f6f8fc;">
  <div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card shadow p-4" style="max-width:400px;width:100%;border-radius:1.5rem;">
      <div class="text-center mb-4">
        <img src="images/logo.png" alt="Logo" style="height:48px;">
        <h3 class="mt-2 mb-1 fw-bold">Reset Password</h3>
      </div>
      <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center"> <?php echo $success; ?> <br><a href="index.html" class="btn btn-gradient mt-3">Go to Login</a></div>
      <?php elseif ($show_form): ?>
        <?php if (!empty($error)): ?><div class="alert alert-danger text-center"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>" />
          <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input type="password" class="form-control" id="password" name="password" required />
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required />
          </div>
          <button type="submit" class="btn btn-gradient w-100 mb-2">Reset Password</button>
        </form>
      <?php else: ?>
        <div class="alert alert-danger text-center"><?php echo $error; ?></div>
      <?php endif; ?>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 