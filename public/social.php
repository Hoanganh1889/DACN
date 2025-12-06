<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$uid  = (int)$user['id'];

$msg   = '';
$error = '';

// ================== XỬ LÝ ĐĂNG BÀI ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    $content = trim($_POST['content'] ?? '');
    $image_path = null;

    if ($content === '' && (empty($_FILES['image']['name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK)) {
        $error = 'Vui lòng nhập nội dung hoặc chọn hình ảnh.';
    } else {
        // Upload ảnh nếu có
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $uploadDir = '../uploads/posts/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $filename   = 'post_' . $uid . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $image_path = 'uploads/posts/' . $filename;
                }
            } else {
                $error = 'Chỉ hỗ trợ ảnh JPG, PNG, GIF, WEBP.';
            }
        }

        if ($error === '') {
            $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image_path) VALUES (?,?,?)");
            $stmt->bind_param('iss', $uid, $content, $image_path);
            $stmt->execute();
            $stmt->close();
            $msg = '✅ Đăng bài thành công.';
        }
    }
}

// ================== XỬ LÝ LIKE / UNLIKE ==================
if (isset($_GET['like'])) {
    $post_id = (int)$_GET['like'];

    // Kiểm tra đã like chưa
    $stmt = $conn->prepare("SELECT id FROM post_likes WHERE post_id=? AND user_id=?");
    $stmt->bind_param('ii', $post_id, $uid);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 0) {
        $stmt->close();
        $stmt2 = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?,?)");
        $stmt2->bind_param('ii', $post_id, $uid);
        $stmt2->execute();
        $stmt2->close();
    } else {
        $stmt->close();
        // Nếu đã like rồi thì bỏ like
        $stmt3 = $conn->prepare("DELETE FROM post_likes WHERE post_id=? AND user_id=?");
        $stmt3->bind_param('ii', $post_id, $uid);
        $stmt3->execute();
        $stmt3->close();
    }

    header('Location: social.php');
    exit;
}

// ================== XỬ LÝ COMMENT ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_post_id'])) {
    $post_id = (int)$_POST['comment_post_id'];
    $comment = trim($_POST['comment_text'] ?? '');

    if ($comment !== '') {
        $stmt = $conn->prepare("INSERT INTO post_comments (post_id, user_id, comment_text) VALUES (?,?,?)");
        $stmt->bind_param('iis', $post_id, $uid, $comment);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: social.php');
    exit;
}

// ================== XỬ LÝ SHARE ==================
if (isset($_GET['share'])) {
    $post_id = (int)$_GET['share'];

    // Chỉ cần ghi nhận share
    $stmt = $conn->prepare("INSERT INTO post_shares (post_id, user_id) VALUES (?,?)");
    $stmt->bind_param('ii', $post_id, $uid);
    $stmt->execute();
    $stmt->close();

    header('Location: social.php');
    exit;
}

// ================== LẤY DANH SÁCH BÀI VIẾT ==================
$posts = $conn->query("
    SELECT p.*, u.username,
           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) AS comment_count,
           (SELECT COUNT(*) FROM post_shares WHERE post_id = p.id) AS share_count,
           EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = $uid) AS is_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
");
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mạng xã hội nội bộ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

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
.sidebar{
    width:var(--sidebar-width);background-color:var(--sidebar-bg);color:var(--text-light);
    position:fixed;top:0;left:0;height:100vh;padding-top:var(--header-height);
    box-shadow:4px 0 8px rgba(0,0,0,0.2);z-index:1020;
    overflow-x: hidden;
scrollbar-width: thin;

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
.post-card{
    margin-bottom:20px;
}
.post-header{
    display:flex;align-items:center;gap:10px;margin-bottom:8px;
}
.post-avatar{
    width:40px;height:40px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;
    font-weight:bold;color:#555;
}
.post-username{font-weight:600;}
.post-time{font-size:0.8rem;color:#888;}
.post-image{
    max-height:350px;object-fit:cover;border-radius:10px;margin-top:10px;
}
.post-actions button, .post-actions a{
    margin-right:10px;
}
.comment-box{
    margin-top:10px;
}
.comment-item{
    font-size:0.9rem;margin-bottom:4px;
}
.comment-author{
    font-weight:600;
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="header d-flex align-items-center">
    <button class="btn btn-outline-secondary d-lg-none me-3" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <h4 class="logo mb-0">DACN</h4>
    <div class="ms-auto d-flex align-items-center">
        <span class="me-3 text-secondary d-none d-sm-inline">
            <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($user['username']) ?>
        </span>
        <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt me-1"></i>Đăng xuất</a>
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
    <div class="container-fluid">

        <?php if($msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- FORM ĐĂNG BÀI -->
        <div class="card mb-4">
            <div class="card-header-custom">
                <i class="fas fa-pen-nib me-2"></i> Tạo bài viết mới
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <textarea class="form-control" name="content" rows="3" placeholder="Bạn đang nghĩ gì?"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ảnh đính kèm (tùy chọn)</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                    </div>
                    <button class="btn btn-primary" type="submit" name="new_post">
                        <i class="fas fa-paper-plane me-1"></i> Đăng bài
                    </button>
                </form>
            </div>
        </div>

        <!-- DANH SÁCH BÀI VIẾT -->
        <?php if($posts && $posts->num_rows): ?>
            <?php while($p = $posts->fetch_assoc()): ?>
                <div class="card post-card">
                    <div class="card-body">
                        <div class="post-header">
                            <div class="post-avatar">
                                <?= strtoupper(substr($p['username'],0,1)) ?>
                            </div>
                            <div>
                                <div class="post-username"><?= htmlspecialchars($p['username']) ?></div>
                                <div class="post-time"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php if($p['content']): ?>
                            <p class="mt-2"><?= nl2br(htmlspecialchars($p['content'])) ?></p>
                        <?php endif; ?>
                        <?php if($p['image_path']): ?>
                            <img src="../<?= htmlspecialchars($p['image_path']) ?>" class="img-fluid post-image" alt="Post image">
                        <?php endif; ?>

                        <div class="post-actions mt-3">
                            <a href="social.php?like=<?= $p['id'] ?>" class="btn btn-sm <?= $p['is_liked'] ? 'btn-success' : 'btn-outline-success' ?>">
                                <i class="fas fa-thumbs-up"></i> Thích (<?= (int)$p['like_count'] ?>)
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('cmt-<?= $p['id'] ?>').focus();">
                                <i class="fas fa-comment"></i> Bình luận (<?= (int)$p['comment_count'] ?>)
                            </button>
                            <a href="social.php?share=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-share"></i> Chia sẻ (<?= (int)$p['share_count'] ?>)
                            </a>
                        </div>

                        <!-- COMMENT LIST -->
                        <div class="mt-3">
                            <?php
                            $pid = (int)$p['id'];
                            $comments = $conn->query("
                                SELECT c.*, u.username 
                                FROM post_comments c 
                                JOIN users u ON c.user_id = u.id
                                WHERE c.post_id = $pid
                                ORDER BY c.created_at ASC
                            ");
                            while($c = $comments->fetch_assoc()):
                            ?>
                                <div class="comment-item">
                                    <span class="comment-author"><?= htmlspecialchars($c['username']) ?>:</span>
                                    <span><?= nl2br(htmlspecialchars($c['comment_text'])) ?></span>
                                    <span class="text-muted" style="font-size:0.75rem;"> (<?= date('d/m H:i', strtotime($c['created_at'])) ?>)</span>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <!-- COMMENT FORM -->
                        <form method="POST" class="comment-box mt-2">
                            <input type="hidden" name="comment_post_id" value="<?= $p['id'] ?>">
                            <div class="input-group">
                                <input type="text" id="cmt-<?= $p['id'] ?>" name="comment_text" class="form-control" placeholder="Viết bình luận...">
                                <button class="btn btn-outline-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">Chưa có bài viết nào. Hãy là người đầu tiên đăng bài!</div>
        <?php endif; ?>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const sidebar = document.getElementById('sidebar');
    const toggle  = document.getElementById('sidebarToggle');
    if (toggle){
        toggle.addEventListener('click', ()=> sidebar.classList.toggle('show'));
    }
});
</script>

</body>
</html>
