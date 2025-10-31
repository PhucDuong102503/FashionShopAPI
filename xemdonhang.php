<?php
include "connect.php";
header('Content-Type: application/json; charset=utf-8');

$user_id = $_POST['user_id'] ?? 0;
// Thêm tham số trạng thái để biết cần lấy đơn hàng loại nào
$trangthai = $_POST['trangthai'] ?? '';

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
    exit();
}

if (empty($trangthai)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu trạng thái']);
    exit();
}

$query = "SELECT * FROM `donhang` WHERE `user_id` = ? AND `trangthai` = ? ORDER BY `ngaydathang` DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $trangthai);
$stmt->execute();
$result = $stmt->get_result();

$mang_donhang = [];
while ($row = $result->fetch_assoc()) {
    // Lấy chi tiết đơn hàng
    $query_chitiet = "SELECT T2.tensanpham, T2.hinhanhsanpham, T1.soluong, T1.gia, T3.tensize 
                    FROM chitietdonhang AS T1 
                    INNER JOIN sanpham AS T2 ON T1.sanpham_id = T2.id 
                    LEFT JOIN size AS T3 ON T1.size_id = T3.id 
                    WHERE T1.donhang_id = ?";
    $stmt_chitiet = $conn->prepare($query_chitiet);
    $stmt_chitiet->bind_param("i", $row['id']);
    $stmt_chitiet->execute();
    $result_chitiet = $stmt_chitiet->get_result();

    $mang_chitiet = [];
    while ($row_chitiet = $result_chitiet->fetch_assoc()) {
        $mang_chitiet[] = $row_chitiet;
    }

    $row['items'] = $mang_chitiet;
    $mang_donhang[] = $row;
    $stmt_chitiet->close();
}

if (!empty($mang_donhang)) {
    echo json_encode(['success' => true, 'result' => $mang_donhang]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không có đơn hàng nào']);
}

$stmt->close();
$conn->close();
