<?php
include "connect.php"; // Kết nối đến cơ sở dữ liệu
header('Content-Type: application/json; charset=utf-8');$arr = ['success' => false, 'message' => "Có lỗi không xác định"];

// Chỉ chấp nhận yêu cầu bằng phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $arr['message'] = 'Phương thức không được hỗ trợ';
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit();
}

// Lấy dữ liệu dạng text
$user_id = $_POST['id'] ?? 0;
$hoten = $_POST['hoten'] ?? '';
$sodienthoai = $_POST['sodienthoai'] ?? '';
$diachi = $_POST['diachi'] ?? '';

if (empty($user_id)) {
    $arr['message'] = 'Thiếu ID người dùng';
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit();
}

$set_parts = [];
$params = [];
$types = '';

if (!empty($hoten)) {
    $set_parts[] = "hoten = ?"; $params[] = $hoten; $types .= 's';
}
if (!empty($sodienthoai)) {
    $set_parts[] = "sodienthoai = ?"; $params[] = $sodienthoai; $types .= 's';
}
if (!empty($diachi)) {
    $set_parts[] = "diachi = ?"; $params[] = $diachi; $types .= 's';
}

// Xử lý upload file ảnh nếu có
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    // ⭐ Quan trọng: Thư mục upload phải khớp với cấu trúc project của bạn
    // Vì file update_profile.php nằm trong `FashionShop`, đường dẫn là `uploads/`
    $target_dir = "uploads/";

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
    $file_name = "avatar_" . $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        // Lưu đường dẫn tương đối vào DB (ví dụ: uploads/avatar_1_12345.png)
        $relative_path = $target_file;
        $set_parts[] = "hinhanh = ?";
        $params[] = $relative_path;
        $types .= 's';
    } else {
        $arr['message'] = 'Lỗi: Không thể lưu file ảnh.';
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

if (empty($set_parts)) {
    $arr['message'] = 'Không có thông tin nào để cập nhật';
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit();
}

// Ghép nối câu lệnh SQL
$sql = "UPDATE `user` SET " . implode(', ', $set_parts) . " WHERE id = ?";
$types .= 'i';
$params[] = $user_id;

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // ⭐⭐⭐ LOGIC MỚI: LẤY LẠI THÔNG TIN VÀ CHUYỂN ĐỔI URL ẢNH ⭐⭐⭐
        $query_new = "SELECT id, tendangnhap, hoten, sodienthoai, email, diachi, hinhanh, role_id FROM user WHERE id = ?";
        $stmt_new = $conn->prepare($query_new);
        $stmt_new->bind_param('i', $user_id);
        $stmt_new->execute();
        $new_user_data = $stmt_new->get_result()->fetch_assoc();
        $stmt_new->close();

        // Tự động tạo Base URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $base_url = $protocol . $host . "/FashionShop/";

        // Lấy đường dẫn ảnh từ DB
        $image_path_from_db = $new_user_data['hinhanh'];
        if (!empty($image_path_from_db) && strpos($image_path_from_db, 'http') !== 0) {
            // Nếu không phải URL đầy đủ, hãy nối nó với Base URL
            $clean_path = ltrim($image_path_from_db, '/');
            $new_user_data['hinhanh'] = $base_url . $clean_path;
        }

        $arr = [
            'success' => true,
            'message' => "Cập nhật thành công",
            'result' => [$new_user_data] // Trả về thông tin user với URL ảnh đầy đủ
        ];
    } else {
        $arr['message'] = "Cập nhật thất bại: " . $stmt->error;
    }
    $stmt->close();
} else {
    $arr['message'] = "Lỗi hệ thống: " . $conn->error;
}

echo json_encode($arr, JSON_UNESCAPED_UNICODE);
$conn->close();
?>
