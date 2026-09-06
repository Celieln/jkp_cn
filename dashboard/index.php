<?php

require '../config/database.php';
require '../auth/cek_login.php';

$总数据 = mysqli_fetch_assoc(
    mysqli_query($连接, "SELECT COUNT(*) AS total FROM jkp")
);

$总映射 = mysqli_fetch_assoc(
    mysqli_query($连接, "SELECT COUNT(*) AS total FROM mapping_excel")
);

$模板路径 = '../templates/template_jkp.xlsx';
$模板状态 = file_exists($模板路径) ? '有效' : '不存在';

$最新数据 = mysqli_query(
    $连接,
    "SELECT id, nomor_register, nama_pekerja, nama_perusahaan, tanggal_phk
     FROM jkp
     ORDER BY tanggal_phk DESC, id DESC
     LIMIT 5"
);

$当前页面 = 'index';

?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>控制面板 - 失业保障系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *{box-sizing:border-box}
        body{background:linear-gradient(135deg,#061220,#081426,#102d63);min-height:100vh;color:#e0e7f0;font-family:system-ui,-apple-system,'Segoe UI',sans-serif}
        ::-webkit-scrollbar{width:6px;height:6px}
        ::-webkit-scrollbar-track{background:rgba(255,255,255,.05);border-radius:10px}
        ::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:10px}
        ::-webkit-scrollbar-thumb:hover{background:rgba(255,255,255,.25)}

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

        .stat-card{background:rgba(255,255,255,.06);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:18px;height:100%;padding:20px;transition:.25s}
        .stat-card:hover{transform:translateY(-2px);background:rgba(255,255,255,.09)}
        .stat-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
        .stat-icon.blue{background:rgba(59,130,246,.2);color:#60a5fa}
        .stat-icon.green{background:rgba(16,185,129,.2);color:#34d399}
        .stat-icon.amber{background:rgba(245,158,11,.2);color:#fbbf24}

        .table{--bs-table-bg:transparent;--bs-table-color:#e0e7f0;color:var(--bs-table-color);margin-bottom:0}
        .table thead th{background:rgba(0,0,0,.25);--bs-table-color:#f1f5f9;color:var(--bs-table-color);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid rgba(255,255,255,.07);padding:12px}
        .table td{background:rgba(255,255,255,.02);border-top:1px solid rgba(255,255,255,.05);padding:12px;vertical-align:middle}
        .table tbody tr:hover td{background:rgba(255,255,255,.06)}
        .table-responsive{border-radius:0 0 20px 20px;overflow-x:auto}

        .badge-soft{background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.2);font-weight:600;padding:6px 14px;border-radius:20px;font-size:13px}
        .badge-soft.green{background:rgba(16,185,129,.15);color:#34d399;border-color:rgba(16,185,129,.2)}
        .small-muted{color:rgba(255,255,255,.45);font-size:13px}
        .btn-glass{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);color:#e0e7f0;border-radius:12px;padding:10px 18px;font-weight:600;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
        .btn-glass:hover{background:rgba(255,255,255,.13);color:#fff;transform:translateY(-1px)}
        .btn-glass.primary{background:rgba(59,130,246,.2);border-color:rgba(59,130,246,.25);color:#60a5fa}
        .btn-glass.primary:hover{background:rgba(59,130,246,.3)}
        .btn-glass.success{background:rgba(16,185,129,.2);border-color:rgba(16,185,129,.25);color:#34d399}
        .btn-glass.success:hover{background:rgba(16,185,129,.3)}
        .btn-glass.danger{background:rgba(239,68,68,.2);border-color:rgba(239,68,68,.25);color:#f87171}
        .btn-glass.danger:hover{background:rgba(239,68,68,.3)}

        .quick-link{display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:14px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);color:#e0e7f0;text-decoration:none;transition:.2s}
        .quick-link:hover{background:rgba(255,255,255,.08);color:#fff;transform:translateX(3px)}
        .quick-link i{font-size:20px;width:24px;text-align:center}

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
            .stat-card{padding:14px}
            .glass-card .card-body{padding:14px}
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
    <a href="index.php" class="nav-item active"><i class="bi bi-speedometer2"></i> 控制面板</a>
    <a href="tambah.php" class="nav-item"><i class="bi bi-plus-circle"></i> 添加数据</a>
    <a href="data.php" class="nav-item"><i class="bi bi-table"></i> 失业保障数据</a>
    <a href="mapping.php" class="nav-item"><i class="bi bi-grid-3x3-gap"></i> Excel映射</a>
    <div class="divider"></div>
    <a href="export_rekap.php" class="nav-item"><i class="bi bi-file-spreadsheet"></i> 导出汇总</a>
    <a href="../index.php" target="_blank" class="nav-item"><i class="bi bi-globe2"></i> 公共页面</a>
    <div class="divider"></div>
    <a href="../auth/logout.php" class="nav-item" style="color:rgba(239,68,68,.7)!important"><i class="bi bi-box-arrow-right"></i> 退出登录</a>
</div>

<div class="topbar">
    <button class="btn btn-glass" onclick="切换侧边栏()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">控制面板</span>
    <span></span>
</div>

<div class="content">

    <div class="glass-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="mb-1 fw-bold" style="color:#fff">控制面板</h3>
                <div class="small-muted">欢迎, <b style="color:rgba(255,255,255,.8)"><?= htmlspecialchars($_SESSION['nama'] ?? '管理员'); ?></b></div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="tambah.php" class="btn-glass success"><i class="bi bi-plus-circle"></i> 添加</a>
                <a href="data.php" class="btn-glass primary"><i class="bi bi-table"></i> 数据</a>
                <a href="mapping.php" class="btn-glass"><i class="bi bi-grid-3x3-gap"></i> 映射</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="stat-card d-flex align-items-center gap-3">
                <div class="stat-icon blue"><i class="bi bi-people"></i></div>
                <div>
                    <div class="small-muted">总数据量</div>
                    <h2 class="mb-0 fw-bold" style="color:#fff"><?= (int)$总数据['total']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="stat-card d-flex align-items-center gap-3">
                <div class="stat-icon green"><i class="bi bi-diagram-3"></i></div>
                <div>
                    <div class="small-muted">已保存映射</div>
                    <h2 class="mb-0 fw-bold" style="color:#fff"><?= (int)$总映射['total']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="stat-card d-flex align-items-center gap-3">
                <div class="stat-icon amber"><i class="bi bi-file-earmark-excel"></i></div>
                <div>
                    <div class="small-muted">Excel模板</div>
                    <h4 class="mb-0 fw-bold"><span class="badge-soft <?= $模板状态 === '有效' ? 'green' : '' ?>"><?= $模板状态; ?></span></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="glass-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold" style="color:#fff">最新数据</h5>
                        <div class="small-muted">最近5条裁员日期最新数据</div>
                    </div>
                    <a href="data.php" class="btn-glass primary btn-sm py-1 px-3">查看全部</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>序号</th>
                                    <th>登记号</th>
                                    <th>员工</th>
                                    <th>公司</th>
                                    <th>裁员日期</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($最新数据 && mysqli_num_rows($最新数据) > 0): ?>
                                <?php $序号 = 1; ?>
                                <?php while ($行数据 = mysqli_fetch_assoc($最新数据)): ?>
                                    <tr>
                                        <td><?= $序号++; ?></td>
                                        <td><?= htmlspecialchars($行数据['nomor_register']); ?></td>
                                        <td><?= htmlspecialchars($行数据['nama_pekerja']); ?></td>
                                        <td><?= htmlspecialchars($行数据['nama_perusahaan']); ?></td>
                                        <td><?= !empty($行数据['tanggal_phk']) ? date('d-m-Y', strtotime($行数据['tanggal_phk'])) : '-'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 small-muted">暂无数据.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass-card mb-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3" style="color:#fff"><i class="bi bi-link-45deg me-2"></i>快速访问</h5>
                    <div class="d-flex flex-column gap-2">
                        <a href="tambah.php" class="quick-link"><i class="bi bi-plus-circle" style="color:#34d399"></i> 添加新数据</a>
                        <a href="data.php" class="quick-link"><i class="bi bi-table" style="color:#60a5fa"></i> 管理数据</a>
                        <a href="mapping.php" class="quick-link"><i class="bi bi-grid-3x3-gap" style="color:#fbbf24"></i> 设置Excel映射</a>
                        <a href="export_rekap.php" class="quick-link"><i class="bi bi-file-spreadsheet" style="color:#a78bfa"></i> 导出汇总</a>
                    </div>
                </div>
            </div>
            <div class="glass-card">
                <div class="card-body">
                    <h5 class="fw-bold mb-2" style="color:#fff"><i class="bi bi-info-circle me-2"></i>备注</h5>
                    <ul class="mb-0 small-muted" style="line-height:1.8">
                        <li>在数据页面按条导出.</li>
                        <li>映射用于将数据放入模板.</li>
                        <li>有效模板保存在templates文件夹中.</li>
                    </ul>
                </div>
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
