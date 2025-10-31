<?php
include "connect.php"; // Kết nối đến cơ sở dữ liệu
header('Content-Type: application/json; charset=utf-8');

$arr = ['success' => false, 'message' => "Có lỗi không xác định"];

// Chỉ chấp nhận yêu cầu bằng phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $arr['message'] = 'Phương thức không được hỗ trợ';
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit();
}

// Lấy dữ liệu dạng text từ multipart/form-data mà app gửi lên
$user_id = $_POST['id'] ?? 0;
$hoten = $_POST['hoten'] ?? '';
$sodienthoai = $_POST['sodienthoai'] ?? '';
$diachi = $_POST['diachi'] ?? '';

// ID người dùng là bắt buộc
if (empty($user_id)) {
    $arr['message'] = 'Thiếu ID người dùng';
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit();
}

$set_parts = []; // Mảng để chứa các phần của câu lệnh SET (ví dụ: "hoten = ?")
$params = [];    // Mảng để chứa các giá trị tương ứng
$types = '';     // Chuỗi để chứa các kiểu dữ liệu cho bind_param (ví dụ: "sssi")

// Xây dựng câu lệnh UPDATE một cách linh hoạt
if (!empty($hoten)) {
    $set_parts[] = "hoten = ?";
    $params[] = $hoten;
    $types .= 's'; // s = string
}
if (!empty($sodienthoai)) {
    $set_parts[] = "sodienthoai = ?";
    $params[] = $sodienthoai;
    $types .= 's';
}
if (!empty($diachi)) {
    $set_parts[] = "diachi = ?";
    $params[] = $diachi;
    $types .= 's';
}

// Xử lý upload file ảnh nếu người dùng có gửi file lên
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $target_dir = "uploads/"; // Thư mục đã tạo ở bước 1
    
    // Tạo tên file mới và duy nhất
    $file_extension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
    $file_name = time() . "_" . $user_id . "." . $file_extension;
    $target_file = $target_dir . $file_name;

    // Di chuyển file từ thư mục tạm của server vào thư mục 'uploads'
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        // Tạo URL đầy đủ của ảnh để lưu vào database
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $hinh_anh_url = $protocol . $_SERVER['HTTP_HOST'] . "/FashionShop/" . $target_file;
        
        // Thêm trường 'hinhanh' vào câu lệnh UPDATE
        $set_parts[] = "hinhanh = ?";
        $params[] = $hinh_anh_url;
        $types .= 's';
    } else {
        // Nếu di chuyển file thất bại, không cập nhật gì cả và báo lỗi
        $arr['message'] = 'Lỗi: Không thể lưu file ảnh.';
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Nếu không có thông tin nào để cập nhật thì báo lỗi
if (empty($set_parts)) {
    $arr['message'] = 'Không có thông tin nào để cập nhật';
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit();
}

// Ghép nối các phần lại thành câu lệnh SQL hoàn chỉnh
$sql = "UPDATE `user` SET " . implode(', ', $set_parts) . " WHERE id = ?";
$types .= 'i'; // i = integer cho user_id
$params[] = $user_id;

$stmt = $conn->prepare($sql);
if ($stmt) {
    // bind_param cần một danh sách các biến, dùng ... để "mở" mảng $params
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // Sau khi cập nhật thành công, lấy lại thông tin mới nhất của người dùng
        $query_new = "SELECT id, tendangnhap, hoten, sodienthoai, email, diachi, hinhanh, role_id FROM user WHERE id = ?";
        $stmt_new = $conn->prepare($query_new);
        $stmt_new->bind_param('i', $user_id);
        $stmt_new->execute();
        $new_user_data = $stmt_new->get_result()->fetch_assoc();
        $stmt_new->close();

        $arr = [
            'success' => true,
            'message' => "Cập nhật thành công",
            'result' => [$new_user_data] // Trả về thông tin user mới nhất
        ];
    } else {
        $arr['message'] = "Cập nhật thất bại";
    }
    $stmt->close();
} else {
    $arr['message'] = "Lỗi hệ thống: " . $conn->error;
}

// Trả kết quả về cho app dưới dạng JSON
echo json_encode($arr, JSON_UNESCAPED_UNICODE);
$conn->close();
?>
