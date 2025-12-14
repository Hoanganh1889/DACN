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
   XÓA TOÀN BỘ THÔNG BÁO
============================= */
if (isset($_GET['clear'])) {
    $conn->query("DELETE FROM notifications WHERE user_id = $uid");
    header("Location: notifications.php");
    exit;
}

/* =============================
   ĐÁNH DẤU ĐÃ ĐỌC
============================= */
if (isset($_GET['read'])) {
    $nid = intval($_GET['read']);
    $conn->query("UPDATE notifications SET is_read = 1 WHERE id=$nid AND user_id=$uid");
    header("Location: notifications.php");
    exit;
}

/* =============================
   LẤY THÔNG BÁO
============================= */
$noti = $conn->query("
    SELECT *
    FROM notifications
    WHERE user_id = $uid
    ORDER BY created_at DESC
");
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Thông báo</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
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
    max-width: 850px;
    margin: 35px auto;
    padding: 25px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0px 4px 12px rgba(0,0,0,0.1);
}

.back-btn {
    display: inline-block;
    margin-bottom: 15px;
    background: #e5e7eb;
    padding: 10px 16px;
    border-radius: 8px;
    color: #333;
    text-decoration: none;
}
.back-btn:hover {
    background: #d1d5db;
}

.page-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.noti-card {
    padding: 15px;
    background: #ffffff;
    border-radius: 10px;
    border-left: 5px solid transparent;
    margin-bottom: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.06);
}

.noti-unread {
    background: #eef4ff;
    border-left-color: #0d6efd;
}

.noti-message {
    font-size: 1rem;
    font-weight: 500;
}

.noti-time {
    font-size: 0.8rem;
    color: #666;
}
</style>
</head>

<body>

<div class="container-box">

    <a onclick="history.back()" class="back-btn">← Quay lại</a>

    <h2 class="page-title"><i class="fas fa-bell me-2"></i>Thông báo</h2>

    <a href="?clear=1" class="btn btn-danger btn-sm mb-3">
        <i class="fas fa-trash"></i> Xóa tất cả
    </a>

    <?php if ($noti->num_rows == 0): ?>
        <div class="alert alert-info">Không có thông báo nào.</div>

    <?php else: ?>
        <?php while($n = $noti->fetch_assoc()): ?>

            <div class="noti-card <?= $n['is_read'] ? '' : 'noti-unread' ?>">
                <div class="noti-message">
                    <?= htmlspecialchars($n["message"]) ?>
                </div>
                <div class="noti-time">
                    <i class="fas fa-clock"></i>
                    <?= date("H:i d/m/Y", strtotime($n["created_at"])) ?>
                </div>

                <div class="mt-2">
                    <?php if (!$n['is_read']): ?>
                        <a href="?read=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary">
                            Đánh dấu đã đọc
                        </a>
                    <?php endif; ?>

                    <?php if ($n["link"]): ?>
                        <a href="<?= $n['link'] ?>" class="btn btn-sm btn-primary">
                            Xem chi tiết
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        <?php endwhile; ?>
    <?php endif; ?>

</div>

</body>
</html>
