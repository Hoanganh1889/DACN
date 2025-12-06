<?php
// Bắt đầu phiên làm việc
session_start();

// Giả lập các phụ thuộc nếu tệp tin phụ thuộc bị thiếu (dành cho môi trường xem trước)
if (!defined('MOCK_DB_LOADED')) {
    define('MOCK_DB_LOADED', true);
    // Giả lập phiên người dùng
    if (!isset($_SESSION['user'])) { 
        $_SESSION['user'] = ['id' => 1, 'username' => 'admin_user', 'role' => 'admin'];
    }
}

// Kiểm tra quyền truy cập và chuyển hướng nếu chưa đăng nhập
if (!isset($_SESSION['user'])) { 
    header("Location: login.php"); 
    exit;
}

$user = $_SESSION['user'];

// Chỉ cho phép quản trị viên truy cập trang này
if ($user['role'] !== 'admin') {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>❌ Bạn không có quyền truy cập trang này.</h3>");
}

// MOCK: Hàm tạo dữ liệu log giả, sử dụng cấu trúc của bảng ql_chat_todo_system_logs
function generate_mock_logs($count = 50) {
    // Các cấp độ log và class CSS tương ứng
    $levels = [
        'CRITICAL' => 'danger', // Cực kỳ nghiêm trọng
        'ERROR' => 'danger',     // Lỗi
        'WARNING' => 'warning',  // Cảnh báo
        'INFO' => 'info',        // Thông tin
        'DEBUG' => 'secondary'   // Gỡ lỗi
    ];
    
    // Dữ liệu người dùng cố định (mô phỏng người dùng từ database)
    $users = [
        1 => ['username' => 'admin_user', 'ip' => '192.168.1.1'], 
        101 => ['username' => 'api_client', 'ip' => '10.0.0.5'], 
        999 => ['username' => 'system', 'ip' => '127.0.0.1'],
    ];

    // Dữ liệu hành động cố định (mô phỏng các hành động từ database)
    $fixed_actions = [
        'Đăng nhập thành công',
        'Lỗi truy vấn SQL',
        'Cập nhật hồ sơ',
        'Thêm công việc mới',
        'Xóa người dùng',
        'Truy cập tài nguyên bị hạn chế',
    ];
    
    // Chuỗi User Agent cố định (user_agent text)
    $fixed_user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36';

    $logs = [];
    $start_time = time() - (86400 * 7); // Log từ 7 ngày trước

    for ($i = 1; $i <= $count; $i++) {
        $level_key = array_rand($levels);
        $user_id = array_rand($users);
        $user_data = $users[$user_id];
        $action_index = ($i - 1) % count($fixed_actions); // Lặp lại các hành động
        
        $logs[] = [
            'id' => $i,
            'user_id' => $user_id,
            'user' => $user_data['username'], // Tên người dùng để hiển thị
            'created_at' => date('Y-m-d H:i:s', mt_rand($start_time, time())),
            'type' => $level_key, // Cấp độ Log (type varchar(50))
            'level_class' => $levels[$level_key], // Class CSS
            'action' => $fixed_actions[$action_index], // Tin nhắn chính (action varchar(255))
            'ip_address' => $user_data['ip'], // Địa chỉ IP (ip_address varchar(50))
            'user_agent' => $fixed_user_agent, // User Agent (user_agent text)
            // Chi tiết bổ sung dưới dạng JSON (details text)
            'details' => json_encode([
                'module' => 'Authentication', 
                'request_id' => 'req-' . uniqid(), 
                'data_size' => mt_rand(1, 10) . 'KB'
            ]),
        ];
    }
    // Sắp xếp theo thời gian tạo (created_at) mới nhất
    usort($logs, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    return $logs;
}

$logs = generate_mock_logs(50);

// MOCK: Xử lý lọc theo cấp độ (type)
$filter_level = $_GET['level'] ?? 'all';
$search_query = $_GET['search'] ?? '';

if ($filter_level !== 'all') {
    $logs = array_filter($logs, fn($log) => $log['type'] === $filter_level);
}

// MOCK: Xử lý tìm kiếm theo hành động (action) hoặc người dùng
if (!empty($search_query)) {
    $logs = array_filter($logs, fn($log) => str_contains(strtolower($log['action']), strtolower($search_query)) || str_contains(strtolower($log['user']), strtolower($search_query))); 
}

// MOCK: Xử lý phân trang
$logs_per_page = 10;
$total_logs = count($logs);
$total_pages = ceil($total_logs / $logs_per_page);
$current_page = max(1, min($total_pages, $_GET['page'] ?? 1));
$start_index = ($current_page - 1) * $logs_per_page;
$display_logs = array_slice($logs, $start_index, $logs_per_page);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nhật ký Hệ thống</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
/* --- THIẾT LẬP CƠ BẢN VÀ TIỆN ÍCH --- */
:root {
    --primary: #2563eb; 
    --primary-hover: #1e4fc9; 
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

/* --- CONTAINER VÀ CARD --- */
.page-container {
    max-width: 1200px; /* Chiều rộng lớn hơn cho bảng log */
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

/* --- NÚT BẤM VÀ LIÊN KẾT --- */
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

/* --- KIỂU DÁNG BẢNG LOG --- */
.log-table th {
    background-color: #f9fafb;
    color: #4b5563;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.85rem;
    padding: 12px 15px;
}
.log-table td {
    vertical-align: middle;
}
.log-table tbody tr:hover {
    background-color: #f5f5f5;
    cursor: default; /* Thay đổi từ pointer để người dùng chỉ tương tác qua nút Chi tiết */
}

/* Nhãn Trạng thái */
.log-badge {
    padding: 5px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.75rem;
}
/* Định nghĩa màu sắc theo cấp độ log */
.bg-danger { background-color: #fecaca !important; color: #b91c1c !important; }
.bg-warning { background-color: #fde68a !important; color: #92400e !important; }
.bg-info { background-color: #bfdbfe !important; color: #1e40af !important; }
.bg-secondary { background-color: #e5e7eb !important; color: #374151 !important; }

/* Nút Hành động */
.view-context-btn {
    background: none;
    border: none;
    color: var(--primary);
    transition: color 0.2s;
}
.view-context-btn:hover {
    color: var(--primary-hover);
}

/* Thanh Lọc */
.filter-bar .form-control, .filter-bar .form-select {
    border-radius: 8px;
    padding: 10px 15px;
    border: 1px solid var(--border-color);
}
</style>

</head>
<body>

<div class="page-container">

    <!-- Nút quay lại -->
  <button class="btn btn-light mt-4" onclick="history.back();">
    ← Quay lại
</button>
    <h2 class="section-title">
        <i class="fas fa-list-alt me-3"></i> Nhật ký Hệ thống
        <span class="badge bg-secondary ms-2 align-top"><?= $total_logs ?> bản ghi</span>
    </h2>
    
    <!-- Thanh Lọc và Tìm kiếm -->
    <div class="filter-bar mb-4">
        <form method="GET" action="system_log.php" class="row g-3 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm theo hành động hoặc người dùng..." value="<?= htmlspecialchars($search_query) ?>">
            </div>
            <div class="col-md-3">
                <select name="level" class="form-select">
                    <option value="all" <?= $filter_level == 'all' ? 'selected' : '' ?>>-- Tất cả cấp độ --</option>
                    <option value="CRITICAL" <?= $filter_level == 'CRITICAL' ? 'selected' : '' ?>>CRITICAL (Nghiêm trọng)</option>
                    <option value="ERROR" <?= $filter_level == 'ERROR' ? 'selected' : '' ?>>ERROR (Lỗi)</option>
                    <option value="WARNING" <?= $filter_level == 'WARNING' ? 'selected' : '' ?>>WARNING (Cảnh báo)</option>
                    <option value="INFO" <?= $filter_level == 'INFO' ? 'selected' : '' ?>>INFO (Thông tin)</option>
                    <option value="DEBUG" <?= $filter_level == 'DEBUG' ? 'selected' : '' ?>>DEBUG (Gỡ lỗi)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i> Lọc
                </button>
            </div>
            <?php if ($filter_level !== 'all' || !empty($search_query)): ?>
                <div class="col-md-3">
                    <a href="system_log.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-2"></i> Xóa Bộ Lọc
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Bảng Log -->
    <div class="table-responsive">
        <table class="table table-striped table-hover log-table">
            <thead>
                <tr>
                    <th scope="col" style="width: 10%;">ID</th>
                    <th scope="col" style="width: 20%;">Thời gian (created_at)</th>
                    <th scope="col" style="width: 15%;">Cấp độ (type)</th>
                    <th scope="col" style="width: 15%;">Người dùng</th>
                    <th scope="col" style="width: 30%;">Hành động (action)</th>
                    <th scope="col" style="width: 10%;" class="text-center">Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($display_logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            Không tìm thấy bản ghi nhật ký nào.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($display_logs as $log): ?>
                        <!-- Lưu trữ dữ liệu chi tiết vào thuộc tính data- cho modal -->
                        <tr data-log-id="<?= $log['id'] ?>"
                            data-log-action="<?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?>"
                            data-log-details='<?= htmlspecialchars($log['details'], ENT_QUOTES, 'UTF-8') ?>'
                            data-log-ip="<?= htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') ?>"
                            data-log-agent="<?= htmlspecialchars($log['user_agent'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <td><?= $log['id'] ?></td>
                            <td><?= $log['created_at'] ?></td>
                            <td>
                                <span class="log-badge bg-<?= $log['level_class'] ?>">
                                    <?= $log['type'] ?>
                                </span>
                            </td>
                            <td><i class="fas fa-user me-1"></i> <?= htmlspecialchars($log['user']) ?></td>
                            <td class="text-truncate" style="max-width: 300px;"><?= htmlspecialchars($log['action']) ?></td>
                            <td class="text-center">
                                <button type="button" class="view-context-btn" data-bs-toggle="modal" data-bs-target="#logDetailModal">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <nav class="mt-4" aria-label="Phân trang Log">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $current_page - 1 ?>&level=<?= $filter_level ?>&search=<?= urlencode($search_query) ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&level=<?= $filter_level ?>&search=<?= urlencode($search_query) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $current_page + 1 ?>&level=<?= $filter_level ?>&search=<?= urlencode($search_query) ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
    
</div>

<!-- Modal Chi tiết Log -->
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logDetailModalLabel">Chi tiết Log #<span id="modal-log-id"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <p class="fw-bold">Hành động (Action):</p>
        <pre id="modal-action-body" class="p-3 bg-light rounded"></pre>

        <p class="fw-bold mt-3">Thông tin truy cập:</p>
        <div class="row mb-3">
            <div class="col-md-6">
                <span class="fw-semibold">Địa chỉ IP (ip_address):</span> <span id="modal-ip-address"></span>
            </div>
            <div class="col-md-6">
                <span class="fw-semibold">User Agent (user_agent):</span> <span id="modal-user-agent"></span>
            </div>
        </div>

        <p class="fw-bold mt-3">Chi tiết bổ sung (details - JSON):</p>
        <pre id="modal-details-body" class="p-3 bg-dark text-white rounded text-break"></pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const logDetailModal = document.getElementById('logDetailModal');
    logDetailModal.addEventListener('show.bs.modal', function (event) {
        // Nút kích hoạt modal
        const button = event.relatedTarget;
        
        // Tìm hàng (tr) cha
        const row = button.closest('tr');

        // Trích xuất thông tin từ thuộc tính data-
        const logId = row.getAttribute('data-log-id');
        const action = row.getAttribute('data-log-action');
        const detailsJson = row.getAttribute('data-log-details');
        const ipAddress = row.getAttribute('data-log-ip');
        const userAgent = row.getAttribute('data-log-agent');


        // Cập nhật nội dung modal
        document.getElementById('modal-log-id').textContent = logId;
        document.getElementById('modal-action-body').textContent = action;
        document.getElementById('modal-ip-address').textContent = ipAddress;
        document.getElementById('modal-user-agent').textContent = userAgent;

        
        // Định dạng JSON chi tiết cho đẹp
        let formattedDetails = detailsJson;
        try {
            const detailsObj = JSON.parse(detailsJson);
            formattedDetails = JSON.stringify(detailsObj, null, 2);
        } catch (e) {
            // Giữ nguyên bản gốc nếu phân tích cú pháp thất bại
        }

        document.getElementById('modal-details-body').textContent = formattedDetails;
    });
});
</script>
</body>
</html>