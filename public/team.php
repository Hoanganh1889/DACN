<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$uid  = $user['id'];

/* =============================
   TẠO NHÓM MỚI
============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_team'])) {

    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);

    if ($name !== "") {
        $stmt = $conn->prepare("INSERT INTO teams (owner_id, name, description) VALUES (?,?,?)");
        $stmt->bind_param("iss", $uid, $name, $desc);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: team.php");
    exit;
}

/* =============================
   LẤY DANH SÁCH NHÓM CỦA USER
============================= */
$teams = $conn->query("
    SELECT t.*,
        (SELECT COUNT(*) FROM team_members WHERE team_id=t.id) AS member_count
    FROM teams t
    WHERE t.owner_id=$uid
    ORDER BY t.created_at DESC
");

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Nhóm làm việc</title>

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
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.team-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    padding: 18px;
    border-radius: 10px;
    margin-bottom: 14px;
    transition: 0.2s;
}
.team-card:hover {
    background: #eef2ff;
}

.team-name {
    font-size: 1.2rem;
    font-weight: 700;
}

.back-btn {
    padding: 10px 16px;
    background: #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
}
.back-btn:hover {
    background: #d1d5db;
}
</style>

</head>
<body>

<div class="container-box">

    <!-- NÚT QUAY LẠI -->
   <button class="btn btn-light mt-4" onclick="history.back();">
    ← Quay lại
</button>

    <h2 class="page-title">
        <i class="fas fa-users me-2"></i> Nhóm làm việc
    </h2>

    <!-- TẠO NHÓM -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createTeamModal">
        + Tạo nhóm mới
    </button>

    <!-- DANH SÁCH NHÓM -->
    <?php if ($teams->num_rows == 0): ?>
        <p class="text-muted">Bạn chưa có nhóm nào. Hãy tạo nhóm mới!</p>
    <?php else: ?>

        <?php while($t = $teams->fetch_assoc()): ?>
            <div class="team-card d-flex justify-content-between">
                <div>
                    <div class="team-name"><?= htmlspecialchars($t["name"]) ?></div>
                    <div class="text-muted small">
                        Thành viên: <?= $t["member_count"] ?>
                    </div>
                    <div class="mt-1">
                        <small><?= nl2br(htmlspecialchars($t["description"])) ?></small>
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    <a href="team_view.php?id=<?= $t['id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-eye me-1"></i> Xem
                    </a>
                </div>
            </div>
        <?php endwhile; ?>

    <?php endif; ?>

</div>


<!-- MODAL TẠO NHÓM -->
<div class="modal fade" id="createTeamModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">

            <input type="hidden" name="create_team" value="1">

            <div class="modal-header">
                <h5 class="modal-title">Tạo nhóm mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                
                <label class="fw-bold">Tên nhóm:</label>
                <input type="text" name="name" class="form-control mb-3" required>

                <label class="fw-bold">Mô tả:</label>
                <textarea name="description" class="form-control mb-3" rows="3"></textarea>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button class="btn btn-primary">Lưu nhóm</button>
            </div>

        </form>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
