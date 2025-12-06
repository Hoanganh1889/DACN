<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    echo json_encode(['alerts' => []]);
    exit;
}

$user = $_SESSION['user'];
$uid  = (int)$user['id'];

/*
 * Lấy các công việc:
 * - Có deadline
 * - Chưa hoàn thành
 * - Deadline <= ngày mai
 */
$sql = "
    SELECT id, title, status, deadline
    FROM todos
    WHERE user_id = $uid
      AND status != 'Hoàn thành'
      AND deadline IS NOT NULL
      AND deadline <> ''
      AND DATE(deadline) <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ORDER BY deadline ASC
";

$alerts = [];
if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $alerts[] = [
            'id'       => (int)$row['id'],
            'title'    => $row['title'],
            'status'   => $row['status'],
            'deadline' => $row['deadline'],
        ];
    }
}

echo json_encode(['alerts' => $alerts]);
