<?php
session_start();
require_once '../config/db.php';

// --- Logic PHP giữ nguyên ---
if (isset($_GET['logout'])) { session_destroy(); header('Location: login.php'); exit; }
if (isset($_SESSION['user'])) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username'] ?? '');
  $p = trim($_POST['password'] ?? '');
  
  if ($u === '' || $p === '') {
    $error = 'Vui lòng nhập đầy đủ thông tin.';
  } else {
    $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param('s', $u);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows) {
      $row = $res->fetch_assoc();
      if (password_verify($p, $row['password'])) {
        $_SESSION['user'] = [
          'id' => (int)$row['id'],
          'username' => $row['username'],
          'email' => $row['email'],
          'role' => $row['role']
        ];
        header('Location: dashboard.php');
        exit;
      } else {
        $error = 'Sai mật khẩu.';
      }
    } else {
      $error = 'Tên đăng nhập không tồn tại.';
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
  <title>Đăng nhập</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;

      /* Nền đẹp dạng Blur + Gradient */
      background: linear-gradient(135deg, rgba(0,150,200,0.5), rgba(0,0,70,0.4)),
                  url('https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1350&q=80');
      background-size: cover;
      background-position: center;
      backdrop-filter: blur(10px);
    }

    .login-container {
      width: 100%;
      max-width: 420px;
      padding: 40px 35px;
      border-radius: 18px;

      /* Glass effect */
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 8px 32px rgba(0,0,0,0.25);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.3);

      animation: fadeIn 0.6s ease-out;
    }

    .login-title {
      color: white;
      font-weight: 700;
      margin-bottom: 25px;
    }

    .form-control {
      border-radius: 10px;
      padding: 12px;
      background: rgba(255,255,255,0.7);
    }

    .btn-primary {
      background: #00a8e8;
      border: none;
      padding: 12px;
      border-radius: 10px;
      font-weight: 600;
    }

    .btn-primary:hover {
      background: #008ccc;
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
      from {opacity: 0; transform: translateY(20px);}
      to {opacity: 1; transform: translateY(0);}
    }
  </style>
</head>

<body>
<div class="login-container">

  <div class="text-center mb-4">
    <i class="fas fa-user-shield fa-3x" style="color: #fff;"></i>
  </div>

  <h3 class="text-center login-title">Đăng nhập hệ thống</h3>

  <?php if ($error): ?>
    <div class="alert alert-danger text-center">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <input class="form-control"
             name="username"
             placeholder="Tên đăng nhập"
             required
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    </div>

    <div class="mb-4">
      <input class="form-control"
             type="password"
             name="password"
             placeholder="Mật khẩu"
             required>
    </div>

    <button class="btn btn-primary w-100 mb-3" type="submit">
      <i class="fas fa-sign-in-alt me-2"></i> Đăng nhập
    </button>
  </form>

  <div class="text-center mt-3 text-light auth-link">
    <a href="forgot_password.php">Quên mật khẩu?</a>
  </div>

  <div class="text-center mt-2 text-light auth-link">
    Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
