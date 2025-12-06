<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$uid  = (int)$user['id'];

// Lấy ID dự án từ URL
if (!isset($_GET['id'])) {
    die("Thiếu ID dự án!");
}
$pid = (int)$_GET['id'];

// LẤY THÔNG TIN DỰ ÁN
$project = $conn->query("
    SELECT * FROM projects 
    WHERE id = $pid AND user_id = $uid
")->fetch_assoc();

if (!$project) {
    die("Không tìm thấy dự án hoặc bạn không có quyền xem.");
}

// LẤY PHÂN TÍCH AI MỚI NHẤT
$analysis = $conn->query("
    SELECT * FROM project_ai_analyses 
    WHERE project_id = $pid 
    ORDER BY created_at DESC 
    LIMIT 1
")->fetch_assoc();

$ai_text = $analysis ? $analysis["result_text"] : "Chưa có phân tích AI.";

// XỬ LÝ XUẤT WORD
if (isset($_GET["export"]) && $analysis) {
    header("Content-Type: application/vnd.ms-word");
    header("Content-Disposition: attachment; filename=project_report_$pid.doc");

    echo "<h2>BÁO CÁO DỰ ÁN</h2>";
    echo "<h3>1. Thông tin dự án</h3>";
    foreach ($project as $k => $v) {
        echo "<b>$k:</b> $v<br>";
    }
    echo "<h3>2. Phân tích AI</h3>";
    echo nl2br($ai_text);

    exit;
}

// LẤY DỮ LIỆU BIỂU ĐỒ AI THEO THÁNG
$chart_query = $conn->query("
    SELECT DATE_FORMAT(created_at, '%m/%Y') AS month, COUNT(*) AS total
    FROM project_ai_analyses
    WHERE project_id = $pid
    GROUP BY month ORDER BY created_at ASC
");

$labels = [];
$values = [];

while ($row = $chart_query->fetch_assoc()) {
    $labels[] = $row["month"];
    $values[] = $row["total"];
}

?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Chi tiết dự án</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.page-title { font-size: 28px; font-weight: 700; }
.section-title { font-size: 20px; font-weight: 600; margin-top: 25px; }
.card-custom { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
pre { white-space: pre-wrap; font-size: 16px; }
</style>

</head>
<body class="bg-light">

<div class="container py-4">

    <button class="btn btn-light mt-4" onclick="history.back();">
    ← Quay lại
</button>


    <h2 class="page-title mb-3">
        <i class="fas fa-folder-open me-2"></i> Chi tiết dự án: <?= htmlspecialchars($project["name"]) ?>
    </h2>

    <!-- THÔNG TIN DỰ ÁN -->
    <div class="card card-custom p-4 mb-4">
        <h4 class="section-title"><i class="fas fa-info-circle me-2 text-primary"></i>1. Thông tin dự án</h4>

        <p><b>Độ phức tạp:</b> <?= $project["complexity"] ?></p>
        <p><b>Thời gian dự kiến:</b> <?= $project["expected_duration_months"] ?> tháng</p>
        <p><b>Ngân sách:</b> <?= number_format($project["expected_budget"],0,",",".") ?> VND</p>
        <p><b>Mô tả:</b><br><?= nl2br($project["description"]) ?></p>
    </div>

    <!-- PHÂN TÍCH AI -->
    <div class="card card-custom p-4 mb-4">
        <h4 class="section-title">
            <i class="fas fa-robot me-2 text-success"></i>
            2. Phân tích AI mới nhất
        </h4>

        <pre><?= htmlspecialchars($ai_text) ?></pre>

        <div class="text-end mt-3">
            <a href="project_ai_view.php?id=<?= $pid ?>&reanalyze=1" class="btn btn-warning">
                <i class="fas fa-sync-alt me-2"></i> Phân tích lại bằng AI
            </a>

            <a href="project_ai_view.php?id=<?= $pid ?>&export=1" class="btn btn-primary">
                <i class="fas fa-file-word me-2"></i> Xuất báo cáo Word
            </a>
        </div>
    </div>

    <!-- BÁO CÁO DỰ ÁN -->
    <div class="card card-custom p-4 mb-4">
        <h4 class="section-title"><i class="fas fa-chart-line me-2 text-danger"></i>3. Báo cáo đánh giá dự án</h4>

        <ul>
            <li><b>Mức độ khả thi:</b> đánh giá dựa trên độ phức tạp & ngân sách</li>
            <li><b>Rủi ro:</b> thay đổi yêu cầu, thiếu nhân lực, vượt chi phí</li>
            <li><b>Tiến độ dự kiến:</b> phân theo từng giai đoạn (phân tích – thiết kế – lập trình – testing – triển khai)</li>
            <li><b>Đề xuất AI:</b> cải thiện hiệu suất, tối ưu database, chọn công nghệ phù hợp</li>
        </ul>
    </div>

    <!-- BIỂU ĐỒ AI -->
    <div class="card card-custom p-4 mb-4">
        <h4 class="section-title"><i class="fas fa-chart-pie me-2 text-info"></i>4. Biểu đồ phân tích AI theo tháng</h4>

        <canvas id="aiChart" height="120"></canvas>
    </div>

</div>

<script>
const ctx = document.getElementById("aiChart");
new Chart(ctx, {
    type: "line",
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: "Số lần phân tích AI",
            data: <?= json_encode($values) ?>,
            borderColor: "blue",
            borderWidth: 2,
            fill: false,
            tension: 0.3
        }]
    }
});
</script>
<style>
/* =======================
   ROOT VARIABLES (THEME)
   ======================= */
:root {
    --sidebar-width: 260px;
    --header-height: 70px;
    --primary-color: #00a8e8;
    --secondary-color: #3f6583;
    --accent-color: #198754;
    --sidebar-bg: #1f2937;
    --content-bg: #f5f7fa;
    --text-light: #e5e7eb;
    --card-bg: #ffffff;
    --card-radius: 14px;
    --shadow: 0 4px 14px rgba(0,0,0,0.08);
}

/* =======================
   GLOBAL
   ======================= */
body {
    background: var(--content-bg);
    font-family: 'Inter', sans-serif;
    margin: 0;
    padding: 0;
    color: #333;
}

/* =======================
   PAGE STRUCTURE
   ======================= */
.container {
    margin-top: 40px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
}

/* =======================
   CARD UI / SECTION
   ======================= */
.card-custom {
    background: var(--card-bg);
    border-radius: var(--card-radius);
    padding: 25px 30px;
    margin-bottom: 25px;
    box-shadow: var(--shadow);
    border-left: 5px solid var(--primary-color);
}

.section-title {
    font-size: 22px;
    font-weight: 700;
    display: flex;
    align-items: center;
    margin-bottom: 14px;
    color: #222;
}

.section-title i {
    margin-right: 10px;
    color: var(--primary-color);
}

/* =======================
   TEXT BLOCK – AI CONTENT
   ======================= */
.analysis-box,
pre {
    background: #f0f2f5;
    padding: 15px;
    border-radius: 10px;
    white-space: pre-wrap;
    border: 1px solid #ddd;
    font-size: 15px;
}

/* =======================
   BUTTONS
   ======================= */
.btn-custom {
    padding: 10px 18px;
    border-radius: 10px;
    font-weight: 600;
    transition: 0.2s;
}

.btn-custom:hover {
    opacity: 0.85;
    transform: translateY(-2px);
}

.btn-primary {
    background: var(--primary-color);
    border: none;
}

.btn-warning {
    background: #ff9800;
    border: none;
    color: #fff;
}

/* =======================
   BADGE
   ======================= */
.badge-score {
    font-size: 26px;
    font-weight: 700;
    padding: 10px 22px;
    border-radius: 8px;
}

/* =======================
   GANTT CHART BOX
   ======================= */
#ganttChart {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--shadow);
}

.gantt-bar {
    height: 30px;
    background: #4CAF50;
    border-radius: 6px;
}

/* =======================
   RESPONSIVE
   ======================= */
@media (max-width: 768px) {
    .section-title {
        font-size: 18px;
    }
    .card-custom {
        padding: 20px;
    }
}

</style>
</body>
</html>
