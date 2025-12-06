<?php
session_start();
require_once '../config/db.php';

// --- Logic PHP GIỮ NGUYÊN ---
if (isset($_SESSION['user'])) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username'] ?? '');
  $e = trim($_POST['email'] ?? '');
  $p = trim($_POST['password'] ?? '');
  
  if ($u === '' || $e === '' || $p === '') {
    $error = 'Vui lòng nhập đầy đủ thông tin.';
  } else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
    $stmt->bind_param('ss', $u, $e);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows) {
      $error = 'Tên đăng nhập hoặc email đã tồn tại.';
    } else {
      $hash = password_hash($p, PASSWORD_DEFAULT);
      $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'user', 'offline')");
      $stmt_insert->bind_param('sss', $u, $e, $hash);
      
      if ($stmt_insert->execute()) {
        $_SESSION['user'] = [
          'id' => $stmt_insert->insert_id,
          'username' => $u,
          'email' => $e,
          'role' => 'user'
        ];
        header('Location: dashboard.php');
        exit;
      } else {
        $error = 'Không thể đăng ký. Lỗi hệ thống.';
      }
    }
    $stmt->close();
  }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng ký Tài khoản</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <style>
    :root {
      --primary-color: #00a8e8;
      --secondary-color: #3f6583;
      --success-color: #28a745;
    }

    /* NỀN GIỐNG TRANG LOGIN */
    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;

      background: linear-gradient(135deg, rgba(0,150,200,0.5), rgba(0,0,70,0.4)),
                  url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1350&q=80');
      background-size: cover;
      background-position: center;
      backdrop-filter: blur(10px);
    }

    .register-container {
      width: 100%;
      max-width: 420px;
      padding: 40px 35px;
      border-radius: 18px;

      /* Hiệu ứng kính giống login */
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 32px rgba(0,0,0,0.25);
      border: 1px solid rgba(255,255,255,0.3);

      animation: fadeIn .6s ease-out;
    }

    .register-title {
      color: white;
      font-weight: 700;
      margin-bottom: 25px;
      font-size: 1.8rem;
    }

    .form-control {
      border-radius: 10px;
      padding: 12px;
      background: rgba(255,255,255,0.8);
    }

    .btn-success-custom {
      background-color: var(--success-color);
      border: none;
      border-radius: 10px;
      padding: 12px;
      font-weight: 600;
    }

    .btn-success-custom:hover {
      background-color: #218838;
    }

    .auth-link a {
      color: #fff;
      font-weight: 500;
      text-decoration: none;
    }
    .auth-link a:hover {
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from { opacity:0; transform:translateY(20px); }
      to { opacity:1; transform:translateY(0); }
    }
  </style>

</head>
<body>

<div class="register-container">
  <div class="text-center mb-4">
    <i class="fas fa-user-plus fa-3x" style="color: #fff;"></i>
  </div>

  <h3 class="text-center register-title">Tạo tài khoản mới</h3>

  <?php if ($error): ?>
    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <input class="form-control"
             name="username"
             placeholder="Tên đăng nhập"
             required
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <input class="form-control"
             type="email"
             name="email"
             placeholder="Email"
             required
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>

    <div class="mb-4">
      <input class="form-control"
             type="password"
             name="password"
             placeholder="Mật khẩu"
             required>
    </div>

    <button class="btn btn-success-custom w-100 mb-3" type="submit">
      <i class="fas fa-user-plus me-2"></i> Đăng ký
    </button>
  </form>

  <div class="text-center mt-3 text-light auth-link">
    Đã có tài khoản? <a href="login.php">Đăng nhập</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
