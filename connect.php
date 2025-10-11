<?php
// Kết nối database thời trang
$host = "localhost";
$u = "root";
$p = "555888";
$db = "thoitrang";

// Tạo kết nối
$conn = mysqli_connect($host, $u, $p, $db);
mysqli_query($conn, "SET NAMES 'utf8'");


// Kiểm tra kết nối
// if (!$conn) {
//     die("Ket noi that bai: " . mysqli_connect_error());
// } else {
//     echo "Ket noi thanh cong";
// }
?>

