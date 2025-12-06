<?php
session_start();
require_once '../config/db.php';

// ================== KIỂM TRA QUYỀN ADMIN ==================
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? 'user') !== 'admin') {
    echo "<p class='text-danger p-4'>🚫 Không có quyền truy cập.</p>";
    exit;
}

$user = $_SESSION['user'];

// ================== LẤY DANH SÁCH USER ==================
$users = $conn->query("SELECT id, username FROM users ORDER BY username ASC");

// ================== THÊM CÔNG VIỆC ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $deadline = $_POST['deadline'] ?? null;
    $status = $_POST['status'] ?? 'Chưa làm';
    $assigned_to = (int)$_POST['user_id'];

    if ($title !== '' && $assigned_to > 0) {
        $stmt = $conn->prepare("INSERT INTO todos (user_id, title, status, deadline) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isss', $assigned_to, $title, $status, $deadline);
        $stmt->execute();
    }
    header('Location: admin_todo.php');
    exit;
}

// ================== HÀNH ĐỘNG: XÓA / HOÀN THÀNH ==================
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM todos WHERE id=$id");
    header('Location: admin_todo.php');
    exit;
}
if (isset($_GET['done'])) {
    $id = (int)$_GET['done'];
    $conn->query("UPDATE todos SET status='Hoàn thành' WHERE id=$id");
    header('Location: admin_todo.php');
    exit;
}

// ================== LẤY CÔNG VIỆC ==================
$todos = $conn->query("
    SELECT t.*, u.username 
    FROM todos t 
    JOIN users u ON t.user_id=u.id 
    ORDER BY t.created_at DESC
");
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý công việc (Admin)</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
:root {
    --sidebar-width: 280px;
    --header-height: 70px;
    --primary-color: #00a8e8;
    --secondary-color: #3f6583;
    --sidebar-bg: #1f2937;
    --content-bg: #f5f7fa;
    --text-light: #e5e7eb;
    --card-bg: #ffffff;
    --card-border-radius: 12px;
}

/* ===== BODY ===== */
body {
    font-family: 'Inter', sans-serif;
    background-color: var(--content-bg);
    margin: 0;
    padding-top: var(--header-height);
    min-height: 100vh;
    color: #333;
    overflow-x: hidden;
}

/* ===== HEADER ===== */
.header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: var(--header-height);
    background-color: white;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    z-index: 1000;
    padding: 0 30px;
}
.header .logo {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--primary-color);
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: var(--sidebar-width);
    background: var(--sidebar-bg);
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    padding-top: var(--header-height);
    color: var(--text-light);
    overflow-y: auto;
    box-shadow: 4px 0 8px rgba(0,0,0,0.25);
}

.sidebar nav a {
    padding: 14px 28px;
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: var(--text-light);
    border-left: 4px solid transparent;
    transition: 0.2s;
}
.sidebar nav a.active {
    background: var(--primary-color);
    color: #fff;
    border-left-color: #fff;
}
.sidebar nav a:hover {
    background: var(--secondary-color);
}

/* LABEL */
.sidebar-label {
    padding: 10px 30px;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #9ca3af;
    opacity: 0.8;
}

/* ===== MAIN CONTENT ===== */
.content-wrapper {
    margin-left: var(--sidebar-width);
    padding: 30px;
}

/* ===== CARD ===== */
.card {
    border: none;
    border-radius: var(--card-border-radius);
    box-shadow: 0 8px 16px rgba(0,0,0,0.08);
}
.card-header {
    background: var(--primary-color);
    color: white;
    font-weight: 600;
    font-size: 1.2rem;
    padding: 18px 25px;
    border-radius: var(--card-border-radius) var(--card-border-radius) 0 0;
}

/* ===== TABLE ===== */
.table thead th {
    background: var(--content-bg);
    border-bottom: 2px solid var(--primary-color);
    color: var(--secondary-color);
    text-transform: uppercase;
}
.table tbody tr:hover {
    background: #e8f8ff !important;
}

/* BUTTON FIX */
.btn-sm {
    padding: 5px 10px !important;
    border-radius: 8px !important;
}
</style>
</head>
<body>

<!-- ================== HEADER ================== -->
<div class="header d-flex align-items-center">
    <button class="btn btn-outline-secondary me-3 d-lg-none" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <h4 class="logo mb-0">DACN</h4>

    <div class="ms-auto">
        <span class="me-3 text-secondary">
            <i class="fas fa-user-circle me-1"></i><?=htmlspecialchars($user['username'])?>
        </span>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">
            <i class="fas fa-sign-out-alt me-1"></i>Đăng xuất
        </a>
    </div>
</div>

<!-- ================== SIDEBAR ================== -->
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

<!-- ================== MAIN CONTENT ================== -->
<main class="content-wrapper">

    <!-- GIAO VIỆC -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle me-2"></i> Giao công việc mới
        </div>

        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-lg-4">
                    <input class="form-control" name="title" placeholder="Tên công việc..." required>
                </div>

                <div class="col-lg-3">
                    <select class="form-select" name="user_id" required>
                        <option value="">-- Chọn người nhận --</option>
                        <?php while($u = $users->fetch_assoc()): ?>
                        <option value="<?=$u['id']?>"><?=$u['username']?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-lg-2">
                    <input type="date" class="form-control" name="deadline">
                </div>

                <div class="col-lg-2">
                    <select class="form-select" name="status">
                        <option>Chưa làm</option>
                        <option>Đang làm</option>
                        <option>Hoàn thành</option>
                    </select>
                </div>

                <div class="col-lg-1">
                    <button class="btn btn-success w-100">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DANH SÁCH CÔNG VIỆC -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list-check me-2"></i> Danh sách công việc toàn hệ thống
        </div>

        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Người nhận</th>
                        <th>Công việc</th>
                        <th>Trạng thái</th>
                        <th>Hạn chót</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($r = $todos->fetch_assoc()): ?>
                    <tr>
                        <td><i class="fas fa-user text-secondary me-1"></i><?=$r['username']?></td>
                        <td><?=htmlspecialchars($r['title'])?></td>

                        <td>
                            <?php
                            $st = $r['status'];
                            $badge = $st === 'Hoàn thành' ? 'success' : ($st === 'Đang làm' ? 'warning' : 'danger');
                            echo "<span class='badge bg-$badge'>$st</span>";
                            ?>
                        </td>

                        <td><?= $r['deadline'] ? date('d/m/Y', strtotime($r['deadline'])) : '—' ?></td>

                        <td class="text-center">
                            <?php if($r['status'] !== 'Hoàn thành'): ?>
                            <a href="?done=<?=$r['id']?>" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php endif; ?>

                            <a href="?delete=<?=$r['id']?>" onclick="return confirm('Xóa công việc này?')"
                               class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                    <?php if ($todos->num_rows === 0): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Không có công việc nào.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>


<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function(){
    document.getElementById('sidebar').classList.toggle('show');
});
</script>

</body>
</html>
