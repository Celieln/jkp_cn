<?php

require '../config/database.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$模板路径 = "../templates/template_jkp.xlsx";

if(!file_exists($模板路径))
{
die("模板未找到");
}

$编号 = isset($_GET['id'])
? (int)$_GET['id']
:0;

$查询 = mysqli_query(
$连接,
"SELECT *
FROM jkp
WHERE id='$编号'
LIMIT 1"
);

if(mysqli_num_rows($查询)==0)
{
die("未找到数据");
}

$数据 = mysqli_fetch_assoc($查询);

$映射=[];

$映射查询 = mysqli_query(
$连接,
"SELECT *
FROM mapping_excel"
);

while(
$行数据=mysqli_fetch_assoc($映射查询)
)
{
$映射[
$行数据['field_name']
]
=
strtoupper(
trim(
$行数据['excel_cell']
)
);
}

if(empty($映射))
{
die(
"映射尚未创建"
);
}

$电子表格 = IOFactory::load(
$模板路径
);

$工作表 = $电子表格->getActiveSheet();

$合并单元格 = $工作表->getMergeCells();

function 获取目标主单元格(
$单元格,
$合并单元格
)
{
foreach(
$合并单元格 as $范围
)
{
list(
$开始,
$结束
)
=
explode(
":",
$范围
);

[$单元格列,$单元格行]
=
Coordinate::coordinateFromString(
$单元格
);

[$开始列,$开始行]
=
Coordinate::coordinateFromString(
$开始
);

[$结束列,$结束行]
=
Coordinate::coordinateFromString(
$结束
);

$单元格列 = Coordinate::columnIndexFromString(
$单元格列
);

$开始列 = Coordinate::columnIndexFromString(
$开始列
);

$结束列 = Coordinate::columnIndexFromString(
$结束列
);

if(
$单元格行 >= $开始行
&&
$单元格行 <= $结束行
&&
$单元格列 >= $开始列
&&
$单元格列 <= $结束列
)
{
return $开始;
}
}
return $单元格;
}

foreach(
$映射 as $字段=>$单元格
)
{
if(
!isset(
$数据[$字段]
)
)
{
continue;
}

$目标单元格 = 获取目标主单元格(
$单元格,
$合并单元格
);

$值 = $数据[$字段];

if(
$字段=="tanggal_phk"
||
$字段=="tanggal_surat_lphk"
)
{
if(
!empty(
$值
)
)
{
$值 = date(
"d-m-Y",
strtotime(
$值
)
);
}
}

$工作表->setCellValueExplicit(
$目标单元格,
(string)$值,
DataType::TYPE_STRING
);
}

$文件名 = str_replace(
" ",
"_",
$数据['nama_pekerja']
);

$文件名 .= ".xlsx";

header(
'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
'Content-Disposition: attachment; filename="'.$文件名.'"'
);

header(
'Cache-Control: max-age=0'
);

$写入器 = IOFactory::createWriter(
$电子表格,
'Xlsx'
);

$写入器->save(
'php://output'
);

exit;
?>
