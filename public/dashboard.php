<?php
session_start();
require_once '../config/db.php';
// --- Logic PHP (GIỮ NGUYÊN) ---
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) { header('Location: login.php'); exit; }
$user=$_SESSION['user']; $uid=(int)$user['id'];

// Hàm truy vấn số lượng
$q=fn($s)=>($r=$conn->query($s))?(int)($r->fetch_assoc()['c']??0):0;

// Lấy thống kê
$total=$q("SELECT COUNT(*) c FROM todos WHERE user_id=$uid");
$todo=$q("SELECT COUNT(*) c FROM todos WHERE user_id=$uid AND status='Chưa làm'");
$doing=$q("SELECT COUNT(*) c FROM todos WHERE user_id=$uid AND status='Đang làm'");
$done=$q("SELECT COUNT(*) c FROM todos WHERE user_id=$uid AND status='Hoàn thành'");

// Lấy công việc gần đây
$todos=$conn->query("SELECT * FROM todos WHERE user_id=$uid ORDER BY created_at DESC LIMIT 5"); // Giới hạn 5 công việc gần đây
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icon Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- CSS Tùy chỉnh cho giao diện Dashboard Mới -->
    <style>
         .sidebar-label {
            padding: 10px 30px;
            color: #9ca3af;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
        }
      

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
             overflow-y: auto;
            overflow-x: hidden;
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

        /* 4. CARD VÀ DASHBOARD SPECIFIC */
        .card {
            border: none;
            border-radius: var(--card-border-radius);
            background-color: var(--card-bg);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }
        
        .card-header-custom {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--secondary-color);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        /* Statistic Cards */
        .stat-card {
            padding: 20px 25px;
            border-radius: var(--card-border-radius);
            text-align: left;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.7;
            margin-right: 15px;
        }

        .stat-details h6 {
            font-size: 0.9rem;
            margin-bottom: 5px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .stat-details h3 {
            font-size: 2.2rem;
            margin-bottom: 0;
            font-weight: 800;
        }

        /* Table Style */
        .table thead th {
            background-color: var(--content-bg);
            color: var(--secondary-color);
            font-weight: 600;
            border-bottom: 2px solid var(--primary-color);
            padding: 15px 25px;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .table tbody td {
            padding: 12px 25px;
            vertical-align: middle;
            border-top: 1px solid #eee;
        }
        .table tbody tr:hover {
            background-color: #f7f9fc !important; 
        }

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
            .stat-card {
                margin-bottom: 20px;
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
         <button id="toggleDarkMode" class="btn btn-sm btn-outline-secondary me-2" title="Chuyển giao diện sáng/tối">
        <i class="fas fa-moon"></i>
    </button>
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

<!-- MAIN CONTENT WRAPPER -->
<main class="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
          <ol class="breadcrumb bg-white p-3 rounded shadow-sm">
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
          </ol>
        </nav>
        
        <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i> Bảng điều khiển (Dashboard)</h2>

        <!-- 1. STATISTIC CARDS -->
        <div class="row mb-5">
            <!-- Thẻ Tổng Công việc -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card bg-primary text-white shadow-lg">
                    <div class="stat-icon"><i class="fas fa-box-open"></i></div>
                    <div class="stat-details">
                        <h6>Tổng công việc</h6>
                        <h3><?=$total?></h3>
                    </div>
                </div>
            </div>
            
            <!-- Thẻ Chưa làm -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card bg-danger text-white shadow-lg">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-details">
                        <h6>Chưa làm</h6>
                        <h3><?=$todo?></h3>
                    </div>
                </div>
            </div>
            
            <!-- Thẻ Đang làm -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card bg-warning text-dark shadow-lg">
                    <div class="stat-icon"><i class="fas fa-tools"></i></div>
                    <div class="stat-details">
                        <h6>Đang làm</h6>
                        <h3><?=$doing?></h3>
                    </div>
                </div>
            </div>
            
            <!-- Thẻ Hoàn thành -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card bg-success text-white shadow-lg">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-details">
                        <h6>Hoàn thành</h6>
                        <h3><?=$done?></h3>
                    </div>
                </div>
            </div>
        </div>
        <!-- 2. BIỂU ĐỒ VÀ CÔNG VIỆC GẦN ĐÂY -->
        <div class="row">
            <!-- Biểu đồ -->
            <div class="col-lg-5 mb-4">
                <div class="card h-100">
                    <h5 class="card-header-custom"><i class="fas fa-chart-pie me-2"></i> Biểu đồ tiến độ</h5>
                    <div class="card-body">
                        <!-- Canvas for Chart.js -->
                        <div style="height: 300px;">
                            <canvas id="chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Công việc gần đây -->
            <div class="col-lg-7 mb-4">
                <div class="card h-100 p-0">
                    <div class="card-header-custom p-4 pb-2" style="font-size: 1.25rem;">
                        <i class="fas fa-list-ul me-2"></i> Công việc gần đây
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="padding-left: 25px;">Tên</th>
                                        <th>Trạng thái</th>
                                        <th>Hạn</th>
                                        <th>Tạo lúc</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($todos->num_rows > 0): ?>
                                        <?php while($r=$todos->fetch_assoc()):?>
                                        <tr>
                                            <td style="padding-left: 25px;"><?=htmlspecialchars($r['title'])?></td>
                                            <td>
                                                <?php
                                                $status_text = htmlspecialchars($r['status']);
                                                $badge_class = 'bg-secondary';
                                                if ($status_text === 'Hoàn thành') { $badge_class = 'bg-success'; } 
                                                else if ($status_text === 'Đang làm') { $badge_class = 'bg-warning text-dark'; } 
                                                else if ($status_text === 'Chưa làm') { $badge_class = 'bg-danger'; }
                                                ?>
                                                <span class="badge rounded-pill <?= $badge_class ?> px-3 py-1"><?= $status_text ?></span>
                                            </td>
                                            <td><?=$r['deadline'] ? date('d/m/Y', strtotime($r['deadline'])) : 'N/A'?></td>
                                            <td><?=date('H:i, d/m', strtotime($r['created_at']))?></td>
                                        </tr>
                                        <?php endwhile;?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center text-muted py-3">Không có công việc nào gần đây.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-end">
                        <a href="todo.php" class="btn btn-sm btn-outline-secondary">Xem tất cả công việc <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ANALYTICS DASHBOARD -->
<div class="row mt-4">
    <!-- Hàng 1: Task theo tháng + Tin nhắn theo ngày -->
    <div class="col-md-6 mb-4">
        <div class="card p-3 h-100">
            <h5 class="fw-bold mb-3">
                <i class="fas fa-check-circle text-success me-2"></i>
                Nhiệm vụ hoàn thành theo tháng
            </h5>
            <canvas id="taskChart" height="180"></canvas>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card p-3 h-100">
            <h5 class="fw-bold mb-3">
                <i class="fas fa-comments text-primary me-2"></i>
                Tin nhắn theo ngày
            </h5>
            <canvas id="messageChart" height="180"></canvas>
        </div>
    </div>
    <!-- Hàng 2: AI theo tháng + Trạng thái người dùng -->
    <div class="col-md-6 mb-4">
        <div class="card p-3 h-100">
            <h5 class="fw-bold mb-3">
                <i class="fas fa-brain text-danger me-2"></i>
                Phân tích AI theo tháng
            </h5>
            <canvas id="aiChart" height="180"></canvas>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card p-3 h-100">
            <h5 class="fw-bold mb-3">
                <i class="fas fa-users text-warning me-2"></i>
                Trạng thái người dùng
            </h5>
            <canvas id="userChart" height="180"></canvas>
        </div>
    </div>
    <!-- Hàng 3: Biểu đồ theo tuần -->
    <div class="col-md-6 mb-4">
        <div class="card p-3 h-100">
            <h5 class="fw-bold mb-3">
                <i class="fas fa-calendar-week text-info me-2"></i>
                Công việc theo tuần
            </h5>
            <canvas id="weeklyTaskChart" height="180"></canvas>
        </div>
    </div>

</div>
</main>
<!-- MODAL CẢNH BÁO DEADLINE -->
<div class="modal fade" id="deadlineModal" tabindex="-1" aria-labelledby="deadlineModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deadlineModalLabel">
            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
            Công việc sắp đến hạn
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Bạn có một số công việc sắp đến hạn hoặc đã gần hạn:</p>
        <ul id="deadlineList" class="list-group list-group-flush">
          <!-- JS sẽ render vào đây -->
        </ul>
      </div>
      <div class="modal-footer">
        <a href="todo.php" class="btn btn-sm btn-primary">
            Xem danh sách công việc
        </a>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// =============================
// KHỞI TẠO DASHBOARD FULL
// =============================
document.addEventListener("DOMContentLoaded", function () {

    // =============================
    // 1. BIỂU ĐỒ TIẾN ĐỘ (Doughnut)
    // =============================
    const ctx = document.getElementById('chart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Chưa làm', 'Đang làm', 'Hoàn thành'],
            datasets: [{
                data: [<?=$todo?>, <?=$doing?>, <?=$done?>],
                backgroundColor: ['#dc3545', '#ffc107', '#198754'],
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) label += ': ';
                            label += context.raw.toLocaleString() + ' việc';
                            return label;
                        }
                    }
                }
            }
        }
    });

    // =============================
    // 2. SIDEBAR TOGGLE
    // =============================
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }

    document.addEventListener('click', function (event) {
        if (window.innerWidth <= 992 &&
            sidebar.classList.contains('show') &&
            !sidebar.contains(event.target) &&
            !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    });

    // =============================
    // 3. DARK MODE
    // =============================
    const darkToggle = document.getElementById('toggleDarkMode');
    const prefersDark = localStorage.getItem('darkMode') === '1';

    if (prefersDark) document.body.classList.add('dark-mode');

    if (darkToggle) {
        darkToggle.addEventListener('click', function () {
            document.body.classList.toggle('dark-mode');
            const enabled = document.body.classList.contains('dark-mode') ? '1' : '0';
            localStorage.setItem('darkMode', enabled);
        });
    }

    // =============================
    // 4. BIỂU ĐỒ Hàng tháng, tin nhắn, user (get_stats.php)
    // =============================
    fetch("get_stats.php")
        .then(res => res.json())
        .then(data => {

            // TASK CHART (Bar)
            new Chart(document.getElementById("taskChart"), {
                type: "bar",
                data: {
                    labels: data.tasks.labels,
                    datasets: [{
                        label: "Nhiệm vụ hoàn thành",
                        data: data.tasks.values,
                        backgroundColor: "rgba(75, 192, 192, 0.6)"
                    }]
                },
                options: { responsive: true }
            });

            // MESSAGE CHART (Line)
            new Chart(document.getElementById("messageChart"), {
                type: "line",
                data: {
                    labels: data.messages.labels,
                    datasets: [{
                        label: "Số tin nhắn",
                        data: data.messages.values,
                        borderColor: "rgba(54, 162, 235, 1)",
                        borderWidth: 2
                    }]
                },
                options: { responsive: true }
            });

            // USER STATUS (Pie)
            new Chart(document.getElementById("userChart"), {
                type: "pie",
                data: {
                    labels: ["Online", "Offline"],
                    datasets: [{
                        data: [data.users.online, data.users.offline],
                        backgroundColor: ["#4caf50", "#9e9e9e"]
                    }]
                }
            });

        })
        .catch(err => console.error("ERROR:", err));

    // =============================
    // 5. BIỂU ĐỒ AI PROJECT (get_ai_stats.php)
    // =============================
    fetch("get_ai_stats.php")
        .then(res => res.json())
        .then(data => {

            new Chart(document.getElementById("aiChart").getContext("2d"), {
                type: "line",
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: "Số phân tích AI theo tháng",
                        data: data.values,
                        borderColor: "#e63946",
                        backgroundColor: "rgba(230, 57, 70, 0.25)",
                        fill: true,
                        borderWidth: 3,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: "bottom" } },
                    scales: { y: { beginAtZero: true } }
                }
            });

        })
        .catch(err => console.error("AI Chart Error:", err));

    // =============================
    // 6. BIỂU ĐỒ CÔNG VIỆC THEO TUẦN (get_task_trend.php)
    // =============================
    const weeklyCanvas = document.getElementById("weeklyTaskChart");
    if (weeklyCanvas) {

        fetch('get_task_trend.php')
            .then(res => res.json())
            .then(data => {

                if (!data.weekly || !data.weekly.labels) return;

                new Chart(weeklyCanvas.getContext("2d"), {
                    type: "bar",
                    data: {
                        labels: data.weekly.labels,
                        datasets: [
                            {
                                label: "Hoàn thành",
                                data: data.weekly.done,
                                backgroundColor: "rgba(25, 135, 84, 0.7)"
                            },
                            {
                                label: "Tổng công việc",
                                data: data.weekly.total,
                                backgroundColor: "rgba(13, 110, 253, 0.5)"
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: "bottom" },
                            tooltip: { mode: "index", intersect: false }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });

            })
            .catch(err => console.error("Task trend error:", err));
    }

    // =============================
    // 7. CẢNH BÁO DEADLINE
    // =============================
    fetch("get_deadline_alerts.php")
        .then(res => res.json())
        .then(data => {

            if (!data.alerts || !data.alerts.length) return;

            const list = document.getElementById("deadlineList");
            if (!list) return;

            list.innerHTML = "";

            data.alerts.forEach(item => {
                const deadline = new Date(item.deadline).toLocaleDateString("vi-VN");

                list.innerHTML += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${item.title}</strong>
                            <div class="small text-muted">
                                Trạng thái: ${item.status} – Hạn: ${deadline}
                            </div>
                        </div>
                        <a href="todo.php" class="btn btn-sm btn-outline-primary">Xem</a>
                    </li>
                `;
            });

            new bootstrap.Modal(document.getElementById("deadlineModal")).show();
        })
        .catch(err => console.error("Deadline alerts error:", err));

});
</script>

