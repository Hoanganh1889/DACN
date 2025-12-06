<?php
session_start();
require_once '../config/db.php';

// --- Logic Xác Thực và Xử Lý (GIỮ NGUYÊN theo yêu cầu) ---
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) { header('Location: login.php'); exit; }

$user = $_SESSION['user']; $uid = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $deadline = $_POST['deadline'] ?? null;
    $status = $_POST['status'] ?? 'Chưa làm';
    
    // Cải tiến: Sử dụng Prepared Statement để tăng bảo mật
    if ($title !== '') {
        $stmt = $conn->prepare("INSERT INTO todos (user_id, title, status, deadline) VALUES (?, ?, ?, ?)");
        // Nếu deadline rỗng, bind_param sẽ cần 's' cho $deadline, nhưng giá trị là NULL (string rỗng)
        $stmt->bind_param('isss', $uid, $title, $status, $deadline); 
        $stmt->execute();
        $stmt->close();
    }
    header('Location: todo.php'); exit;
}

if (isset($_GET['delete'])) { $id = (int)$_GET['delete']; $conn->query("DELETE FROM todos WHERE id=$id AND user_id=$uid"); header('Location: todo.php'); exit; }
if (isset($_GET['done'])) { $id = (int)$_GET['done']; $conn->query("UPDATE todos SET status='Hoàn thành' WHERE id=$id AND user_id=$uid"); header('Location: todo.php'); exit; }

$todos = $conn->query("SELECT * FROM todos WHERE user_id=$uid ORDER BY created_at DESC");
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Công việc</title>
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

        /* 4. CARD VÀ TO-DO SPECIFIC */
        .card {
            border: none;
            border-radius: var(--card-border-radius);
            background-color: var(--card-bg);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-header-custom {
            background-color: #5bc0de; /* Info blue */
            color: white;
            padding: 20px 30px;
            font-size: 1.6rem;
            font-weight: 700;
            border-radius: var(--card-border-radius) var(--card-border-radius) 0 0;
            border-bottom: none;
        }

        /* Form Tạo Mới */
        .add-task-form .form-control,
        .add-task-form .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05) inset;
        }

        /* Bảng Công việc */
        .table thead th {
            background-color: var(--content-bg);
            color: var(--secondary-color);
            font-weight: 600;
            border-bottom: 2px solid var(--primary-color);
            padding: 15px 30px;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .table tbody td {
            padding: 12px 30px;
            vertical-align: middle;
            border-top: 1px solid #eee;
        }
        
        .table tbody tr:hover {
            background-color: #f7f9fc !important; 
        }

        .task-title {
            font-weight: 500;
            color: #444;
        }
        
        .task-done .task-title {
            text-decoration: line-through;
            color: #aaa;
        }

        /* Status Badges */
        .badge-status {
            padding: 6px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            min-width: 100px;
            text-align: center;
        }

        /* Hành động */
        .btn-action {
            font-size: 0.8rem;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-action:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.2); }

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
            /* Đảm bảo form tạo mới hiển thị tốt trên mobile */
            .add-task-form .col-md-6,
            .add-task-form .col-md-3,
            .add-task-form .col-md-2,
            .add-task-form .col-md-1 {
                width: 100%;
                margin-bottom: 10px;
            }
            .add-task-form .col-md-1 {
                margin-bottom: 0;
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
            <i class="fas fa-user-circle me-1"></i> Xin chào, <strong><?=htmlspecialchars($user['username'])?></strong>
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
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-secondary">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Quản lý công việc</li>
          </ol>
        </nav>

        <div class="card mb-5">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="fas fa-plus-circle me-2"></i> Thêm công việc mới</span>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 add-task-form">
                    <div class="col-lg-6 col-md-12">
                        <input class="form-control" name="title" placeholder="Tên công việc (Ví dụ: Hoàn thành báo cáo tháng 11)..." required>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label visually-hidden" for="deadline">Hạn chót</label>
                        <input class="form-control" type="date" name="deadline" id="deadline">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label visually-hidden" for="status">Trạng thái</label>
                        <select class="form-select" name="status" id="status">
                            <option value="Chưa làm">Chưa làm</option>
                            <option value="Đang làm">Đang làm</option>
                            <option value="Hoàn thành">Hoàn thành</option>
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-12">
                        <button class="btn btn-success w-100 h-100" title="Thêm công việc">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header-custom d-flex justify-content-between align-items-center" style="background-color: var(--secondary-color);">
                <span><i class="fas fa-list-check me-2"></i> Danh sách công việc của bạn</span>
                <span class="badge rounded-pill bg-primary shadow-sm px-3 py-2">Tổng: <?= $todos->num_rows ?> việc</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50%; padding-left: 30px;">Công việc</th>
                                <th style="width: 20%;">Trạng thái</th>
                                <th style="width: 15%;">Hạn chót</th>
                                <th class="text-center" style="width: 15%; padding-right: 30px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($r=$todos->fetch_assoc()): ?>
                        <tr class="<?= $r['status'] === 'Hoàn thành' ? 'task-done' : '' ?>">
                            <td style="padding-left: 30px;" class="task-title"><?=htmlspecialchars($r['title'])?></td>
                            <td>
                                <?php
                                $status_text = htmlspecialchars($r['status']);
                                $badge_class = 'bg-secondary';
                                if ($status_text === 'Hoàn thành') { $badge_class = 'bg-success'; } 
                                else if ($status_text === 'Đang làm') { $badge_class = 'bg-warning text-dark'; } 
                                else if ($status_text === 'Chưa làm') { $badge_class = 'bg-danger'; }
                                ?>
                                <span class="badge badge-status <?= $badge_class ?>"><?= $status_text ?></span>
                            </td>
                            <td>
                                <?php if($r['deadline']): ?>
                                    <span class="text-muted"><?= date('d/m/Y', strtotime($r['deadline'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Không có</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center" style="padding-right: 30px;">
                                <?php if($r['status']!=='Hoàn thành'): ?>
                                    <a href="?done=<?=$r['id']?>" class="btn btn-action btn-outline-success me-2" title="Đánh dấu hoàn thành">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-success me-2"><i class="fas fa-check-circle"></i></span>
                                <?php endif; ?>
                                <a href="?delete=<?=$r['id']?>" class="btn btn-action btn-outline-danger" 
                                   onclick="return confirm('XÁC NHẬN: Bạn có chắc chắn muốn XÓA công việc này không?')" 
                                   title="Xóa công việc">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($todos->num_rows === 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">🎉 Bạn không có công việc nào. Thêm một công việc mới!</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- SCRIPTS CHO UI/UX ---

    // 1. Sidebar Toggle Script (Responsive)
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }

        // Đóng sidebar khi click ra ngoài trên mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 992 && sidebar.classList.contains('show') && 
                !sidebar.contains(event.target) && !toggleBtn.contains(event.target)) 
            {
                sidebar.classList.remove('show');
            }
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('show');
            }
        });

        // Cập nhật CSS cho responsive (cơ chế an toàn)
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