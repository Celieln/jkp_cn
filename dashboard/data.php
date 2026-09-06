<?php

require '../config/database.php';
require '../auth/cek_login.php';

$搜索 = trim($_GET['搜索'] ?? '');

if ($搜索 !== '') {
    $安全 = str_replace(['%', '_'], ['\%', '\_'], $搜索);
    $模糊 = "%$安全%";
    $语句 = $连接->prepare("SELECT * FROM jkp WHERE nomor_register LIKE ? OR nama_perusahaan LIKE ? OR nama_pekerja LIKE ? OR nik LIKE ? ORDER BY nomor_register ASC");
    $语句->bind_param("ssss", $模糊, $模糊, $模糊, $模糊);
    $语句->execute();
    $查询结果 = $语句->get_result();
} else {
    $查询结果 = mysqli_query($连接, "SELECT * FROM jkp ORDER BY nomor_register ASC");
}

$总数 = $查询结果->num_rows;
$当前页面 = 'data';
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>数据 - 失业保障系统</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
    *{box-sizing:border-box}
    body{background:linear-gradient(135deg,#061220,#081426,#102d63);min-height:100vh;color:#e0e7f0;font-family:system-ui,-apple-system,'Segoe UI',sans-serif}
    ::-webkit-scrollbar{width:6px;height:6px}
    ::-webkit-scrollbar-track{background:rgba(255,255,255,.05);border-radius:10px}
    ::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:10px}

    .sidebar{width:260px;position:fixed;left:0;top:0;bottom:0;z-index:1040;background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border-right:1px solid rgba(255,255,255,.07);padding:0;overflow-y:auto;transition:transform .3s ease}
    .sidebar .brand{padding:22px 20px 16px;border-bottom:1px solid rgba(255,255,255,.07);text-align:center}
    .sidebar .brand h5{color:#fff;font-weight:700;letter-spacing:1px;margin:0;font-size:17px}
    .sidebar .brand small{color:rgba(255,255,255,.4);font-size:11px;display:block;margin-top:3px}
    .sidebar .nav-item{display:flex;align-items:center;gap:12px;padding:12px 20px;margin:3px 12px;border-radius:12px;color:rgba(255,255,255,.75);text-decoration:none;transition:.2s;font-weight:500;font-size:14px}
    .sidebar .nav-item:hover{background:rgba(255,255,255,.08);color:#fff;transform:translateX(3px)}
    .sidebar .nav-item.active{background:rgba(59,130,246,.25);color:#fff;box-shadow:inset 3px 0 0 #3b82f6}
    .sidebar .nav-item i{font-size:18px;width:22px;text-align:center}
    .sidebar .divider{height:1px;background:rgba(255,255,255,.06);margin:10px 20px}

    .content{margin-left:260px;padding:24px;min-height:100vh}
    .topbar{display:none;background:rgba(255,255,255,.05);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,.07);padding:12px 18px;position:sticky;top:0;z-index:1030;align-items:center;justify-content:space-between}
    .topbar .brand-mobile{color:#fff;font-weight:700;font-size:16px;letter-spacing:1px}

    .glass-card{background:rgba(255,255,255,.06);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:20px;box-shadow:0 20px 40px rgba(0,0,0,.25)}
    .glass-card .card-header{background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.07);border-radius:20px 20px 0 0!important;padding:16px 20px}
    .glass-card .card-body{padding:20px}

    .form-control{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:#fff;height:46px;border-radius:12px;padding:10px 14px}
    .form-control:focus{background:rgba(255,255,255,.12);border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.2);color:#fff}
    .form-control::placeholder{color:rgba(255,255,255,.35)}

    .table{--bs-table-bg:transparent;--bs-table-color:#e0e7f0;color:var(--bs-table-color);margin-bottom:0}
    .table thead th{background:rgba(0,0,0,.25);--bs-table-color:#f1f5f9;color:var(--bs-table-color);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid rgba(255,255,255,.07);padding:12px;white-space:nowrap}
    .table td{background:rgba(255,255,255,.02);border-top:1px solid rgba(255,255,255,.05);padding:12px;vertical-align:middle}
    .table tbody tr:hover td{background:rgba(255,255,255,.06)}
    .table-responsive{border-radius:0 0 20px 20px;overflow-x:auto}

    .btn-glass{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);color:#e0e7f0;border-radius:10px;padding:8px 16px;font-weight:600;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:14px}
    .btn-glass:hover{background:rgba(255,255,255,.13);color:#fff}
    .btn-glass.primary{background:rgba(59,130,246,.2);border-color:rgba(59,130,246,.25);color:#60a5fa}
    .btn-glass.primary:hover{background:rgba(59,130,246,.3)}
    .btn-glass.success{background:rgba(16,185,129,.2);border-color:rgba(16,185,129,.25);color:#34d399}
    .btn-glass.success:hover{background:rgba(16,185,129,.3)}
    .btn-glass.warning{background:rgba(245,158,11,.2);border-color:rgba(245,158,11,.25);color:#fbbf24}
    .btn-glass.warning:hover{background:rgba(245,158,11,.3)}
    .btn-glass.danger{background:rgba(239,68,68,.2);border-color:rgba(239,68,68,.25);color:#f87171}
    .btn-glass.danger:hover{background:rgba(239,68,68,.3)}
    .btn-glass.info{background:rgba(99,102,241,.2);border-color:rgba(99,102,241,.25);color:#a5b4fc}
    .btn-glass.info:hover{background:rgba(99,102,241,.3)}

    .small-muted{color:rgba(255,255,255,.45);font-size:13px}

    @media(max-width:992px){
        .sidebar{transform:translateX(-100%)}
        .sidebar.show{transform:translateX(0)}
        .content{margin-left:0;padding:16px}
        .topbar{display:flex}
        .overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1035;display:none}
        .overlay.show{display:block}
    }
    @media(max-width:576px){
        .content{padding:12px}
        .glass-card .card-body{padding:14px}
        .table td,.table th{padding:8px 6px;font-size:13px}
    }
</style>
</head>
<body>

<div class="overlay" id="sidebarOverlay" onclick="切换侧边栏()"></div>

<div class="sidebar" id="sidebar">
    <div class="brand">
        <h5><i class="bi bi-shield-fill-check me-2"></i>失业保障</h5>
        <small>裁员记录系统</small>
    </div>
    <a href="index.php" class="nav-item"><i class="bi bi-speedometer2"></i> 控制面板</a>
    <a href="tambah.php" class="nav-item"><i class="bi bi-plus-circle"></i> 添加数据</a>
    <a href="data.php" class="nav-item active"><i class="bi bi-table"></i> 失业保障数据</a>
    <a href="mapping.php" class="nav-item"><i class="bi bi-grid-3x3-gap"></i> Excel映射</a>
    <div class="divider"></div>
    <a href="export_rekap.php" class="nav-item"><i class="bi bi-file-spreadsheet"></i> 导出汇总</a>
    <a href="../index.php" target="_blank" class="nav-item"><i class="bi bi-globe2"></i> 公共页面</a>
    <div class="divider"></div>
    <a href="../auth/logout.php" class="nav-item" style="color:rgba(239,68,68,.7)!important"><i class="bi bi-box-arrow-right"></i> 退出登录</a>
</div>

<div class="topbar">
    <button class="btn btn-glass" onclick="切换侧边栏()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">失业保障数据</span>
    <span></span>
</div>

<div class="content">
    <div class="glass-card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0" style="color:#fff">失业保障数据</h5>
                    <div class="small-muted">总计: <b style="color:rgba(255,255,255,.7)"><?= $总数; ?></b> 条数据</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn-glass"><i class="bi bi-speedometer2"></i> 控制面板</a>
                    <a href="tambah.php" class="btn-glass success"><i class="bi bi-plus-circle"></i> 添加</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6"></div>
                <div class="col-md-6">
                    <form method="GET">
                        <div class="input-group">
                            <input type="text" name="搜索" value="<?= htmlspecialchars($搜索); ?>" class="form-control" placeholder="搜索登记号、公司、员工、身份证号...">
                            <button class="btn-glass primary px-3"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>序号</th>
                        <th>登记号</th>
                        <th>公司</th>
                        <th>员工</th>
                        <th>身份证号</th>
                        <th>裁员日期</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $序号=1; while($行数据=mysqli_fetch_assoc($查询结果)): ?>
                    <tr>
                        <td><?= $序号++; ?></td>
                        <td><?= htmlspecialchars($行数据['nomor_register']); ?></td>
                        <td><?= htmlspecialchars($行数据['nama_perusahaan']); ?></td>
                        <td><?= htmlspecialchars($行数据['nama_pekerja']); ?></td>
                        <td><?= htmlspecialchars($行数据['nik']); ?></td>
                        <td><?= !empty($行数据['tanggal_phk']) ? date('d-m-Y', strtotime($行数据['tanggal_phk'])) : '-'; ?></td>
                        <td>
                            <div class="d-flex gap-1 flex-nowrap">
                                <a href="edit.php?id=<?= $行数据['id']; ?>" class="btn-glass warning py-1 px-2" style="font-size:12px"><i class="bi bi-pencil"></i></a>
                                <a href="hapus.php?id=<?= $行数据['id']; ?>" class="btn-glass danger py-1 px-2" style="font-size:12px" onclick="return confirm('删除数据?')"><i class="bi bi-trash"></i></a>
                                <a href="export_excel.php?id=<?= $行数据['id']; ?>" class="btn-glass success py-1 px-2" style="font-size:12px"><i class="bi bi-download"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function 切换侧边栏(){
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>
</body>
</html>
