<?php
session_start();

// Clear admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_role']);

// Destroy the session completely
session_destroy();

// Redirect to admin login page
header('Location: admin_login.php');
exit(); 