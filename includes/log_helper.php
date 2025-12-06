<?php
function add_log($conn, $user_id, $type, $action, $details = '') {

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    $stmt = $conn->prepare("
        INSERT INTO system_logs (user_id, type, action, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssss", $user_id, $type, $action, $details, $ip, $agent);
    $stmt->execute();
}
