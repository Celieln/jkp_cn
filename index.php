<?php

require 'config/database.php';

$搜索 = isset($_GET['搜索']) ? trim(preg_replace('/\s+/', ' ', $_GET['搜索'])) : '';
$排序 = isset($_GET['排序']) && $_GET['排序'] === '降序' ? 'DESC' : 'ASC';

if ($搜索 !== '') {
    $安全 = str_replace(['%', '_'], ['\%', '\_'], $搜索);
    $模糊 = "%$安全%";
    $语句 = $连接->prepare("
        SELECT * FROM jkp
        WHERE nama_pekerja LIKE ? OR nama_perusahaan LIKE ?
        ORDER BY tanggal_phk $排序
    ");
    $语句->bind_param("ss", $模糊, $模糊);
    $语句->execute();
    $查询 = $语句->get_result();
} else {
    $查询 = mysqli_query($连接, "SELECT * FROM jkp ORDER BY tanggal_phk $排序");
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>失业保障系统</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{background:linear-gradient(135deg,#f0f4ff,#e8edf5,#fafbfe);min-height:100vh;font-family:system-ui,-apple-system,"Segoe UI",sans-serif}
    .hero{background:linear-gradient(135deg,#0a2540,#1a3a6b,#2563eb);color:#fff;border-radius:24px;padding:28px 32px;box-shadow:0 20px 50px rgba(10,37,64,.15);margin-bottom:24px;text-align:center}
    .hero h1{font-weight:800;letter-spacing:4px;font-size:36px;margin:0}
    .hero p{color:rgba(255,255,255,.5);font-size:13px;margin-top:2px;letter-spacing:2px}
    .card-table{border:none;border-radius:20px;box-shadow:0 12px 40px rgba(16,24,40,.06);overflow:hidden}
    .card-table .card-header{background:#fff;border-bottom:1px solid #eef2f6;padding:20px 24px}
    .search-box{border-radius:14px;border:1px solid #e2e8f0;padding:12px 18px;font-size:14px;transition:.2s;background:#fff;width:100%}
    .search-box:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1);outline:none}
    .btn-cari{background:#2563eb;color:#fff;border:none;border-radius:14px;padding:12px 24px;font-weight:600;transition:.2s;width:100%;font-size:14px}
    .btn-cari:hover{background:#1d4ed8;transform:translateY(-1px)}
    .table thead th{background:#f8fafc;color:#475569;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e2e8f0;padding:14px 16px}
    .table td{padding:14px 16px;vertical-align:middle;border-top:1px solid #f1f5f9;color:#334155;font-size:14px}
    .table tbody tr:hover{background:#f8fafc}
    .badge-status{background:#eef2ff;color:#4338ca;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600}
    .footer-text{text-align:center;margin-top:24px;color:#94a3b8;font-size:13px;padding-bottom:20px}
    @media(max-width:576px){
        .hero{padding:20px 16px;border-radius:16px}
        .hero h1{font-size:24px}
        .table td,.table th{padding:10px 8px;font-size:13px}
    }
</style>
</head>
<body>

<div class="container py-4" style="max-width:960px">
    <div class="hero">
        <h1>失业保障</h1>
        <p>裁员记录数据</p>
    </div>

    <div class="card card-table">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h5 class="mb-0 fw-bold" style="color:#0f172a"><i class="bi bi-list-ul me-2"></i>失业保障记录数据</h5>
                <div class="d-flex gap-2">
                    <a href="?排序=升序" class="btn btn-sm <?= $排序 === 'ASC' ? 'btn-primary' : 'btn-outline-secondary' ?>" style="border-radius:10px"><i class="bi bi-sort-alpha-down"></i></a>
                    <a href="?排序=降序" class="btn btn-sm <?= $排序 === 'DESC' ? 'btn-primary' : 'btn-outline-secondary' ?>" style="border-radius:10px"><i class="bi bi-sort-alpha-down-alt"></i></a>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form method="GET" class="mb-4">
                <div class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="搜索" value="<?= htmlspecialchars($搜索); ?>" placeholder="搜索员工或公司名称..." class="form-control search-box">
                    </div>
                    <div class="col-md-2">
                        <button class="btn-cari"><i class="bi bi-search me-1"></i> 搜索</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>序号</th>
                        <th>员工</th>
                        <th>公司</th>
                        <th>裁员原因</th>
                        <th>
                            <a href="?排序=<?= $排序 === 'ASC' ? '降序' : '升序' ?>" style="color:#475569;text-decoration:none;">
                                裁员日期 <?= $排序 === 'ASC' ? '▲' : '▼' ?>
                            </a>
                        </th>
                        <th>信函编号</th>
                        <th>信函日期</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $序号=1; while($数据=mysqli_fetch_assoc($查询)): ?>
                    <tr>
                        <td><span class="badge-status"><?= $序号++; ?></span></td>
                        <td><b><?= htmlspecialchars($数据['nama_pekerja']); ?></b></td>
                        <td><?= htmlspecialchars($数据['nama_perusahaan']); ?></td>
                        <td><?= htmlspecialchars($数据['alasan_phk']); ?></td>
                        <td><?= !empty($数据['tanggal_phk']) ? date('d-m-Y', strtotime($数据['tanggal_phk'])) : '-'; ?></td>
                        <td><?= htmlspecialchars($数据['nomor_surat_lphk']); ?></td>
                        <td><?= !empty($数据['tanggal_surat_lphk']) ? date('d-m-Y', strtotime($数据['tanggal_surat_lphk'])) : '-'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($查询) == 0): ?>
                    <tr><td colspan="7" class="text-center py-4" style="color:#94a3b8">未找到数据.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="footer-text">
        &copy; <?= date('Y'); ?> 失业保障系统
    </div>
</div>

</body>
</html>
