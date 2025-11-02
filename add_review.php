<?php
include "connect.php"; // Kết nối database

// Nhận dữ liệu từ App
$user_id = $_POST['user_id'];
$sanpham_id = $_POST['sanpham_id'];
$donhang_id = $_POST['donhang_id'];
$sao = $_POST['sao'];
$binhluan = $_POST['binhluan'];

// Kiểm tra dữ liệu đầu vào
if (empty($user_id) || empty($sanpham_id) || empty($donhang_id) || empty($sao)) {
    $arr = [
        'success' => false,
        'message' => "Thiếu thông tin cần thiết"
    ];
} else {
    // Kiểm tra xem người dùng đã đánh giá sản phẩm này trong đơn hàng này chưa
    $query_check = "SELECT * FROM `danhgia` WHERE `user_id` = '$user_id' AND `sanpham_id` = '$sanpham_id' AND `donhang_id` = '$donhang_id'";
    $data_check = mysqli_query($conn, $query_check);
    
    if (mysqli_num_rows($data_check) > 0) {
        $arr = [
            'success' => false,
            'message' => "Bạn đã đánh giá sản phẩm này rồi"
        ];
    } else {
        // Thêm đánh giá mới
        $query = "INSERT INTO `danhgia` (`user_id`, `sanpham_id`, `donhang_id`, `sao`, `binhluan`) VALUES ('$user_id', '$sanpham_id', '$donhang_id', '$sao', '$binhluan')";
        $data = mysqli_query($conn, $query);

        if ($data) {
            $arr = [
                'success' => true,
                'message' => "Cảm ơn bạn đã gửi đánh giá"
            ];
        } else {
            $arr = [
                'success' => false,
                'message' => "Gửi đánh giá thất bại"
            ];
        }
    }
}

print_r(json_encode($arr));
?>
