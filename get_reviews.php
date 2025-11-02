<?php
include "connect.php";

$sanpham_id = $_POST['sanpham_id'];
$page = $_POST['page'];
$total = 5; // Số lượng đánh giá mỗi trang
$pos = ($page - 1) * $total; 

// Truy vấn để lấy đánh giá và thông tin người dùng
$query = "SELECT d.*, u.hoten, u.hinhanh as user_avatar 
          FROM `danhgia` d
          INNER JOIN `user` u ON d.user_id = u.id
          WHERE d.sanpham_id = '$sanpham_id' 
          ORDER BY d.ngaydanhgia DESC
          LIMIT $pos, $total";

$data = mysqli_query($conn, $query);
$result = array();

while ($row = mysqli_fetch_assoc($data)) {
    $result[] = $row;
}

if (!empty($result)) {
    $arr = [
        'success' => true,
        'message' => "Thành công",
        'result' => $result
    ];
} else {
    $arr = [
        'success' => false,
        'message' => "Chưa có đánh giá nào",
        'result' => []
    ];
}

print_r(json_encode($arr));
?>
