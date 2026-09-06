<?php
require '../config/database.php';
require '../auth/cek_login.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$字段 = [
    'nomor_register'     => '登记编号',
    'nama_perusahaan'    => '公司名称',
    'nama_pekerja'       => '员工姓名',
    'nik'                => '身份证号',
    'alasan_phk'         => '裁员原因',
    'tanggal_phk'        => '裁员日期',
    'nomor_surat_lphk'   => '解雇信函编号',
    'tanggal_surat_lphk' => '解雇信函日期',
];

$模板目录 = realpath(__DIR__ . '/../templates');
$模板路径 = $模板目录 . DIRECTORY_SEPARATOR . 'template_jkp.xlsx';

mysqli_query($连接, "INSERT IGNORE INTO mapping_setting (id) VALUES (1)");
$设置 = mysqli_fetch_assoc(mysqli_query($连接, "SELECT * FROM mapping_setting WHERE id=1 LIMIT 1"));

$已锁定 = ($设置 && !empty($设置['is_locked']));
$消息 = '';
$错误   = '';

$当前页面 = 'mapping';

function 是否有效单元格($单元格) {
    return preg_match('/^[A-Z]{1,3}\d+$/i', strtoupper(trim($单元格)));
}

function 清理单元格($单元格) {
    return strtoupper(trim($单元格));
}

function 获取主单元格($单元格, $合并单元格) {
    foreach ($合并单元格 as $范围) {
        list($开始, $结束) = explode(':', $范围);
        list($开始列, $开始行) = Coordinate::coordinateFromString($开始);
        list($结束列, $结束行) = Coordinate::coordinateFromString($结束);
        list($当前列, $当前行) = Coordinate::coordinateFromString($单元格);
        if ($当前行 >= $开始行 && $当前行 <= $结束行 && Coordinate::columnIndexFromString($当前列) >= Coordinate::columnIndexFromString($开始列) && Coordinate::columnIndexFromString($当前列) <= Coordinate::columnIndexFromString($结束列)) {
            return $开始;
        }
    }
    return $单元格;
}

if (isset($_FILES['模板文件']) && $_FILES['模板文件']['error'] === UPLOAD_ERR_OK) {
    if ($已锁定) {
        $错误 = '映射已锁定。请解锁或删除设置以更改位置。';
    } else {
        $扩展名 = strtolower(pathinfo($_FILES['模板文件']['name'], PATHINFO_EXTENSION));
        if ($扩展名 !== 'xlsx') {
            $错误 = '仅允许.xlsx文件。';
        } else {
            if (move_uploaded_file($_FILES['模板文件']['tmp_name'], $模板路径)) {
                $消息 = '模板上传成功。';
            } else {
                $错误 = '保存模板文件失败。';
            }
        }
    }
}

if (isset($_POST['保存映射'])) {
    if ($已锁定) {
        $错误 = '映射已锁定。请解锁或删除设置以更改位置。';
    } else {
        $字段名 = trim($_POST['字段名'] ?? '');
        $excel单元格 = 清理单元格($_POST['excel单元格'] ?? '');
        if (!array_key_exists($字段名, $字段)) {
            $错误 = '所选字段无效。';
        } elseif (!是否有效单元格($excel单元格)) {
            $错误 = 'Excel单元格无效。例如: B7, D12, H25。';
        } elseif (!file_exists($模板路径)) {
            $错误 = '模板尚未上传。';
        } else {
            try {
                $电子表格 = IOFactory::load($模板路径);
                $工作表 = $电子表格->getActiveSheet();
                $excel单元格 = 获取主单元格($excel单元格, $工作表->getMergeCells());
                $检查 = $连接->prepare("SELECT id FROM mapping_excel WHERE field_name = ? LIMIT 1");
                $检查->bind_param('s', $字段名);
                $检查->execute();
                $检查结果 = $检查->get_result();
                if ($检查结果 && $检查结果->num_rows > 0) {
                    $更新 = $连接->prepare("UPDATE mapping_excel SET excel_cell = ? WHERE field_name = ?");
                    $更新->bind_param('ss', $excel单元格, $字段名);
                    $更新->execute();
                } else {
                    $插入 = $连接->prepare("INSERT INTO mapping_excel (field_name, excel_cell) VALUES (?, ?)");
                    $插入->bind_param('ss', $字段名, $excel单元格);
                    $插入->execute();
                }
                $消息 = '映射已保存。"' . $字段[$字段名] . '" 已设置到单元格 ' . $excel单元格 . '。';
            } catch (Throwable $e) {
                $错误 = '保存映射失败: ' . $e->getMessage();
            }
        }
    }
}

if (isset($_POST['锁定设置'])) {
    if (file_exists($模板路径)) {
        mysqli_query($连接, "UPDATE mapping_setting SET is_locked = 1, locked_at = NOW() WHERE id = 1");
        $已锁定 = true;
        $消息 = '映射已锁定。';
    } else {
        $错误 = '模板不存在。请先上传模板。';
    }
}

if (isset($_POST['解锁设置'])) {
    mysqli_query($连接, "UPDATE mapping_setting SET is_locked = 0, locked_at = NULL WHERE id = 1");
    $已锁定 = false;
    $消息 = '映射已解锁。';
}

if (isset($_POST['删除单个映射'])) {
    $删除字段 = trim($_POST['删除字段'] ?? '');
    if ($删除字段 !== '') {
        $语句 = $连接->prepare("DELETE FROM mapping_excel WHERE field_name = ?");
        $语句->bind_param('s', $删除字段);
        $语句->execute();
        $消息 = '映射 "' . htmlspecialchars($字段[$删除字段] ?? $删除字段) . '" 已成功删除。';
    }
}

if (isset($_POST['删除设置'])) {
    mysqli_query($连接, "DELETE FROM mapping_excel");
    mysqli_query($连接, "UPDATE mapping_setting SET is_locked = 0, locked_at = NULL WHERE id = 1");
    $已锁定 = false;
    $消息 = '所有映射已删除。';
}

$映射 = [];
$映射编号 = [];
$映射查询 = mysqli_query($连接, "SELECT id, field_name, excel_cell FROM mapping_excel");
if ($映射查询) {
    while ($行数据 = mysqli_fetch_assoc($映射查询)) {
        $映射[$行数据['field_name']] = strtoupper(trim($行数据['excel_cell']));
        $映射编号[$行数据['field_name']] = $行数据['id'];
    }
}

$预览就绪 = file_exists($模板路径);

$工作表 = null;
$最大行 = 0;
$最大列索引 = 0;
$合并起始 = [];
$覆盖单元格 = [];

if ($预览就绪) {
    try {
        $电子表格 = IOFactory::load($模板路径);
        $工作表 = $电子表格->getActiveSheet();
        $最大行 = (int) $工作表->getHighestRow();
        $最大列索引 = Coordinate::columnIndexFromString($工作表->getHighestColumn());
        foreach ($工作表->getMergeCells() as $范围) {
            [$开始, $结束] = explode(':', $范围);
            [$开始列字母, $开始行] = Coordinate::coordinateFromString($开始);
            [$结束列字母, $结束行] = Coordinate::coordinateFromString($结束);
            $开始列 = Coordinate::columnIndexFromString($开始列字母);
            $结束列   = Coordinate::columnIndexFromString($结束列字母);
            $合并起始[$开始] = ['colspan' => $结束列 - $开始列 + 1, 'rowspan' => $结束行 - $开始行 + 1];
            for ($r = $开始行; $r <= $结束行; $r++) {
                for ($c = $开始列; $c <= $结束列; $c++) {
                    if ($r === $开始行 && $c === $开始列) continue;
                    $覆盖单元格[$c . ':' . $r] = true;
                }
            }
        }
    } catch (Throwable $e) {
        $错误 = '模板读取失败: ' . $e->getMessage();
        $预览就绪 = false;
    }
}

$单元格到字段 = [];
foreach ($映射 as $字段名 => $单元格) {
    $单元格到字段[$单元格] = $字段名;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel映射 - 失业保障系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .step-card{background:rgba(255,255,255,.06);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);border-radius:18px;height:100%;box-shadow:0 12px 30px rgba(0,0,0,.2)}
        .step-number{width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;background:#3b82f6;color:#fff;margin-right:10px;flex-shrink:0;font-size:14px}

        .form-control,.form-select{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:#fff;height:46px;border-radius:12px;padding:10px 14px}
        .form-control:focus,.form-select:focus{background:rgba(255,255,255,.12);border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.2);color:#fff}
        .form-control::placeholder{color:rgba(255,255,255,.3)}
        .form-select option{background:#0f172a;color:#fff}

        .btn-glass{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);color:#e0e7f0;border-radius:10px;padding:8px 16px;font-weight:600;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:13px}
        .btn-glass:hover{background:rgba(255,255,255,.13);color:#fff}
        .btn-glass.primary{background:rgba(59,130,246,.2);border-color:rgba(59,130,246,.25);color:#60a5fa}
        .btn-glass.primary:hover{background:rgba(59,130,246,.3)}
        .btn-glass.success{background:rgba(16,185,129,.2);border-color:rgba(16,185,129,.25);color:#34d399}
        .btn-glass.success:hover{background:rgba(16,185,129,.3)}
        .btn-glass.warning{background:rgba(245,158,11,.2);border-color:rgba(245,158,11,.25);color:#fbbf24}
        .btn-glass.warning:hover{background:rgba(245,158,11,.3)}
        .btn-glass.danger{background:rgba(239,68,68,.2);border-color:rgba(239,68,68,.25);color:#f87171}
        .btn-glass.danger:hover{background:rgba(239,68,68,.3)}

        .hero-box{background:linear-gradient(135deg,rgba(59,130,246,.2),rgba(99,102,241,.15));border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:22px 26px;backdrop-filter:blur(12px)}

        .sheet-wrap{overflow:auto;max-height:70vh;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04);border-radius:14px;padding:2px}
        .sheet-table{border-collapse:collapse;width:max-content;min-width:100%}
        .sheet-table th,.sheet-table td{border:1px solid rgba(255,255,255,.1);min-width:110px;height:46px;padding:5px 7px;vertical-align:middle;white-space:nowrap;color:#e0e7f0;font-size:13px}
        .sheet-table th{background:rgba(255,255,255,.06);position:sticky;top:0;z-index:3;text-align:center;font-weight:600;color:rgba(255,255,255,.7)}
        .row-head{background:rgba(255,255,255,.06);position:sticky;left:0;z-index:2;text-align:center;min-width:50px!important;width:50px;font-weight:600;color:rgba(255,255,255,.6)!important}
        .corner-head{position:sticky;left:0;top:0;z-index:4;background:rgba(255,255,255,.08)!important;min-width:50px!important;width:50px}
        .cell{cursor:pointer;position:relative;transition:.12s ease}
        .cell:hover{background:rgba(59,130,246,.1)}
        .cell.mapped{background:rgba(16,185,129,.15)!important}
        .cell.selected{outline:2px solid #3b82f6;outline-offset:-2px;background:rgba(59,130,246,.2)!important}
        .cell-value{display:block;font-size:12px;line-height:1.2;max-width:200px;overflow:hidden;text-overflow:ellipsis}
        .cell-code{display:block;font-size:10px;opacity:.5;margin-top:2px}

        .table{--bs-table-bg:transparent;--bs-table-color:#e0e7f0;color:var(--bs-table-color);margin-bottom:0}
        .table thead th{background:rgba(0,0,0,.25);--bs-table-color:#f1f5f9;color:var(--bs-table-color);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid rgba(255,255,255,.07)}
        .table td{background:rgba(255,255,255,.02);border-top:1px solid rgba(255,255,255,.05)}
        .table tbody tr:hover td{background:rgba(255,255,255,.06)}
        .table-bordered>:not(caption)>*{border-color:rgba(255,255,255,.08)!important}

        .small-muted{color:rgba(255,255,255,.45);font-size:13px}
        .help-text{font-size:13px;color:rgba(255,255,255,.4)}

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
            .step-card .card-body{padding:16px}
            .hero-box{padding:16px}
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
    <a href="data.php" class="nav-item"><i class="bi bi-table"></i> 失业保障数据</a>
    <a href="mapping.php" class="nav-item active"><i class="bi bi-grid-3x3-gap"></i> Excel映射</a>
    <div class="divider"></div>
    <a href="export_rekap.php" class="nav-item"><i class="bi bi-file-spreadsheet"></i> 导出汇总</a>
    <a href="../index.php" target="_blank" class="nav-item"><i class="bi bi-globe2"></i> 公共页面</a>
    <div class="divider"></div>
    <a href="../auth/logout.php" class="nav-item" style="color:rgba(239,68,68,.7)!important"><i class="bi bi-box-arrow-right"></i> 退出登录</a>
</div>

<div class="topbar">
    <button class="btn btn-glass" onclick="切换侧边栏()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">Excel映射</span>
    <span></span>
</div>

<div class="content">

    <div class="hero-box mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1 fw-bold" style="color:#fff"><i class="bi bi-grid-3x3-gap me-2"></i>Excel映射</h4>
                <div class="small-muted">将数据位置设置到Excel模板单元格中。</div>
            </div>
            <a href="index.php" class="btn-glass primary btn-sm"><i class="bi bi-speedometer2"></i> 控制面板</a>
        </div>
    </div>

    <?php if ($已锁定): ?>
        <div class="alert" style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:12px 16px;color:#f87171;font-size:14px"><i class="bi bi-lock-fill me-2"></i><b>映射已锁定。</b>请解锁或删除设置以更改位置。</div>
    <?php endif; ?>
    <?php if ($消息): ?><div class="alert" style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.2);border-radius:12px;padding:12px 16px;color:#34d399;font-size:14px"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($消息) ?></div><?php endif; ?>
    <?php if ($错误): ?><div class="alert" style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:12px 16px;color:#f87171;font-size:14px"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($错误) ?></div><?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="step-card">
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="step-number">1</div>
                        <div><h5 class="mb-1" style="color:#fff">上传模板</h5><div class="help-text">使用的Excel文件。</div></div>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="color:rgba(255,255,255,.7);font-size:13px">Excel模板文件</label>
                            <input type="file" name="模板文件" accept=".xlsx" class="form-control" <?= $已锁定 ? 'disabled' : 'required' ?>>
                        </div>
                        <button type="submit" name="upload_template" class="btn-glass primary w-100" <?= $已锁定 ? 'disabled' : '' ?>><i class="bi bi-upload"></i> 上传</button>
                    </form>
                    <hr style="border-color:rgba(255,255,255,.06);margin:16px 0">
                    <div class="small-muted mb-2">当前文件: <code style="color:rgba(255,255,255,.5)">templates/template_jkp.xlsx</code></div>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" class="d-inline">
                            <button type="submit" name="锁定设置" class="btn-glass primary btn-sm" <?= $已锁定 ? 'disabled' : '' ?>><i class="bi bi-lock"></i> 锁定</button>
                        </form>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="解锁设置" class="btn-glass warning btn-sm" <?= !$已锁定 ? 'disabled' : '' ?>><i class="bi bi-unlock"></i> 解锁</button>
                        </form>
                        <form method="POST" class="d-inline" onsubmit="return confirm('删除所有映射？')">
                            <button type="submit" name="删除设置" class="btn-glass danger btn-sm"><i class="bi bi-trash"></i> 删除</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="step-card">
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="step-number">2</div>
                        <div><h5 class="mb-1" style="color:#fff">选择数据与单元格</h5><div class="help-text">点击预览中的单元格，然后选择数据。</div></div>
                    </div>
                    <form method="POST" id="mappingForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="color:rgba(255,255,255,.7);font-size:13px">选择数据</label>
                            <select name="字段名" class="form-select" <?= $已锁定 ? 'disabled' : 'required' ?>>
                                <option value="">-- 选择数据 --</option>
                                <?php foreach ($字段 as $键 => $标签): ?>
                                    <option value="<?= htmlspecialchars($键) ?>">
                                        <?= htmlspecialchars($标签) ?>
                                        <?php if (isset($映射[$键])): ?> (在 <?= htmlspecialchars($映射[$键]) ?>)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="color:rgba(255,255,255,.7);font-size:13px">Excel单元格</label>
                            <input type="text" name="excel单元格" id="excel单元格" class="form-control" placeholder="点击预览中的单元格" <?= $已锁定 ? 'disabled' : 'required' ?>>
                        </div>
                        <button type="submit" name="保存映射" class="btn-glass success w-100" <?= $已锁定 ? 'disabled' : '' ?>><i class="bi bi-check-lg"></i> 保存映射</button>
                        <button type="button" class="btn-glass w-100 mt-2" onclick="document.getElementById('excel单元格').value='';"><i class="bi bi-x-circle"></i> 清空</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="step-card">
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="step-number">3</div>
                        <div><h5 class="mb-1" style="color:#fff">已保存映射</h5><div class="help-text">当前数据位置。</div></div>
                    </div>
                    <?php if (!empty($映射)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead><tr><th>数据</th><th>单元格</th><th width="40">操作</th></tr></thead>
                                <tbody>
                                <?php foreach ($字段 as $键 => $标签): ?>
                                    <?php if (isset($映射[$键])): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($标签) ?></td>
                                        <td><?= htmlspecialchars($映射[$键]) ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('删除映射 <?= htmlspecialchars($标签) ?>？')">
                                                <input type="hidden" name="删除字段" value="<?= htmlspecialchars($键) ?>">
                                                <button type="submit" name="删除单个映射" class="btn-glass danger py-1 px-2" style="font-size:11px" <?= $已锁定 ? 'disabled' : '' ?>>✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3 small-muted">暂无已保存的映射。</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><h5 class="mb-1" style="color:#fff">模板预览</h5><div class="help-text">点击方框选择单元格。</div></div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge" style="background:rgba(59,130,246,.2);color:#60a5fa">已选单元格</span>
                    <span class="badge" style="background:rgba(16,185,129,.2);color:#34d399">已映射</span>
                </div>
            </div>
        </div>
        <div class="card-body p-3">
            <?php if (!$预览就绪 || !$工作表): ?>
                <div class="text-center py-4 small-muted">模板不可用。请先上传Excel文件。</div>
            <?php else: ?>
                <div class="sheet-wrap">
                    <table class="sheet-table" id="sheetTable">
                        <thead>
                        <tr>
                            <th class="corner-head">#</th>
                            <?php for ($列 = 1; $列 <= $最大列索引; $列++): ?>
                                <th><?= htmlspecialchars(Coordinate::stringFromColumnIndex($列)) ?></th>
                            <?php endfor; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php for ($行 = 1; $行 <= $最大行; $行++): ?>
                            <tr>
                                <th class="row-head"><?= $行 ?></th>
                                <?php for ($列 = 1; $列 <= $最大列索引; $列++): ?>
                                    <?php
                                    $单元格 = Coordinate::stringFromColumnIndex($列) . $行;
                                    $跳过键 = $列 . ':' . $行;
                                    if (isset($覆盖单元格[$跳过键])) continue;
                                    $值 = (string) $工作表->getCell($单元格)->getFormattedValue();
                                    $值 = trim($值);
                                    $是否合并起始 = isset($合并起始[$单元格]);
                                    $colspan = $是否合并起始 ? $合并起始[$单元格]['colspan'] : 1;
                                    $rowspan = $是否合并起始 ? $合并起始[$单元格]['rowspan'] : 1;
                                    $是否映射 = isset($单元格到字段[$单元格]);
                                    $字段标签 = $是否映射 ? $字段[$单元格到字段[$单元格]] : '';
                                    ?>
                                    <td class="cell <?= $是否映射 ? 'mapped' : '' ?>" data-cell="<?= htmlspecialchars($单元格) ?>"
                                        <?= $colspan > 1 ? 'colspan="'.(int)$colspan.'"' : '' ?>
                                        <?= $rowspan > 1 ? 'rowspan="'.(int)$rowspan.'"' : '' ?>>
                                        <span class="cell-value"><?= $值 !== '' ? htmlspecialchars($值) : '&nbsp;' ?></span>
                                        <span class="cell-code"><?= htmlspecialchars($单元格) ?>
                                            <?php if ($是否映射): ?><span class="badge" style="background:rgba(16,185,129,.3);color:#34d399;font-size:10px"><?= htmlspecialchars($字段标签) ?></span><?php endif; ?>
                                        </span>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.cell').forEach(function(td){
    td.addEventListener('click', function(){
        if (document.getElementById('excel单元格').disabled) return;
        document.querySelectorAll('.cell.selected').forEach(function(el){ el.classList.remove('selected'); });
        this.classList.add('selected');
        document.getElementById('excel单元格').value = this.dataset.cell;
    });
});

function 切换侧边栏(){
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>
</body>
</html>
