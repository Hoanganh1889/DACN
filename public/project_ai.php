<style>
:root {
    --sidebar-width: 280px;
    --header-height: 70px;
    --secondary-color: #3f6583;
    --sidebar-bg: #1f2937;
    --content-bg: #f5f7fa;
    --text-light: #e5e7eb;
    --card-bg: #ffffff;
    --card-border-radius: 12px;
}
body{
    font-family:'Inter',sans-serif;
    background-color:var(--content-bg);
    margin:0;
    padding-top:var(--header-height);
}
.header{
    position:fixed;top:0;left:0;width:100%;height:var(--header-height);
    background-color:var(--card-bg);
    box-shadow:0 4px 8px rgba(0,0,0,0.05);
    z-index:1030;padding:0 30px;
}
.header .logo{color:var(--primary-color);font-weight:800;font-size:1.6rem;}
.sidebar{
    width:var(--sidebar-width);background-color:var(--sidebar-bg);color:var(--text-light);
    position:fixed;top:0;left:0;height:100vh;padding-top:var(--header-height);
    box-shadow:4px 0 8px rgba(0,0,0,0.2);z-index:1020;
    overflow-x: hidden;
scrollbar-width: thin;

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
.sidebar h3{
    padding:25px 30px 15px;margin-bottom:20px;color:#fff;font-size:1.2rem;
    border-bottom:1px solid rgba(255,255,255,0.1);
}
.sidebar nav a{
    display:flex;align-items:center;padding:15px 30px;color:var(--text-light);
    text-decoration:none;transition:0.2s;border-left:4px solid transparent;
}
.sidebar nav a:hover{background-color:var(--secondary-color);border-left-color:var(--primary-color);}
.sidebar nav a.active{background-color:var(--primary-color);color:#fff;border-left-color:#fff;font-weight:600;}
.content-wrapper{
    margin-left:var(--sidebar-width);padding:30px;
}
.card{
    border:none;border-radius:var(--card-border-radius);
    background-color:var(--card-bg);box-shadow:0 8px 20px rgba(0,0,0,0.1);
}
.card-header-custom{
    background:linear-gradient(135deg,#00a8e8,#3f6583);
    color:white;padding:20px 30px;font-weight:700;font-size:1.4rem;
    border-radius:var(--card-border-radius) var(--card-border-radius) 0 0;
}
@media(max-width:992px){
    .sidebar{width:250px;left:-250px;transition:.3s;}
    .sidebar.show{left:0;}
    .content-wrapper{margin-left:0;padding:20px;}
}
</style>
<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$uid  = (int)$user['id'];

$ai_result = "";
$error = "";

/* ============================================================
    XỬ LÝ FORM PHÂN TÍCH DỰ ÁN MỚI
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $complexity  = $_POST['complexity'];
    $duration    = (int)$_POST['duration'];
    $budget      = (float)$_POST['budget'];

    if ($name === "" || $description === "") {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } else {

        // LƯU DỰ ÁN
        $stmt = $conn->prepare("
            INSERT INTO projects (user_id, name, description, complexity, expected_duration_months, expected_budget)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->bind_param("isssid", $uid, $name, $description, $complexity, $duration, $budget);
        $stmt->execute();
        $project_id = $stmt->insert_id;
        $stmt->close();

        // PROMPT
        $prompt = "
Phân tích dự án:

Tên dự án: $name
Độ phức tạp: $complexity
Thời gian dự kiến: $duration tháng
Ngân sách: $budget VND
Mô tả: $description

Hãy phân tích chi tiết:
1. Nhân sự cần
2. Chi phí hợp lý
3. Timeline
4. Rủi ro & giải pháp
5. Gợi ý sử dụng AI
";

        // GỌI OPENROUTER FREE
        $api_key = "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";
        $url = "https://openrouter.ai/api/v1/chat/completions";

        $payload = [
            "model" => "meta-llama/llama-3.3-70b-instruct:free",
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ]
        ];

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer $api_key",
            "HTTP-Referer: http://localhost",
            "X-Title: DACN-Web-AI"
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE)
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            $error = "API ERROR ($code): $response";
        } else {
            $data = json_decode($response, true);
            $ai_result = $data["choices"][0]["message"]["content"] ?? "Không có phản hồi AI.";
        }

        // LƯU PHÂN TÍCH VÀO DB
        if ($ai_result) {
            $stmt2 = $conn->prepare("
                INSERT INTO project_ai_analyses (project_id, model, result_text)
                VALUES (?,?,?)
            ");
            $model_used = "llama-3.3-70b-free";
            $stmt2->bind_param("iss", $project_id, $model_used, $ai_result);
            $stmt2->execute();
            $stmt2->close();
        }
    }
}

/* ============================================================
    LẤY DANH SÁCH DỰ ÁN + PHÂN TÍCH GẦN NHẤT
============================================================ */
$projects = $conn->query("
    SELECT p.*,
           a.result_text AS last_analysis
    FROM projects p
    LEFT JOIN project_ai_analyses a 
         ON a.id = (
            SELECT id FROM project_ai_analyses 
            WHERE project_id = p.id 
            ORDER BY created_at DESC LIMIT 1
         )
    WHERE p.user_id = $uid
    ORDER BY p.created_at DESC
");
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Phân tích dự án (AI)</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
/* giữ nguyên CSS của bạn để đảm bảo đẹp */
</style>
</head>
<body>

<!-- HEADER -->
<div class="header d-flex align-items-center">
    <button class="btn btn-outline-secondary d-lg-none me-3" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    <h4 class="logo mb-0">DACN</h4>
    <div class="ms-auto">
        <span class="text-secondary me-3">
            <i class="fas fa-user-circle me-1"></i> <?=htmlspecialchars($user['username'])?>
        </span>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">
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

<!-- MAIN -->
<main class="content-wrapper">
<div class="container-fluid">

    <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- FORM NHẬP DỰ ÁN -->
    <div class="card mb-4">
        <div class="card-header-custom">
            <i class="fas fa-brain me-2"></i> Nhập thông tin dự án để AI phân tích
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Tên dự án *</label>
                    <input class="form-control" name="name" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Độ phức tạp</label>
                    <select class="form-select" name="complexity">
                        <option>Thấp</option>
                        <option selected>Trung bình</option>
                        <option>Cao</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Thời gian (tháng)</label>
                    <input class="form-control" type="number" name="duration" value="3">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ngân sách (VND)</label>
                    <input class="form-control" type="number" name="budget">
                </div>

                <div class="col-md-12">
                    <label class="form-label">Mô tả *</label>
                    <textarea class="form-control" name="description" rows="5" required></textarea>
                </div>

                <div class="col-md-12 text-end">
                    <button class="btn btn-primary">
                        <i class="fas fa-wand-magic-sparkles me-1"></i> Phân tích bằng AI
                    </button>
                </div>
            </form>
        </div>
    </div>
<!-- HIỂN THỊ KẾT QUẢ PHÂN TÍCH MỚI -->
    <?php if($ai_result): ?>
        <div class="card mb-4">
            <div class="card-header-custom bg-success">
                <i class="fas fa-chart-pie"></i> Kết quả phân tích mới nhất
            </div>
            <div class="card-body">
                <pre style="white-space:pre-wrap"><?= htmlspecialchars($ai_result) ?></pre>
            </div>
        </div>
    <?php endif; ?>
    <!-- DANH SÁCH DỰ ÁN -->
    <div class="card">
        <div class="card-header-custom bg-dark">
            <i class="fas fa-list"></i> Danh sách dự án
        </div>

        <div class="card-body p-0">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Độ phức tạp</th>
                        <th>Thời gian</th>
                        <th>Ngân sách</th>
                        <th style="width:230px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>

                <?php while($p = $projects->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['complexity']) ?></td>
                        <td><?= $p['expected_duration_months'] ?> tháng</td>
                        <td><?= number_format($p['expected_budget']) ?> VND</td>

                        <td>
                            <!-- Xem chi tiết -->
                            <a class="btn btn-sm btn-primary"
                               href="project_ai_view.php?id=<?= $p['id'] ?>">
                               <i class="fas fa-folder-open"></i> Chi tiết
                            </a>

                            <!-- Phân tích lại -->
                            <a class="btn btn-sm btn-warning"
                               href="project_ai_view.php?id=<?= $p['id'] ?>&reanalyze=1">
                               <i class="fas fa-robot"></i>
                            </a>

                            <!-- Xem nhanh -->
                            <?php if($p['last_analysis']): ?>
                                <button class="btn btn-sm btn-info"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalAI"
                                    data-content="<?= htmlspecialchars($p['last_analysis']) ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </div>

</div>
</main>

<!-- POPUP XEM NHANH -->
<div class="modal fade" id="modalAI" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Phân tích AI</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <pre id="aiContent" style="white-space:pre-wrap;font-size:16px"></pre>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('modalAI').addEventListener('show.bs.modal', function (event) {
    let content = event.relatedTarget.getAttribute('data-content');
    document.getElementById('aiContent').textContent = content;
});
</script>

</body>
</html>
