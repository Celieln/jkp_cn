<?php

require '../config/database.php';
require '../auth/cek_login.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$查询 = mysqli_query($连接, "SELECT * FROM jkp ORDER BY nomor_register ASC");

$电子表格 = new Spreadsheet();
$工作表 = $电子表格->getActiveSheet();

$工作表->setTitle('汇总数据');

$工作表->mergeCells('A1:I1');
$工作表->setCellValue('A1', '失业保障数据汇总');
$工作表->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$工作表->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$工作表->getRowDimension(1)->setRowHeight(35);

$工作表->mergeCells('A2:I2');
$工作表->setCellValue('A2', '导出日期: ' . date('d-m-Y H:i'));
$工作表->getStyle('A2')->getFont()->setItalic(true)->setSize(11);
$工作表->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$表头 = [
    'A' => '序号',
    'B' => '登记编号',
    'C' => '公司名称',
    'D' => '员工姓名',
    'E' => '身份证号',
    'F' => '裁员原因',
    'G' => '裁员日期',
    'H' => '解雇信函编号',
    'I' => '解雇信函日期',
];

$表头行 = 4;
foreach ($表头 as $列 => $标签) {
    $工作表->setCellValue($列 . $表头行, $标签);
}
$工作表->getStyle('A4:I4')->getFont()->setBold(true)->setSize(11);
$工作表->getStyle('A4:I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$工作表->getStyle('A4:I4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$工作表->getStyle('A4:I4')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF0D6EFD');
$工作表->getStyle('A4:I4')->getFont()->getColor()->setARGB('FFFFFFFF');
$工作表->getRowDimension($表头行)->setRowHeight(22);

$边框样式 = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF999999'],
        ],
    ],
];

$序号 = 1;
$行 = 5;
while ($数据 = mysqli_fetch_assoc($查询)) {
    $工作表->setCellValue('A' . $行, $序号);
    $工作表->setCellValue('B' . $行, $数据['nomor_register']);
    $工作表->setCellValue('C' . $行, $数据['nama_perusahaan']);
    $工作表->setCellValue('D' . $行, $数据['nama_pekerja']);
    $工作表->setCellValue('E' . $行, $数据['nik']);
    $工作表->setCellValue('F' . $行, $数据['alasan_phk']);
    $工作表->setCellValue('G' . $行, !empty($数据['tanggal_phk']) ? date('d-m-Y', strtotime($数据['tanggal_phk'])) : '');
    $工作表->setCellValue('H' . $行, $数据['nomor_surat_lphk']);
    $工作表->setCellValue('I' . $行, !empty($数据['tanggal_surat_lphk']) ? date('d-m-Y', strtotime($数据['tanggal_surat_lphk'])) : '');

    $工作表->getStyle('A' . $行 . ':I' . $行)->applyFromArray($边框样式);

    if ($序号 % 2 == 0) {
        $工作表->getStyle('A' . $行 . ':I' . $行)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF2F7FF');
    }

    $行++;
    $序号++;
}

$最后行 = $行 - 1;
$工作表->getStyle('A4:I' . $最后行)->applyFromArray($边框样式);
$工作表->getStyle('A4:I4')->applyFromArray($边框样式);

$工作表->getColumnDimension('A')->setWidth(6);
$工作表->getColumnDimension('B')->setWidth(22);
$工作表->getColumnDimension('C')->setWidth(28);
$工作表->getColumnDimension('D')->setWidth(28);
$工作表->getColumnDimension('E')->setWidth(20);
$工作表->getColumnDimension('F')->setWidth(35);
$工作表->getColumnDimension('G')->setWidth(16);
$工作表->getColumnDimension('H')->setWidth(22);
$工作表->getColumnDimension('I')->setWidth(20);

$工作表->getStyle('A5:I' . $最后行)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
$工作表->getStyle('A5:A' . $最后行)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$工作表->getStyle('G5:G' . $最后行)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$工作表->getStyle('I5:I' . $最后行)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="汇总数据_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$写入器 = new Xlsx($电子表格);
$写入器->save('php://output');
exit;
