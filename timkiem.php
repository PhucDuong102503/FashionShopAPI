<?php
include "connect.php"; // Kết nối đến cơ sở dữ liệu

// Nhận từ khóa tìm kiếm từ request POST
$search = isset($_POST['search']) ? $_POST['search'] : '';

// --- VALIDATION: Kiểm tra xem từ khóa có được gửi lên không ---
if (empty($search)) {
    $arr = [
        'success' => false,
        'message' => "Không nhận được từ khóa tìm kiếm"
    ];
    print_r(json_encode($arr));
    die();
}

$query = "SELECT * FROM `sanpham` WHERE `tensanpham` LIKE ?";
$stmt = $conn->prepare($query);
$search_term = "%" . $search . "%"; // Thêm ký tự '%' để tìm kiếm bất kỳ vị trí nào trong tên
$stmt->bind_param("s", $search_term);
$stmt->execute();
$result = $stmt->get_result();

$mangsanpham = array();
while ($row = $result->fetch_assoc()) {
    $mangsanpham[] = $row;
}

if (!empty($mangsanpham)) {
    $arr = [
        'success' => true,
        'message' => "Thành công",
        'result' => $mangsanpham
    ];
} else {
    $arr = [
        'success' => false,
        'message' => "Không tìm thấy sản phẩm nào khớp với từ khóa của bạn."
    ];
}

print_r(json_encode($arr));
$conn->close();
?>
