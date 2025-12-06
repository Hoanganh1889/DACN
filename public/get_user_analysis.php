<?php
require '../config/db.php';
session_start();
$uid = $_SESSION['user']['id'];

/* Tổng hợp Task */
$stats = [];

// Tổng task
$stats['total'] = $conn->query("SELECT COUNT(*) c FROM todos WHERE user_id=$uid")->fetch_assoc()['c'];

// Đã hoàn thành
$stats['done'] = $conn->query("SELECT COUNT(*) c FROM todos WHERE user_id=$uid AND status='Hoàn thành'")->fetch_assoc()['c'];

// Task trễ deadline
$stats['late'] = $conn->query("
    SELECT COUNT(*) c 
    FROM todos 
    WHERE user_id=$uid 
    AND status='Hoàn thành' 
    AND deadline IS NOT NULL 
    AND DATE(completed_at) > DATE(deadline)
")->fetch_assoc()['c'];

// Trung bình thời gian hoàn thành
$avg = $conn->query("
    SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) avg_hour 
    FROM todos 
    WHERE user_id=$uid AND status='Hoàn thành'
")->fetch_assoc()['avg_hour'];
$stats['avg_time'] = round($avg, 1);

// Số lần sửa task
$stats['revisions'] = $conn->query("
    SELECT COUNT(*) c 
    FROM task_updates tu 
    JOIN todos t ON t.id = tu.task_id 
    WHERE t.user_id=$uid
")->fetch_assoc()['c'];

/* Thời gian Online */
$online = $conn->query("
    SELECT SUM(TIMESTAMPDIFF(MINUTE, login_time, logout_time)) AS mins
    FROM login_logs WHERE user_id=$uid
")->fetch_assoc();

$stats['online_minutes'] = $online['mins'] ?? 0;

echo json_encode($stats);
