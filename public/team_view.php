<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$uid  = $user['id'];

if (!isset($_GET['id'])) {
    die("<h3>❌ Nhóm không tồn tại.</h3>");
}

$team_id = intval($_GET['id']);

/* ================================
    LẤY THÔNG TIN NHÓM
================================ */
$team = $conn->query("
    SELECT t.*, u.username AS owner_name
    FROM teams t
    JOIN users u ON u.id = t.owner_id
    WHERE t.id = $team_id
")->fetch_assoc();

if (!$team) {
    die("<h3>❌ Nhóm không tồn tại.</h3>");
}

/* ================================
    LẤY DANH SÁCH THÀNH VIÊN
================================ */
$members = $conn->query("
    SELECT tm.*, u.username 
    FROM team_members tm
    JOIN users u ON u.id = tm.user_id
    WHERE tm.team_id = $team_id
    ORDER BY tm.role DESC, u.username
");

/* ================================
    QUYỀN OWNER
================================ */
$is_owner = ($team['owner_id'] == $uid);

/* ================================
    THÊM THÀNH VIÊN
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member']) && $is_owner) {

    $user_add = intval($_POST['user_id']);

    // Không cho thêm lại thành viên
    $exists = $conn->query("
        SELECT id FROM team_members 
        WHERE team_id=$team_id AND user_id=$user_add
    ");

    if ($exists->num_rows == 0) {
        $conn->query("
            INSERT INTO team_members (team_id, user_id, role)
            VALUES ($team_id, $user_add, 'member')
        ");
    }

    header("Location: team_view.php?id=$team_id");
    exit;
}

/* ================================
    XÓA THÀNH VIÊN
================================ */
if (isset($_GET['remove']) && $is_owner) {
    $remove_id = intval($_GET['remove']);

    // không thể xóa chính chủ nhóm
    if ($remove_id != $team['owner_id']) {
        $conn->query("
            DELETE FROM team_members 
            WHERE team_id=$team_id AND user_id=$remove_id
        ");
    }

    header("Location: team_view.php?id=$team_id");
    exit;
}

/* ================================
    LẤY USER ĐỂ THÊM THÀNH VIÊN
================================ */
$all_users = $conn->query("
    SELECT id, username 
    FROM users 
    WHERE id NOT IN (SELECT user_id FROM team_members WHERE team_id=$team_id)
    ORDER BY username ASC
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Nhóm làm việc - <?=htmlspecialchars($team['name'])?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>

<style>
body {
    background: #f3f4f6;
    font-family: 'Inter', sans-serif;
}

.container-box {
    max-width: 900px;
    margin: 30px auto;
    padding: 25px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.back-btn {
    padding: 10px 16px;
    background: #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
}
.back-btn:hover { background: #d1d5db; }

.member-item {
    padding: 12px;
    border-radius: 8px;
    background: #f9fafb;
    border: 1px solid #ddd;
}
.member-item:hover { background: #eef2ff; }

.role-badge {
    padding: 4px 8px;
    font-size: 0.75rem;
}
</style>

</head>
<body>

<div class="container-box">

    <button class="btn btn-light mt-4" onclick="history.back();">
    ← Quay lại
</button>

    <h2 class="fw-bold mb-3">
        <i class="fas fa-users me-2"></i> Nhóm: <?=htmlspecialchars($team['name'])?>
    </h2>

    <p><b>Trưởng nhóm:</b> <?=$team["owner_name"]?></p>

    <?php if ($team["description"]): ?>
        <p><b>Mô tả:</b> <?= nl2br(htmlspecialchars($team["description"])) ?></p>
    <?php endif; ?>

    <hr>

    <h4 class="fw-bold">👥 Thành viên</h4>

    <div class="list-group mb-4">

        <?php while ($m = $members->fetch_assoc()): ?>
            <div class="list-group-item member-item d-flex justify-content-between">

                <div>
                    <b><?= htmlspecialchars($m["username"]) ?></b><br>
                    <span class="role-badge bg-<?=$m['role']=='owner'?'warning text-dark':'secondary'?>">
                        <?=$m['role']=='owner'?'Trưởng nhóm':'Thành viên'?>
                    </span>
                </div>

                <?php if ($is_owner && $m['user_id'] != $team['owner_id']): ?>
                    <a href="?id=<?=$team_id?>&remove=<?=$m['user_id']?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Xóa thành viên này?')">
                        Xóa
                    </a>
                <?php endif; ?>

            </div>
        <?php endwhile; ?>

    </div>

    <?php if ($is_owner): ?>
        <h4 class="fw-bold">➕ Thêm thành viên mới</h4>

        <form method="POST" class="row g-3">
            <input type="hidden" name="add_member" value="1">

            <div class="col-md-8">
                <select name="user_id" class="form-select" required>
                    <option value="">-- Chọn thành viên --</option>
                    <?php while($u = $all_users->fetch_assoc()): ?>
                        <option value="<?=$u['id']?>"><?=$u['username']?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-4">
                <button class="btn btn-primary w-100">
                    <i class="fas fa-user-plus me-1"></i> Thêm vào nhóm
                </button>
            </div>
        </form>
    <?php endif; ?>

</div>

</body>
</html>
