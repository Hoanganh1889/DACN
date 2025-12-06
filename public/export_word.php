<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    die("Bạn chưa đăng nhập.");
}

if (!isset($_GET['id'])) {
    die("Thiếu ID dự án.");
}

$project_id = (int)$_GET['id'];
$uid        = (int)$_SESSION['user']['id'];

// Lấy thông tin dự án + phân tích AI mới nhất
$stmt = $conn->prepare("
    SELECT p.*, a.result_text
    FROM projects p
    LEFT JOIN project_ai_analyses a 
        ON a.project_id = p.id
    WHERE p.id = ? AND p.user_id = ?
    ORDER BY a.created_at DESC
    LIMIT 1
");
$stmt->bind_param("ii", $project_id, $uid);
$stmt->execute();
$result  = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    die("Không tìm thấy dự án.");
}

$project_name = $project['name'];
$description  = $project['description'];
$complexity   = $project['complexity'];
$duration     = (int)$project['expected_duration_months'];
$budget       = number_format((float)$project['expected_budget'], 0, ',', '.');
$analysis     = $project['result_text'] ?? "Chưa có kết quả phân tích AI.";

// Chuẩn bị HTML để Word đọc được
$html  = "<html><head><meta charset='utf-8'></head><body>";
$html .= "<h1 style='text-align:center;'>BÁO CÁO PHÂN TÍCH DỰ ÁN</h1>";
$html .= "<h2>$project_name</h2>";

$html .= "<p><strong>Độ phức tạp:</strong> $complexity</p>";
$html .= "<p><strong>Thời gian dự kiến:</strong> $duration tháng</p>";
$html .= "<p><strong>Ngân sách:</strong> $budget VND</p>";

$html .= "<h3>Mô tả dự án</h3>";
$html .= "<p>" . nl2br(htmlspecialchars($description)) . "</p>";

$html .= "<h3>Kết quả phân tích AI</h3>";
$html .= "<div style='border:1px solid #ccc;padding:10px;'>";
$html .= nl2br(htmlspecialchars($analysis));
$html .= "</div>";

$html .= "</body></html>";

// Tên file tải về
$fileName = "Phan_tich_du_an_" . $project_id . ".doc";

// Header cho trình duyệt tải file Word
header("Content-Type: application/msword; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$fileName\"");

echo $html;
exit;
