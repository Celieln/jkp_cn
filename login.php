<?php

session_start();
require 'config/database.php';

if(isset($_SESSION['login'])){
    header("Location: dashboard/");
    exit;
}

$错误 = '';

if(isset($_POST['登录'])){
    $用户名 = trim($_POST['用户名']);
    $密码值 = $_POST['密码'];

    $语句 = $连接->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
    $语句->bind_param("s", $用户名);
    $语句->execute();
    $结果 = $语句->get_result();

    if($行数据 = $结果->fetch_assoc()){
        if(password_verify($密码值, $行数据['password'])){
            session_regenerate_id(true);
            $_SESSION['login'] = true;
            $_SESSION['id'] = $行数据['id'];
            $_SESSION['nama'] = $行数据['nama_lengkap'];
            header("Location: dashboard/");
            exit;
        }
    }

    $错误 = "用户名或密码错误";
}

?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>管理员登录 - 失业保障系统</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{background:linear-gradient(135deg,#061220,#081426,#102d63);min-height:100vh;display:flex;align-items:center;color:#e0e7f0;font-family:system-ui,-apple-system,'Segoe UI',sans-serif}
.login-card{background:rgba(255,255,255,.06);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.08);border-radius:24px;padding:40px 36px;box-shadow:0 30px 60px rgba(0,0,0,.35)}
.login-card .brand{text-align:center;margin-bottom:28px}
.login-card .brand h3{color:#fff;font-weight:800;letter-spacing:1px;font-size:22px}
.login-card .brand p{color:rgba(255,255,255,.35);font-size:12px;margin-top:4px}
.form-control{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:#fff;height:50px;border-radius:14px;padding:12px 16px;font-size:14px}
.form-control:focus{background:rgba(255,255,255,.12);border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.2);color:#fff}
.form-control::placeholder{color:rgba(255,255,255,.3)}
label{font-size:13px;font-weight:600;color:rgba(255,255,255,.6);margin-bottom:6px}
.btn-login{width:100%;height:50px;border-radius:14px;font-weight:700;background:linear-gradient(135deg,#2563eb,#1d4ed8);border:none;color:#fff;box-shadow:0 4px 18px rgba(37,99,235,.35);transition:.2s;font-size:15px;letter-spacing:1px}
.btn-login:hover{background:linear-gradient(135deg,#1d4ed8,#1e40af);transform:translateY(-2px);box-shadow:0 8px 25px rgba(37,99,235,.45)}
.btn-login:active{transform:translateY(0)}
.back-link{display:block;text-align:center;margin-top:16px;color:rgba(255,255,255,.3);font-size:13px;text-decoration:none;transition:.2s}
.back-link:hover{color:rgba(255,255,255,.5)}
.alert-custom{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.15);border-radius:12px;padding:10px 14px;color:#f87171;font-size:13px;margin-bottom:18px}
</style>
</head>
<body>
<div class="container">
<div class="row justify-content-center">
<div class="col-md-5 col-lg-4">
<div class="login-card">
<div class="brand">
<h3><i class="bi bi-shield-fill-check me-2"></i>失业保障</h3>
<p>裁员记录系统</p>
</div>
<h4 class="fw-bold mb-3 text-center" style="color:#fff;font-size:17px">管理员登录</h4>
<?php if($错误): ?>
<div class="alert-custom"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $错误 ?></div>
<?php endif; ?>
<form method="POST">
<div class="mb-3">
<label>用户名</label>
<input type="text" name="用户名" class="form-control" placeholder="请输入用户名" required>
</div>
<div class="mb-3">
<label>密码</label>
<input type="password" name="密码" class="form-control" placeholder="请输入密码" required>
</div>
<button name="登录" class="btn-login">登录</button>
</form>
<a href="index.php" class="back-link"><i class="bi bi-arrow-left me-1"></i> 返回公共页面</a>
</div>
</div>
</div>
</div>
</body>
</html>
