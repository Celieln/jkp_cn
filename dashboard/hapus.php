<?php

require '../config/database.php';
require '../auth/cek_login.php';

$编号=(int)$_GET['id'];

mysqli_query(
$连接,
"DELETE FROM jkp
WHERE id='$编号'"
);

header("location:data.php");
exit;
