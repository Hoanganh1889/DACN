<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION["user"];
$uid  = $user["id"];

/* =============================
   LẤY DANH SÁCH DỰ ÁN + PHÂN TÍCH AI GẦN NHẤT
============================= */
$projects = $conn->query("
    SELECT p.*,
        (
            SELECT result_text 
            FROM project_ai_analyses 
            WHERE project_id = p.id 
            ORDER BY created_at DESC 
            LIMIT 1
        ) AS ai_result
    FROM projects p
    WHERE p.user_id = $uid
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Dự án của tôi</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
body {
    background: #f3f4f6;
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, rgba(0,150,200,0.5), rgba(0,0,70,0.4)),
                  url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1350&q=80');
      background-size: cover;
      background-position: center;
      backdrop-filter: blur(10px);
}

.container-box {
    max-width: 1100px;
    margin: 40px auto;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
}

/* CARD PROJECT */
.project-card {
    background: white;
    border-radius: 14px;
    padding: 22px;
    border: 1px solid #e5e7eb;
    transition: 0.25s;
}
.project-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    transform: translateY(-3px);
}

.badge {
    font-size: 0.75rem;
}

.ai-preview {
    background: #f9fafb;
    border-left: 4px solid #3b82f6;
    padding: 10px 14px;
    border-radius: 6px;
    font-size: 0.9rem;
    max-height: 100px;
    overflow: hidden;
}

.actions a {
    margin-right: 10px;
}
</style>

</head>
<body>

<div class="container-box">

    <h2 class="page-title mb-4"><i class="fas fa-folder-open me-2"></i> Dự án của tôi</h2>

    <a href="project_ai.php" class="btn btn-primary mb-4">
        <i class="fas fa-plus-circle me-1"></i> Tạo dự án mới
    </a>

    <?php if ($projects->num_rows === 0): ?>
        <div class="alert alert-info">Bạn chưa có dự án nào. Hãy tạo dự án mới!</div>
    <?php endif; ?>

    <div class="row g-4">
        <?php while ($p = $projects->fetch_assoc()): ?>

        <div class="col-md-6">
            <div class="project-card">

                <h4 class="fw-bold"><?= htmlspecialchars($p["name"]) ?></h4>

                <div class="mb-2">
                    <span class="badge bg-secondary"><?= $p["complexity"] ?></span>
                    <span class="badge bg-info text-dark"><?= $p["expected_duration_months"] ?> tháng</span>
                    <span class="badge bg-success"><?= number_format($p["expected_budget"]) ?> VND</span>
                </div>

                <p class="text-muted">
                    <?= nl2br(htmlspecialchars(substr($p["description"], 0, 120))) ?>...
                </p>

                <?php if ($p["ai_result"]): ?>
                    <div class="ai-preview mb-3">
                        <?= nl2br(htmlspecialchars(substr($p["ai_result"], 0, 200))) ?>...
                    </div>
                <?php else: ?>
                    <p class="text-muted">Chưa có phân tích AI.</p>
                <?php endif; ?>

                <div class="actions mt-3">
                    <a href="project_ai_view.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i> Xem chi tiết
                    </a>

                    <?php if ($p["ai_result"]): ?>
                        <a href="project_ai_view.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-robot"></i> Xem phân tích AI
                        </a>
                    <?php endif; ?>

                    <a href="project_delete.php?id=<?= $p['id'] ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Xóa dự án này?');">
                        <i class="fas fa-trash"></i> Xóa
                    </a>
                </div>

            </div>
        </div>

        <?php endwhile; ?>
    </div>

    <button class="btn btn-light mt-4" onclick="history.back();">
    ← Quay lại
</button>
</div>

</body>
</html>
