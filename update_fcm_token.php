<?php
// File: api/update_fcm_token.php (Phiên bản đã sửa theo connect.php của bạn)

include 'connect.php'; // << Sửa thành tên file của bạn
header("Content-Type: application/json; charset=UTF-8");

$response = array();

if (isset($_POST['user_id']) && isset($_POST['fcm_token'])) {
    $userId = $_POST['user_id'];
    $fcmToken = $_POST['fcm_token'];

    // Câu lệnh SQL với placeholder
    $query = "UPDATE user SET fcm_token = ? WHERE id = ?";
    
    // Sửa lại cú pháp Prepared Statement
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        // "si" -> s: string (cho token), i: integer (cho id)
        mysqli_stmt_bind_param($stmt, "si", $fcmToken, $userId);
        
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = "Cập nhật token thành công.";
        } else {
            $response['success'] = false;
            $response['message'] = "Lỗi khi thực thi: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['success'] = false;
        $response['message'] = "Lỗi khi chuẩn bị câu lệnh: " . mysqli_error($conn);
    }
} else {
    $response['success'] = false;
    $response['message'] = "Thiếu thông tin user_id hoặc fcm_token.";
}

echo json_encode($response);
mysqli_close($conn); // Sửa lại cú pháp đóng kết nối
?>
