<?php

require '../config/database.php';
require '../auth/cek_login.php';

$登记编号=$_POST['nomor_register'];
$公司名称=$_POST['nama_perusahaan'];
$员工姓名=$_POST['nama_pekerja'];
$身份证号=$_POST['nik'];
$裁员原因=$_POST['alasan_phk'];
$裁员日期=$_POST['tanggal_phk'];
$解雇信函编号=$_POST['nomor_surat_lphk'];
$解雇信函日期=$_POST['tanggal_surat_lphk'];

$语句=$连接->prepare(
"INSERT INTO jkp(
nomor_register,
nama_perusahaan,
nama_pekerja,
nik,
alasan_phk,
tanggal_phk,
nomor_surat_lphk,
tanggal_surat_lphk
)
VALUES(
?,?,?,?,?,?,?,?
)"
);

$语句->bind_param(
"ssssssss",
$登记编号,
$公司名称,
$员工姓名,
$身份证号,
$裁员原因,
$裁员日期,
$解雇信函编号,
$解雇信函日期
);

$语句->execute();

header("location:data.php");
exit;
?>
