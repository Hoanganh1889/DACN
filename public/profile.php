<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$uid = (int)$user['id'];

// ===== Cập nhật thông tin cá nhân =====
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_pass'])) {
        $old = $_POST['old_pass'] ?? '';
        $new = $_POST['new_pass'] ?? '';
        $cfm = $_POST['confirm_pass'] ?? '';
        if ($new !== $cfm) {
            $msg = '<div class="alert alert-danger">❌ Mật khẩu mới không khớp.</div>';
        } else {
            $r = $conn->query("SELECT password FROM users WHERE id=$uid")->fetch_assoc();
            if ($r && password_verify($old, $r['password'])) {
                $hash = password_hash($new, PASSWORD_BCRYPT);
                $conn->query("UPDATE users SET password='$hash' WHERE id=$uid");
                $msg = '<div class="alert alert-success">✅ Đổi mật khẩu thành công.</div>';
            } else $msg = '<div class="alert alert-danger">⚠️ Mật khẩu cũ không đúng.</div>';
        }
    }
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $targetDir = '../uploads/avatars/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $filename = "user{$uid}_" . time() . "." . $ext;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $targetDir . $filename);
            $conn->query("UPDATE users SET avatar='uploads/avatars/$filename' WHERE id=$uid");
            $msg = '<div class="alert alert-success">🖼️ Cập nhật ảnh đại diện thành công!</div>';
        } else {
            $msg = '<div class="alert alert-warning">⚠️ Chỉ hỗ trợ ảnh JPG, PNG, GIF, WEBP.</div>';
        }
    }
}

// ===== Lấy thông tin user mới nhất =====
$u = $conn->query("SELECT username, email, role, created_at, status, avatar FROM users WHERE id=$uid")->fetch_assoc();
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Thông tin cá nhân</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
:root {
            --sidebar-width: 280px;
            --header-height: 70px;
            --secondary-color: #3f6583; /* Darker blue/grey for sidebar hover */
            --sidebar-bg: #1f2937; /* Dark Slate Blue/Grey */
            --content-bg: #f5f7fa; /* Light grey background */
            --text-light: #e5e7eb;
            --card-bg: #ffffff;
            --card-border-radius: 12px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--content-bg);
            margin: 0;
            padding-top: var(--header-height); 
            min-height: 100vh;
            color: #333;
        }
        
        /* 1. HEADER */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--header-height);
            background-color: var(--card-bg);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            z-index: 1030;
            transition: left 0.3s;
            padding: 0 30px;
        }
        .header .logo {
            color: var(--primary-color);
            font-weight: 800;
            font-size: 1.6rem;
            letter-spacing: -0.5px;
        }
        .sidebar-label {
    padding: 10px 30px;
    color: #9ca3af;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.8;
}
.sidebar nav a {
    margin-bottom: 5px;
    border-radius: 4px;
}
.sidebar nav a:hover {
    background: rgba(255,255,255,0.08);
}
        /* 2. SIDEBAR */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: var(--text-light);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            padding-top: var(--header-height); 
            box-shadow: 4px 0 8px rgba(0, 0, 0, 0.2);
            z-index: 1020;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94); /* Smooth transition */
            overflow-x: hidden;
scrollbar-width: thin;

        }

        .sidebar h3 {
            padding: 25px 30px 15px;
            margin-bottom: 20px;
            color: #ffffff;
            font-size: 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            padding: 15px 30px;
            color: var(--text-light);
            text-decoration: none;
            transition: background-color 0.2s, color 0.2s;
            border-left: 4px solid transparent;
        }

        .sidebar nav a:hover {
            background-color: var(--secondary-color);
            border-left-color: var(--primary-color);
        }

        .sidebar nav a.active {
            background-color: var(--primary-color);
            border-left: 4px solid white;
            color: #fff;
            font-weight: 600;
        }
.content {
    margin-left: var(--sidebar-width);
    padding: 30px;
}
.avatar {
    width: 120px; height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary-color);
}
.card {
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}
</style>
</head>
<body>

<!-- HEADER -->
<div class="header d-flex align-items-center">
    <button class="btn btn-outline-secondary d-lg-none me-3 toggle-btn" id="sidebarToggle" aria-label="Toggle Navigation">
        <i class="fas fa-bars"></i>
    </button>
    <h4 class="logo mb-0">DACN</h4>
    <div class="ms-auto d-flex align-items-center">
        <span class="d-none d-sm-inline me-3 text-secondary">
            <i class="fas fa-user-circle me-1"></i> Xin chào, <strong><?=htmlspecialchars($user['username'])?></strong>
        </span>
        <a href="logout.php" class="btn btn-sm btn-outline-danger" style="border-radius: 8px;">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>
    </div>
</div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <h3><i class="fas fa-rocket me-2"></i> TRANG CHỦ</h3>
    <nav>

    <!-- NHÓM HỆ THỐNG -->
    <div class="sidebar-label">Hệ thống</div>
    <a class="active" href="dashboard.php"><i class="fas fa-chart-line fa-fw me-3"></i> Dashboard</a>

    <?php if($user['role']==='admin'):?>
    <a href="admin.php"><i class="fas fa-users-cog fa-fw me-3"></i> Quản Lý Admin</a>
    <a href="system_logs.php"><i class="fas fa-file-alt fa-fw me-3"></i> Nhật ký hệ thống</a>
    <a href="settings.php"><i class="fas fa-cog fa-fw me-3"></i> Cài đặt hệ thống</a>
    <?php endif;?>

    <!-- NHÓM AI -->
    <div class="sidebar-label mt-3">Trí tuệ nhân tạo</div>
    <a href="project_ai.php"><i class="fas fa-brain fa-fw me-3"></i> Phân tích dự án (AI)</a>
    <a href="ai_insights.php"><i class="fas fa-lightbulb fa-fw me-3"></i> Gợi ý thông minh</a>
    <a href="ai_compare.php"><i class="fas fa-robot fa-fw me-3"></i> So sánh mô hình AI</a>

    <!-- NHÓM CÔNG VIỆC -->
    <div class="sidebar-label mt-3">Quản lý công việc</div>
    <a href="todo.php"><i class="fas fa-clipboard-list fa-fw me-3"></i> Công việc</a>
    <a href="calendar.php"><i class="fas fa-calendar-alt fa-fw me-3"></i> Lịch làm việc</a>
    <a href="projects.php"><i class="fas fa-folder-open fa-fw me-3"></i> Dự án</a>
    <a href="team.php"><i class="fas fa-users fa-fw me-3"></i> Nhóm làm việc</a>

    <!-- MẠNG XÃ HỘI -->
    <div class="sidebar-label mt-3">Mạng xã hội</div>
    <a href="social.php"><i class="fas fa-share-alt fa-fw me-3"></i> Dòng thời gian</a>
    <a href="messages.php"><i class="fas fa-envelope fa-fw me-3"></i> Tin nhắn</a>
    <a href="notifications.php"><i class="fas fa-bell fa-fw me-3"></i> Thông báo</a>

    <!-- NGƯỜI DÙNG -->
    <div class="sidebar-label mt-3">Tài khoản</div>
    <a href="profile.php"><i class="fas fa-user-circle fa-fw me-3"></i> Hồ sơ cá nhân</a>
    <a href="settings_user.php"><i class="fas fa-sliders-h fa-fw me-3"></i> Cài đặt</a>

    <!-- CHAT -->
    <div class="sidebar-label mt-3">Liên lạc</div>
    <a href="chat.php"><i class="fas fa-comments fa-fw me-3"></i> Chat</a>
    <a href="calls.php"><i class="fas fa-phone fa-fw me-3"></i> Cuộc gọi</a>

</nav>
</aside>

<!-- MAIN CONTENT -->
<main class="content-wrapper">
<div class="content">
    <div class="container">
        <?= $msg ?>
        <div class="card p-4 mb-4">
            <div class="d-flex align-items-center">
                <img src="../<?= htmlspecialchars($u['avatar'] ?: 'assets/default-avatar.png') ?>" class="avatar me-4" alt="Avatar">
                <div>
                    <h4 class="fw-bold text-primary mb-1"><?= htmlspecialchars($u['username']) ?></h4>
                    <p class="mb-0 text-muted"><?= htmlspecialchars($u['email']) ?></p>
                    <span class="badge bg-info mt-2"><?= htmlspecialchars(strtoupper($u['role'])) ?></span>
                </div>
            </div>
            <hr>
            <p><strong>Trạng thái:</strong> <?= htmlspecialchars($u['status']) ?></p>
            <p><strong>Ngày tạo:</strong> <?= date('d/m/Y', strtotime($u['created_at'])) ?></p>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card p-4">
                    <h5 class="text-primary mb-3"><i class="fas fa-lock me-2"></i> Đổi mật khẩu</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <input type="password" class="form-control" name="old_pass" placeholder="Mật khẩu cũ" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="new_pass" placeholder="Mật khẩu mới" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="confirm_pass" placeholder="Nhập lại mật khẩu mới" required>
                        </div>
                        <button name="change_pass" class="btn btn-success w-100">Cập nhật mật khẩu</button>
                    </form>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4">
                    <h5 class="text-primary mb-3"><i class="fas fa-image me-2"></i> Cập nhật ảnh đại diện</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input class="form-control" type="file" name="avatar" accept="image/*" required>
                        </div>
                        <button class="btn btn-primary w-100">Tải lên</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</main>
</body>
</html>
