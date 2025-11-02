<?php
include "connect.php"; // Kết nối đến database
header('Content-Type: application/json; charset=utf-8');

// Nhận dữ liệu từ POST request
$user_id = $_POST['user_id'] ?? 0;
$sanpham_id = $_POST['sanpham_id'] ?? 0;
$donhang_id = $_POST['donhang_id'] ?? 0;
$sao = $_POST['sao'] ?? 0;
$binhluan = $_POST['binhluan'] ?? '';

// --- VALIDATE DỮ LIỆU ---
if ($user_id == 0 || $sanpham_id == 0 || $donhang_id == 0 || $sao == 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc.']);
    exit();
}

// --- KIỂM TRA XEM USER ĐÃ ĐÁNH GIÁ SẢN PHẨM NÀY TRONG ĐƠN HÀNG NÀY CHƯA ---
// Điều này ngăn việc một người dùng gửi nhiều đánh giá cho cùng 1 sản phẩm trong 1 đơn hàng
$check_query = "SELECT * FROM `danhgia` WHERE `user_id` = ? AND `sanpham_id` = ? AND `donhang_id` = ?";
$stmt_check = $conn->prepare($check_query);
$stmt_check->bind_param("iii", $user_id, $sanpham_id, $donhang_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Nếu đã tồn tại -> Cập nhật đánh giá cũ
    $update_query = "UPDATE `danhgia` SET `sao` = ?, `binhluan` = ?, `ngaydanhgia` = NOW() WHERE `user_id` = ? AND `sanpham_id` = ? AND `donhang_id` = ?";
    $stmt_update = $conn->prepare($update_query);
    $stmt_update->bind_param("isiii", $sao, $binhluan, $user_id, $sanpham_id, $donhang_id);
    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật đánh giá thành công.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật đánh giá.']);
    }
    $stmt_update->close();
} else {
    // Nếu chưa tồn tại -> Thêm đánh giá mới
    $insert_query = "INSERT INTO `danhgia` (`user_id`, `sanpham_id`, `donhang_id`, `sao`, `binhluan`) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($insert_query);
    $stmt_insert->bind_param("iiiis", $user_id, $sanpham_id, $donhang_id, $sao, $binhluan);
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Gửi đánh giá thành công.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi gửi đánh giá.']);
    }
    $stmt_insert->close();
}

$stmt_check->close();
$conn->close();
?>
