<?php
session_start();
require_once "../config/db.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(["labels" => [], "values" => []]);
    exit;
}

$uid = (int)$_SESSION['user']['id'];

// Query thống kê AI theo tháng
$sql = "
SELECT 
    DATE_FORMAT(pa.created_at, '%m/%Y') AS month,
    COUNT(*) AS total
FROM project_ai_analyses pa
JOIN projects p ON pa.project_id = p.id
WHERE p.user_id = $uid
GROUP BY month
ORDER BY pa.created_at ASC
";

$result = $conn->query($sql);

$labels = [];
$values = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row["month"];
        $values[] = (int)$row["total"];
    }
}

// Trả JSON đúng chuẩn
header("Content-Type: application/json; charset=UTF-8");
echo json_encode([
    "labels" => $labels,
    "values" => $values
]);
