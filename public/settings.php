<?php
session_start();

// Mock dependencies if file dependencies are missing (for preview environment)
if (!defined('MOCK_DB_LOADED')) {
    define('MOCK_DB_LOADED', true);
    // Mock user session
    if (!isset($_SESSION['user'])) { 
        $_SESSION['user'] = ['id' => 1, 'username' => 'admin_user', 'role' => 'admin'];
    }
}

if (!isset($_SESSION['user'])) { 
    header("Location: login.php"); 
    exit;
}

$user = $_SESSION['user'];

if ($user['role'] !== 'admin') {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>❌ Bạn không có quyền truy cập trang này.</h3>");
}

// MOCK: Giả lập việc tải cài đặt hiện tại từ cơ sở dữ liệu
$current_settings = [
    'site_name' => 'Hệ thống quản trị DACN',
    'notifications' => 'on',
    'max_logs' => 5000,
    'theme' => 'dark',
    'ai_api_key' => 'sk-mock-a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6'
];

// MOCK: Giả lập xử lý POST (chỉ hiển thị thông báo thành công)
$message = '';
$is_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trong môi trường thật, bạn sẽ xử lý và lưu dữ liệu vào DB tại đây.
    // Dữ liệu mock:
    $current_settings['site_name'] = $_POST['site_name'] ?? $current_settings['site_name'];
    $current_settings['notifications'] = $_POST['notifications'] ?? $current_settings['notifications'];
    $current_settings['theme'] = $_POST['theme'] ?? $current_settings['theme'];
    
    $message = "✅ Cài đặt đã được lưu thành công!";
    $is_success = true;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cài đặt hệ thống</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
/* --- FONT & BASE --- */
:root {
    --primary: #2563eb; /* Blue 600 */
    --primary-hover: #1e4fc9; /* Blue 700 */
    --text-color: #1f2937;
    --background-color: #f3f4f6;
    --card-background: #ffffff;
    --border-color: #e5e7eb;
}

body {
    background: var(--background-color);
    font-family: 'Inter', sans-serif;
    padding: 20px;
}

/* --- CONTAINER & CARD --- */
.page-container {
    max-width: 800px; /* Tăng chiều rộng */
    margin: 40px auto;
    background: var(--card-background);
    padding: 35px 40px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.section-title {
    font-size: 2rem;
    font-weight: 800;
    color: var(--text-color);
    margin-bottom: 30px;
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 15px;
}

/* --- BUTTONS & LINKS --- */
.back-btn {
    display: inline-flex;
    align-items: center;
    margin-bottom: 25px;
    padding: 8px 15px;
    background: var(--border-color);
    color: var(--text-color);
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
}
.back-btn:hover {
    background: #d1d5db;
    color: black;
    transform: translateY(-1px);
}

.save-btn {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 15px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 1.1rem;
    transition: background 0.2s, transform 0.2s;
}
.save-btn:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.4);
}

/* --- FORM ELEMENTS --- */
.form-label {
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 8px;
}
.form-control, .form-select {
    border-radius: 8px;
    padding: 10px 15px;
    border: 1px solid var(--border-color);
}
.form-control:focus, .form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25);
}

/* Grouping for better structure */
.setting-group {
    border: 1px solid var(--border-color);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
}
.setting-group h5 {
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 20px;
}
</style>

</head>
<body>

<div class="page-container">

    <!-- Nút quay lại -->
    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i> Quay lại Dashboard
    </a>

    <h2 class="section-title">
        <i class="fas fa-sliders-h me-3"></i> Cài đặt hệ thống
    </h2>
    
    <!-- Thông báo trạng thái -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $is_success ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="settings.php">

        <!-- CÀI ĐẶT CHUNG -->
        <div class="setting-group">
            <h5><i class="fas fa-globe me-2"></i> Cấu hình chung</h5>
            
            <!-- Tên hệ thống -->
            <div class="mb-3">
                <label class="form-label">Tên hệ thống</label>
                <input type="text" name="site_name" class="form-control" 
                       value="<?= htmlspecialchars($current_settings['site_name']) ?>"
                       placeholder="VD: Hệ thống quản trị DACN" required>
            </div>
            
            <!-- Giao diện -->
            <div class="mb-3">
                <label class="form-label">Chế độ giao diện</label>
                <select name="theme" class="form-select">
                    <option value="light" <?= $current_settings['theme'] == 'light' ? 'selected' : '' ?>>Sáng</option>
                    <option value="dark" <?= $current_settings['theme'] == 'dark' ? 'selected' : '' ?>>Tối</option>
                    <option value="auto" <?= $current_settings['theme'] == 'auto' ? 'selected' : '' ?>>Tự động</option>
                </select>
            </div>
        </div>

        <!-- CÀI ĐẶT BẢO MẬT & LOG -->
        <div class="setting-group">
            <h5><i class="fas fa-shield-alt me-2"></i> Bảo mật & Log</h5>
            
            <!-- Log -->
            <div class="mb-3">
                <label class="form-label">Giới hạn nhật ký hệ thống (Số lượng bản ghi)</label>
                <input type="number" name="max_logs" class="form-control" 
                       value="<?= htmlspecialchars($current_settings['max_logs']) ?>"
                       placeholder="VD: 5000" min="100" required>
            </div>

            <!-- Chế độ thông báo -->
            <div class="mb-3">
                <label class="form-label">Thông báo hệ thống</label>
                <select name="notifications" class="form-select">
                    <option value="on" <?= $current_settings['notifications'] == 'on' ? 'selected' : '' ?>>Bật thông báo</option>
                    <option value="off" <?= $current_settings['notifications'] == 'off' ? 'selected' : '' ?>>Tắt thông báo</option>
                </select>
                <div class="form-text">Điều khiển việc gửi thông báo qua email hoặc push.</div>
            </div>
        </div>
        
        <!-- CÀI ĐẶT TÍCH HỢP (Mới thêm) -->
        <div class="setting-group">
            <h5><i class="fas fa-brain me-2"></i> Tích hợp AI</h5>

            <div class="mb-3">
                <label class="form-label">API Key của Mô hình AI</label>
                <div class="input-group">
                    <input type="password" id="ai_api_key" name="ai_api_key" class="form-control" 
                           value="<?= htmlspecialchars($current_settings['ai_api_key']) ?>"
                           placeholder="Nhập khóa API">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-text">Khóa này được sử dụng để gọi các dịch vụ phân tích dự án AI.</div>
            </div>
        </div>


        <button type="submit" class="save-btn w-100 mt-4">
            <i class="fas fa-save me-2"></i> Lưu tất cả cài đặt
        </button>

    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logic để bật/tắt hiển thị API Key
    const toggleButton = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('ai_api_key');
    const toggleIcon = toggleButton.querySelector('i');

    toggleButton.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    });
});
</script>

</body>
</html>