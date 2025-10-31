<?php

/**
 * API để nhận và lưu tin nhắn mới vào database.
 * Sau khi lưu, nó sẽ gửi thông báo FCM đến người nhận.
 */

// Nạp file kết nối database và file gửi FCM
include 'connect.php';
include_once 'fcm_sender.php'; // Sử dụng include_once để tránh lỗi nạp lại file

// Lấy dữ liệu được gửi từ app Android qua phương thức POST
$id_user_send = $_POST['id_user_send'];
$id_user_receive = $_POST['id_user_receive'];
$content = $_POST['content'];

// --- BƯỚC 1: LƯU TIN NHẮN VÀO DATABASE ---
// Câu lệnh SQL để chèn tin nhắn mới
$query = "INSERT INTO `message` (`id_user_send`, `id_user_receive`, `content`) VALUES (?, ?, ?)";

// Chuẩn bị câu lệnh để tránh SQL Injection
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iis", $id_user_send, $id_user_receive, $content);

// Thực thi câu lệnh
if (mysqli_stmt_execute($stmt)) {
    // Nếu chèn thành công
    $arr = [
        'success' => true,
        'message' => "Lưu tin nhắn thành công"
    ];

    // --- BƯỚC 2: GỬI THÔNG BÁO FCM ĐẾN NGƯỜI NHẬN ---
    try {
        // 2.1. Lấy thông tin người gửi (tên) và FCM token của người nhận
        $query_info = "
            SELECT 
                (SELECT `username` FROM `user` WHERE `id` = ?) AS sender_name,
                (SELECT `fcm_token` FROM `user` WHERE `id` = ?) AS receiver_fcm_token
        ";
        $stmt_info = mysqli_prepare($conn, $query_info);
        mysqli_stmt_bind_param($stmt_info, "ii", $id_user_send, $id_user_receive);
        mysqli_stmt_execute($stmt_info);
        $result_info = mysqli_stmt_get_result($stmt_info);
        $info_row = mysqli_fetch_assoc($result_info);

        $sender_name = $info_row['sender_name'];
        $receiver_fcm_token = $info_row['receiver_fcm_token'];

        // 2.2. Kiểm tra xem người nhận có FCM token không
        if (!empty($receiver_fcm_token)) {
            // Biến $projectId đã được định nghĩa trong file fcm_sender.php
            global $projectId;

            // Gọi hàm gửi thông báo
            sendFCM_V1($receiver_fcm_token, $sender_name, $content, $id_user_send, $projectId);

            // (Tùy chọn) Ghi log để biết đã gửi thành công
            error_log("FCM sent to user " . $id_user_receive);
        } else {
            error_log("User " . $id_user_receive . " không có FCM token.");
        }
    } catch (Exception $e) {
        // Ghi lại lỗi nếu quá trình gửi FCM thất bại
        error_log("Lỗi khi gửi FCM: " . $e->getMessage());
    }
} else {
    // Nếu chèn thất bại
    $arr = [
        'success' => false,
        'message' => "Lưu tin nhắn thất bại"
    ];
}

// Trả kết quả về cho app Android dưới dạng JSON
print_r(json_encode($arr));

// Đóng kết nối
mysqli_stmt_close($stmt);
mysqli_close($conn);
