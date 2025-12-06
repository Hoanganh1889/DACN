<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['user'])) {
    $uid = (int)$_SESSION['user']['id'];

    $stmt = $conn->prepare("UPDATE users SET status='offline', last_active=NOW() WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
}
add_log($conn, $uid, "logout", "logout", "Đăng xuất hệ thống");
session_destroy();
header('Location: login.php');
exit;
