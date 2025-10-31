<?php
// File: api/get_admins.php (Phiên bản đã sửa theo connect.php của bạn)

include 'connect.php'; 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$response = array();
$query = "SELECT id, hoten, hinhanh FROM user WHERE role_id = 1 AND banned = 0";

// Sửa lại cú pháp truy vấn
$result = mysqli_query($conn, $query);

if ($result) {
    $admins = array();
    // Sửa lại cú pháp lấy dữ liệu
    while ($row = mysqli_fetch_assoc($result)) {
        $admins[] = $row;
    }

    if (count($admins) > 0) {
        $response['success'] = true;
        $response['message'] = "Tải danh sách admin thành công.";
        $response['admins'] = $admins;
    } else {
        $response['success'] = false;
        $response['message'] = "Không tìm thấy nhân viên hỗ trợ nào.";
    }
} else {
    $response['success'] = false;
    // Sửa lại cú pháp báo lỗi
    $response['message'] = "Lỗi khi truy vấn: " . mysqli_error($conn);
}

echo json_encode($response);
mysqli_close($conn); // Sửa lại cú pháp đóng kết nối
?>
