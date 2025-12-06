<?php
require_once "../config/db.php";
session_start();

header("Content-Type: application/json");

// ===== 1. THỐNG KÊ NHIỆM VỤ THEO THÁNG =====
$task_sql = $conn->query("
    SELECT DATE_FORMAT(created_at, '%m/%Y') AS month, COUNT(*) AS total
    FROM todos
    WHERE status = 'completed'
    GROUP BY month
    ORDER BY created_at ASC
");
$task_labels = [];
$task_values = [];
while ($r = $task_sql->fetch_assoc()) {
    $task_labels[] = $r["month"];
    $task_values[] = (int)$r["total"];
}

// ===== 2. THỐNG KÊ TIN NHẮN THEO NGÀY =====
$msg_sql = $conn->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS total
    FROM messages
    GROUP BY day
    ORDER BY day ASC
");
$msg_labels = [];
$msg_values = [];
while ($r = $msg_sql->fetch_assoc()) {
    $msg_labels[] = $r["day"];
    $msg_values[] = (int)$r["total"];
}

// ===== 3. USER ONLINE / OFFLINE =====
$u_sql = $conn->query("SELECT status, COUNT(*) AS total FROM users GROUP BY status");

$online = 0;
$offline = 0;

while ($u = $u_sql->fetch_assoc()) {
    if ($u["status"] === "online") $online = $u["total"];
    else $offline = $u["total"];
}

// ===== TRẢ JSON CHO JAVASCRIPT =====
echo json_encode([
    "tasks" => ["labels" => $task_labels, "values" => $task_values],
    "messages" => ["labels" => $msg_labels, "values" => $msg_values],
    "users" => ["online" => $online, "offline" => $offline]
]);
