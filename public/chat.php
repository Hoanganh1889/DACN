<?php
session_start();
require_once '../config/db.php';

// KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php'); 
    exit;
}

$user = $_SESSION['user']; 
$uid  = (int)$user['id'];

// --- CHAT RIÊNG ---
$receiver_id = isset($_GET['to']) ? (int)$_GET['to'] : null;

$where = $receiver_id
    ? "(sender_id=$uid AND receiver_id=$receiver_id) OR (sender_id=$receiver_id AND receiver_id=$uid)"
    : "receiver_id IS NULL";

// --- GỬI TIN NHẮN & FILE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $uploaded_files = [];

    if (!empty($_FILES['files']['name'][0])) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['files']['name'] as $i => $name) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $filename = time() . '_' . uniqid() . '_' . basename($name);
                $target = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $target)) {
                    $uploaded_files[] = 'uploads/' . $filename;
                }
            }
        }
    }

    $files_json = $uploaded_files ? json_encode($uploaded_files, JSON_UNESCAPED_SLASHES) : null;

    if ($message !== '' || $files_json) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, file_paths) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiss', $uid, $receiver_id, $message, $files_json);
        $stmt->execute();
    }

    header('Location: chat.php' . ($receiver_id ? '?to=' . $receiver_id : ''));
    exit;
}

// --- XÓA TIN NHẮN ---
if (isset($_GET['del'])) {
    $id  = (int)$_GET['del'];
    $msg = $conn->query("SELECT sender_id,file_paths FROM messages WHERE id=$id")->fetch_assoc();

    if ($msg && ($msg['sender_id'] == $uid || $user['role'] === 'admin')) {

        if (!empty($msg['file_paths'])) {
            foreach (json_decode($msg['file_paths'], true) as $f) {
                $file_path = '../' . $f;
                if (strpos(realpath($file_path), realpath('../uploads/')) === 0 && file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }

        $conn->query("DELETE FROM messages WHERE id=$id");
    }

    header('Location: chat.php' . ($receiver_id ? '?to=' . $receiver_id : ''));
    exit;
}

// --- LẤY TIN NHẮN & DANH SÁCH USER ---
$msgs = $conn->query("
    SELECT m.*, u.username
    FROM messages m
    JOIN users u ON m.sender_id=u.id
    WHERE $where
    ORDER BY m.created_at ASC
    LIMIT 100
");

$users = $conn->query("SELECT id, username, status FROM users WHERE id!=$uid");

// Lấy tên người nhận (nếu đang ở chat riêng)
$receiver_username = '';
if ($receiver_id) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id=?");
    $stmt->bind_param('i', $receiver_id);
    $stmt->execute();
    $result_receiver  = $stmt->get_result();
    $receiver_username = $result_receiver->fetch_assoc()['username'] ?? 'Người dùng không tồn tại';
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>💬 Chat Nâng Cao</title>
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
}

/* BODY */
body {
    font-family: 'Inter', sans-serif;
    background-color: var(--content-bg);
    margin: 0;
    padding-top: var(--header-height);
    height: 100vh;
    overflow: hidden;
}

/* HEADER */
.header {
    position: fixed;
    top: 0; left: 0;
    width: 100%;
    height: var(--header-height);
    background-color: var(--card-bg);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    z-index: 1030;
    padding: 0 30px;
}
.header .logo {
    color: var(--primary-color);
    font-weight: 800;
    font-size: 1.6rem;
    letter-spacing: -0.5px;
}

/* SIDEBAR */
.sidebar {
    width: var(--sidebar-width);
    background-color: var(--sidebar-bg);
    color: var(--text-light);
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    padding-top: var(--header-height);
    box-shadow: 4px 0 8px rgba(0,0,0,0.2);
    overflow-y: auto;
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
    gap: 12px;
    padding: 14px 25px;
    color: var(--text-light);
    text-decoration: none;
    font-weight: 500;
    border-left: 4px solid transparent;
    transition: all 0.25s;
}
.sidebar nav a:hover {
    background-color: var(--secondary-color);
    border-left-color: var(--primary-color);
}
.sidebar nav a.active {
    background-color: var(--primary-color);
    color: #fff;
    font-weight: 600;
    border-left-color: #fff;
}

/* CHAT RIÊNG */
.dm-section {
    margin-top: 20px;
    padding: 0 20px 20px;
}
.dm-section h6 {
    color: #cbd5e1;
    text-transform: uppercase;
    font-size: 0.8rem;
    font-weight: 700;
    margin-bottom: 10px;
    letter-spacing: 0.5px;
}
.dm-list a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: rgba(255,255,255,0.05);
    color: #f3f4f6;
    text-decoration: none;
    border-radius: 8px;
    padding: 10px 15px;
    margin-bottom: 8px;
    font-size: 0.95rem;
    transition: all 0.25s;
}
.dm-list a:hover {
    background-color: rgba(255,255,255,0.12);
    transform: translateX(3px);
}
.dm-list a.active {
    background-color: var(--primary-color);
    font-weight: 600;
    color: #fff;
}
.dm-list a i { width: 20px; text-align: center; color: #a3b1c6; margin-right: 10px; }
.online-status {
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-left: 8px;
    border: 2px solid rgba(255,255,255,0.5);
}
.online { background-color: #22c55e; }
.offline { background-color: #6b7280; }

/* MAIN CONTENT */
.content-wrapper {
    margin-left: var(--sidebar-width);
    padding: 30px;
    height: calc(100vh - var(--header-height));
    display: flex; 
    flex-direction: column;
}

/* CHAT CARD */
.chat-card {
    background-color: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    display: flex; flex-direction: column; 
    flex-grow: 1;
    overflow: hidden;
}
.chat-header {
    background-color: var(--secondary-color);
    color: white; 
    padding: 20px 30px;
    font-weight: 600; 
    font-size: 1.2rem;
}

/* VÙNG CUỘN TIN NHẮN (CHỈ MAIN CUỘN, SIDEBAR & HEADER ĐỨNG YÊN) */
.message-scroll-wrapper {
    flex-grow: 1;
    overflow-y: auto;
    background: var(--content-bg);
}
.message-container {
    padding: 20px 30px;
    display: flex;
    flex-direction: column;
}

/* TIN NHẮN */
.message-wrapper { margin-bottom: 12px; display: flex; }
.sender { justify-content: flex-end; }
.receiver { justify-content: flex-start; }
.message-content {
    padding: 12px 16px; max-width: 70%;
    border-radius: 18px; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.sender .message-content { 
    background: var(--primary-color); 
    color: white; 
    border-radius: 18px 18px 4px 18px; 
}
.receiver .message-content { 
    background: white; 
    color: #333; 
    border-radius: 18px 18px 18px 4px; 
}

.delete-btn { 
    opacity: 0; 
    transition: 0.2s; 
    margin-left: 8px; 
}
.message-wrapper:hover .delete-btn { opacity: 1; }

/* FORM GỬI */
.form-send { 
    padding: 20px 30px; 
    border-top: 1px solid #ddd; 
    background-color: white; 
}

@media(max-width:992px){
    .sidebar{left:-280px;transition:.3s}
    .sidebar.show{left:0}
    .content-wrapper{margin-left:0;padding:15px}
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
        <a href="dashboard.php"><i class="fas fa-chart-line fa-fw me-3"></i> Dashboard</a>
        <?php if($user['role']==='admin'):?>
            <a href="admin.php"><i class="fas fa-users-cog fa-fw me-3"></i> Quản Lý Admin</a>
            <a href="system_logs.php"><i class="fas fa-file-alt fa-fw me-3"></i> Nhật ký hệ thống</a>
        <?php endif;?>
        <a href="project_ai.php"><i class="fas fa-brain fa-fw me-3"></i> Phân tích dự án (AI)</a>
        <a href="todo.php"><i class="fas fa-clipboard-list fa-fw me-3"></i> Công việc</a>  
        <a href="social.php"><i class="fas fa-share-alt fa-fw me-3"></i> Mạng xã hội</a>
        <a href="profile.php"><i class="fas fa-user-circle fa-fw me-3"></i> Hồ sơ cá nhân</a>
        <a class="active" href="chat.php"><i class="fas fa-comments fa-fw me-3"></i> Chat</a>
    </nav>

    <hr class="text-secondary mx-3">
    <div class="dm-section">
        <h6><i class="fas fa-user-friends me-2"></i> Chat riêng</h6>
        <div class="dm-list">
            <a href="chat.php" class="<?=!$receiver_id?'active':''?>">
                <div class="d-flex align-items-center">
                    <i class="fas fa-globe"></i><span>Phòng chung</span>
                </div>
                <span class="online-status online"></span>
            </a>
            <?php while($u=$users->fetch_assoc()): ?>
            <a href="?to=<?=$u['id']?>" class="<?=($receiver_id==$u['id']?'active':'')?>">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user"></i><span><?=htmlspecialchars($u['username'])?></span>
                </div>
                <span class="online-status <?=$u['status']==='online'?'online':'offline'?>"></span>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
</aside>

<!-- MAIN -->
<main class="content-wrapper">
    <div class="chat-card">

        <div class="chat-header">
            <?php if($receiver_id): ?>
                💬 Chat với <b><?= htmlspecialchars($receiver_username) ?></b>
            <?php else: ?>
                🌐 Phòng chat chung
            <?php endif; ?>
        </div>

        <!-- VÙNG CUỘN TIN NHẮN -->
        <div class="message-scroll-wrapper">
            <div class="message-container" id="message-container">
                <?php if($msgs->num_rows == 0): ?>
                    <p class="text-muted text-center mt-3">Chưa có tin nhắn...</p>
                <?php endif; ?>

                <?php while($r = $msgs->fetch_assoc()): 
                    $is_sender = $r['sender_id'] == $uid;
                ?>
                    <div class="message-wrapper <?= $is_sender ? 'sender' : 'receiver' ?>">

                        <div class="message-content">
                            <?php if(!$is_sender): ?>
                                <div class="fw-bold text-primary"><?= htmlspecialchars($r['username']) ?></div>
                            <?php endif; ?>

                            <?php if($r['message']): ?>
                                <div><?= nl2br(htmlspecialchars($r['message'])) ?></div>
                            <?php endif; ?>

                            <?php if($r['file_paths']): ?>
                                <div class="mt-2">
                                    <?php foreach(json_decode($r['file_paths'], true) as $file): 
                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                        $url = "../$file";
                                    ?>
                                        <?php if(in_array($ext,['jpg','jpeg','png','gif','webp'])): ?>
                                            <img src="<?= $url ?>" class="img-fluid rounded mb-2" style="max-height:200px;">
                                        <?php else: ?>
                                            <p><a target="_blank" href="<?= $url ?>"><i class="fas fa-paperclip"></i> <?= basename($file) ?></a></p>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="text-end text-muted" style="font-size:12px;">
                                <?= date("H:i", strtotime($r['created_at'])) ?>
                            </div>
                        </div>

                        <?php if($is_sender || $user['role']=='admin'): ?>
                            <a href="?del=<?= $r['id'] ?><?= $receiver_id ? "&to=".$receiver_id : "" ?>" 
                               class="delete-btn text-danger">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        <?php endif; ?>

                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- FORM GỬI TIN -->
        <form method="POST" enctype="multipart/form-data" class="form-send row g-2">
            <div class="col-md-8">
                <input class="form-control rounded-pill" name="message" placeholder="Nhập tin nhắn...">
            </div>
            <div class="col-md-3">
                <input class="form-control rounded-pill" type="file" name="files[]" multiple>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary rounded-pill w-100">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{

    // Toggle sidebar
    const sidebar=document.getElementById('sidebar');
    const toggle=document.getElementById('sidebarToggle');
    if(toggle){
        toggle.addEventListener('click',()=>sidebar.classList.toggle('show'));
    }

    // Xác nhận xóa
    document.querySelectorAll('.delete-btn').forEach(el=>{
        el.addEventListener('click',e=>{
            if(!confirm("Bạn có chắc muốn xóa tin nhắn này không?")) e.preventDefault();
        });
    });

    // Cuộn xuống tin nhắn cuối
    const box=document.querySelector('.message-scroll-wrapper');
    const scrollBottom=()=>{ if(box) box.scrollTop = box.scrollHeight; };
    scrollBottom();

    // Auto reload như Zalo
    setInterval(()=>{
        fetch(location.href)
        .then(res=>res.text())
        .then(html=>{
            const dom = new DOMParser().parseFromString(html,"text/html");
            const newHTML = dom.querySelector("#message-container").innerHTML;
            const container = document.getElementById('message-container');
            if (container && container.innerHTML !== newHTML) {
                container.innerHTML = newHTML;
                scrollBottom();
            }
        });
    }, 3000);

});
</script>
</body>
</html>
