<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user = $_SESSION['user'];
$uid  = (int)$user['id'];

$data = [
    'weekly'  => ['labels' => [], 'done' => [], 'total' => []],
    'monthly' => ['labels' => [], 'done' => [], 'total' => []],
];

// ----- THỐNG KÊ THEO TUẦN (8 tuần gần nhất) -----
$sqlWeek = "
    SELECT 
        YEARWEEK(created_at, 1) AS yw,
        DATE_FORMAT(MIN(created_at), '%d/%m') AS start_date,
        DATE_FORMAT(MAX(created_at), '%d/%m') AS end_date,
        SUM(status='Hoàn thành') AS done,
        COUNT(*) AS total
    FROM todos
    WHERE user_id = $uid
    GROUP BY yw
    ORDER BY yw DESC
    LIMIT 8
";

if ($res = $conn->query($sqlWeek)) {
    $tmp = [];
    while ($row = $res->fetch_assoc()) {
        $tmp[] = [
            'label' => 'Tuần ' . $row['yw'] . " ({$row['start_date']} - {$row['end_date']})",
            'done'  => (int)$row['done'],
            'total' => (int)$row['total']
        ];
    }
    // đảo ngược lại cho tuần cũ -> mới
    $tmp = array_reverse($tmp);
    foreach ($tmp as $r) {
        $data['weekly']['labels'][] = $r['label'];
        $data['weekly']['done'][]   = $r['done'];
        $data['weekly']['total'][]  = $r['total'];
    }
}

// ----- THỐNG KÊ THEO THÁNG (6 tháng gần nhất) -----
$sqlMonth = "
    SELECT 
        DATE_FORMAT(created_at, '%m/%Y') AS m,
        SUM(status='Hoàn thành') AS done,
        COUNT(*) AS total
    FROM todos
    WHERE user_id = $uid
    GROUP BY m
    ORDER BY MIN(created_at) DESC
    LIMIT 6
";

if ($res = $conn->query($sqlMonth)) {
    $tmp = [];
    while ($row = $res->fetch_assoc()) {
        $tmp[] = [
            'label' => 'Tháng ' . $row['m'],
            'done'  => (int)$row['done'],
            'total' => (int)$row['total']
        ];
    }
    $tmp = array_reverse($tmp);
    foreach ($tmp as $r) {
        $data['monthly']['labels'][] = $r['label'];
        $data['monthly']['done'][]   = $r['done'];
        $data['monthly']['total'][]  = $r['total'];
    }
}

echo json_encode($data);
