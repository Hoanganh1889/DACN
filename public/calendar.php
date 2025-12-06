<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$uid  = $user['id'];

/* ==========================
    THÊM SỰ KIỆN
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {

    $title = trim($_POST['title']);
    $start = $_POST['start'];
    $end   = $_POST['end'];

    if ($title !== "" && $start !== "") {
        $stmt = $conn->prepare("INSERT INTO events (user_id, title, start, end) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $uid, $title, $start, $end);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: calendar.php");
    exit;
}

/* ==========================
    XÓA SỰ KIỆN
========================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM events WHERE id=$id AND user_id=$uid");
    header("Location: calendar.php");
    exit;
}

/* ==========================
    LẤY SỰ KIỆN
========================== */
$events = [];
$res = $conn->query("SELECT * FROM events WHERE user_id=$uid");

while ($row = $res->fetch_assoc()) {
    $events[] = [
        "id"    => $row["id"],
        "title" => $row["title"],
        "start" => $row["start"],
        "end"   => $row["end"]
    ];
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Lịch làm việc</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />

<style>
body {
    background: #f3f4f6;
    font-family: 'Inter', sans-serif;
}

.container-box {
    max-width: 1100px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.back-btn {
    padding: 10px 16px;
    background: #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
}
.back-btn:hover {
    background: #d1d5db;
}

.fc {
    background: white;
    border-radius: 12px;
    padding: 20px;
}
/* TẮT highlight khi hover vào ngày */
.fc-daygrid-day:hover {
    background: transparent !important;
}

/* TẮT highlight khi click chọn ngày */
.fc-daygrid-day.fc-day-today {
    background: transparent !important;
    border: none !important;
}

/* Làm ngày nhỏ gọn hơn */
.fc-daygrid-day-number {
    font-size: 0.85rem !important;
    color: #374151;
    padding: 4px;
}

/* Tắt border đậm của ô ngày */
.fc-theme-standard td, 
.fc-theme-standard th {
    border-color: #e5e7eb !important; /* xám rất nhẹ */
}

/* Bo tròn nhẹ ô ngày */
.fc-daygrid-day-frame {
    border-radius: 6px;
}

/* Hover nhẹ nhàng – không bôi đen */
.fc-daygrid-day-frame:hover {
    background: #f3f4f6 !important; /* xám nhạt */
    transition: 0.2s;
}

/* Màu tiêu đề tháng */
.fc-toolbar-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2937;
}

.event-box {
    margin-top: 25px;
}
.event-item {
    padding: 12px;
    border-radius: 8px;
    background: #f9fafb;
    border: 1px solid #ddd;
    margin-bottom: 8px;
}
.event-item:hover {
    background: #eef2ff;
}
</style>

</head>
<body>

<div class="container-box">
    <h2 class="page-title"><i class="fas fa-calendar-alt me-2"></i> Lịch làm việc</h2>

    <!-- NÚT TẠO SỰ KIỆN -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addEventModal">
        + Thêm sự kiện
    </button>

    <!-- FULL CALENDAR -->
    <div id="calendar"></div>
  <button class="btn btn-light mt-4" onclick="history.back();">
    ← Quay lại
</button>

    <!-- DANH SÁCH SỰ KIỆN -->
    <div class="event-box">
        <h4 class="fw-bold">📌 Sự kiện gần đây</h4>

        <?php if (empty($events)): ?>
            <p class="text-muted">Chưa có sự kiện nào.</p>
        <?php else: ?>
            <?php foreach($events as $ev): ?>
                <div class="event-item d-flex justify-content-between">
                    <div>
                        <b><?=htmlspecialchars($ev['title'])?></b><br>
                        <small><?=date("H:i d/m/Y", strtotime($ev['start']))?></small>
                    </div>
                    <a href="?delete=<?=$ev['id']?>" class="btn btn-sm btn-danger">Xóa</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL TẠO SỰ KIỆN -->
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">

            <input type="hidden" name="create_event" value="1">

            <div class="modal-header">
                <h5 class="modal-title">Tạo sự kiện mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                
                <label class="fw-bold">Tên sự kiện:</label>
                <input type="text" name="title" class="form-control mb-3" required>

                <label class="fw-bold">Thời gian bắt đầu:</label>
                <input type="datetime-local" name="start" class="form-control mb-3" required>

                <label class="fw-bold">Thời gian kết thúc:</label>
                <input type="datetime-local" name="end" class="form-control mb-3">

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button class="btn btn-primary">Lưu</button>
            </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },

        initialView: 'dayGridMonth',
        events: <?=json_encode($events)?>,

        selectable: true,
        dateClick: function(info) {
            document.querySelector("input[name=start]").value = info.dateStr + "T09:00";
            var modal = new bootstrap.Modal(document.getElementById('addEventModal'));
            modal.show();
        }
    });

    calendar.render();
});
</script>

</body>
</html>
