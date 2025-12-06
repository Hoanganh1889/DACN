<?php
session_start();
require_once '../config/db.php';
// --- Logic Xác Thực và Xử Lý (GIỮ NGUYÊN THEO YÊU CẦU) ---
if (!isset($_SESSION['user'])||!is_array($_SESSION['user'])){header('Location: login.php');exit;}
$user=$_SESSION['user'];
if(($user['role']??'user')!=='admin'){echo"<p class='text-danger p-4'>🚫 Không có quyền truy cập.</p>";exit;}

if(isset($_GET['delete'])){ $id=(int)$_GET['delete']; if($id!=$user['id']) $conn->query("DELETE FROM users WHERE id=$id"); header('Location: admin.php'); exit; }
if(isset($_GET['toggle_admin'])){ $id=(int)$_GET['toggle_admin']; $r=$conn->query("SELECT role FROM users WHERE id=$id")->fetch_assoc(); $new=($r['role']==='admin')?'user':'admin'; $conn->query("UPDATE users SET role='$new' WHERE id=$id"); header('Location: admin.php'); exit; }

$list=$conn->query("SELECT id,username,email,role,status,last_active FROM users ORDER BY id");
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Quản lý người dùng</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icon Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- CSS Tùy chỉnh cho giao diện Dashboard Mới -->
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

        /* 3. MAIN CONTENT */
        .content-wrapper {
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: margin-left 0.3s;
        }

        /* 4. CARD VÀ BẢNG */
        .card {
            border: none;
            border-radius: var(--card-border-radius);
            background-color: var(--card-bg);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px 30px;
            font-size: 1.6rem;
            font-weight: 700;
            border-radius: var(--card-border-radius) var(--card-border-radius) 0 0;
            border-bottom: none;
        }
        
        /* Tinh chỉnh bảng Bootstrap */
        .table {
            --bs-table-bg: var(--card-bg);
        }
        .table thead th {
            background-color: var(--content-bg);
            color: var(--secondary-color);
            font-weight: 600;
            border-bottom: 2px solid var(--primary-color);
            padding: 15px 30px;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .table tbody tr:hover {
            background-color: #e6f7ff !important; 
            cursor: pointer;
        }
        
        .table tbody td {
            padding: 12px 30px;
            vertical-align: middle;
            border-top: 1px solid #eee;
        }
        
        /* Tinh chỉnh nút hành động */
        .btn-action {
            font-size: 0.7rem;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.2s;
            font-weight: 500;
        }
        .btn-action i { font-size: 0.8rem; }
        .btn-action:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.2); }

        /* Badges */
        .badge.bg-primary { background-color: #0077b6 !important; }
        .badge.bg-success-subtle { background-color: #d4edda !important; color: #155724 !important; }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 250px;
                left: -250px; /* Ẩn sidebar */
                padding-top: 0;
            }
            .content-wrapper {
                margin-left: 0;
                padding: 20px;
            }
            .header {
                padding-left: 15px;
            }
            .toggle-btn {
                display: block !important;
            }
            .sidebar.show {
                left: 0;
            }
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
            <i class="fas fa-user-circle me-1"></i> Chào mừng, <strong><?=htmlspecialchars($user['username'])?></strong>
        </span>
        <a href="logout.php" class="btn btn-action btn-outline-danger">
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

<!-- MAIN CONTENT WRAPPER -->
<main class="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
          <ol class="breadcrumb bg-white p-3 rounded shadow-sm">
            <a href="admin_todo.php" class="active"><i class="fas fa-tasks me-2"></i> Quản lý công việc</a>
          </ol>
        </nav>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-table me-2"></i> Danh sách người dùng hệ thống</span>
                <span class="badge rounded-pill bg-light text-dark shadow-sm px-3 py-2">Tổng: <?= $list->num_rows ?> người</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 5%; padding-left: 30px;">ID</th>
                                <th style="width: 20%;">Tên người dùng</th>
                                <th style="width: 25%;">Email</th>
                                <th style="width: 10%;">Vai trò</th>
                                <th style="width: 15%;">Trạng thái</th>
                                <th style="width: 15%;">Hoạt động cuối</th>
                                <th class="text-center" style="width: 10%; padding-right: 30px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($u = $list->fetch_assoc()): ?>
                            <tr>
                                <td style="padding-left: 30px;"><?= htmlspecialchars($u['id']) ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php if($u['role']==='admin'): ?>
                                        <span class="badge rounded-pill bg-primary shadow-sm"><i class="fas fa-crown me-1"></i> ADMIN</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-secondary"><i class="fas fa-user me-1"></i> USER</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($u['status']==='online'): ?>
                                        <span class="text-success fw-bold"><i class="fas fa-circle me-1" style="font-size: 0.6rem;"></i> Online</span>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="far fa-circle me-1" style="font-size: 0.6rem;"></i> Offline</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($u['last_active']) ?></td>
                                <td class="text-center" style="padding-right: 30px;">
                                    <?php if($u['id'] != $user['id']): ?>
                                        <!-- Nút Cấp/Hạ quyền -->
                                        <a href="?toggle_admin=<?=$u['id']?>" class="btn btn-action me-1 
                                           <?= $u['role']==='admin' ? 'btn-outline-warning' : 'btn-outline-primary' ?>" 
                                           title="<?= $u['role']==='admin' ? 'Hạ quyền xuống User' : 'Cấp quyền Admin' ?>">
                                            <i class="fas fa-user-alt-slash"></i>
                                        </a>
                                        <!-- Nút Xóa -->
                                        <a href="?delete=<?=$u['id']?>" class="btn btn-action btn-outline-danger" 
                                           onclick="return confirm('XÁC NHẬN: Bạn có chắc chắn muốn XÓA người dùng: <?= htmlspecialchars($u['username']) ?>?')" 
                                           title="Xóa người dùng">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle px-3 py-2">(Bạn)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($list->num_rows === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Không có người dùng nào trong hệ thống.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap 5 JS Bundle (cần thiết cho responsive và các thành phần Bootstrap khác) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Javascript cho tính năng Responsive: Toggle Sidebar
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const contentWrapper = document.querySelector('.content-wrapper');

        // Hàm mở/đóng sidebar
        function toggleSidebar() {
            // Sử dụng class 'show' để kiểm soát state
            sidebar.classList.toggle('show');
        }
        
        // Gắn sự kiện cho nút toggle
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleSidebar);
        }

        // Đóng sidebar khi click ra ngoài trên mobile (cần thiết)
        document.addEventListener('click', function(event) {
            // Kiểm tra nếu màn hình nhỏ, sidebar đang mở, và click không phải trên sidebar hay nút toggle
            if (window.innerWidth <= 992 && sidebar.classList.contains('show') && 
                !sidebar.contains(event.target) && !toggleBtn.contains(event.target)) 
            {
                sidebar.classList.remove('show');
            }
        });

        // Đảm bảo sidebar hiển thị đúng trên màn hình lớn khi resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                // Đảm bảo sidebar không có class 'show' trên desktop
                sidebar.classList.remove('show');
            }
        });
        
        // Cập nhật CSS để class .show hoạt động (Được thêm vào trong style block)
        const styleSheet = document.styleSheets[document.styleSheets.length - 1];
        try {
            styleSheet.insertRule(`
                @media (max-width: 992px) {
                    .sidebar.show {
                        left: 0 !important;
                    }
                }
            `, styleSheet.cssRules.length);
        } catch (e) {
            // console.error("Error inserting media query rule:", e);
        }
    });
</script>