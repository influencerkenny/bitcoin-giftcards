<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false]);
  exit;
}
if (!isset($_SESSION['user_2fa'])) {
  $_SESSION['user_2fa'] = false;
}
$_SESSION['user_2fa'] = !$_SESSION['user_2fa'];
echo json_encode(['success' => true, 'enabled' => $_SESSION['user_2fa']]); 