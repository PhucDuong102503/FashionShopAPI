<?php
include 'connect.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phải dùng POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy và trim input (đã có tendangnhap)
$tendangnhap = isset($_POST['tendangnhap']) ? trim($_POST['tendangnhap']) : '';
$hoten       = isset($_POST['hoten']) ? trim($_POST['hoten']) : '';
$matkhau     = isset($_POST['matkhau']) ? trim($_POST['matkhau']) : '';
$sodienthoai = isset($_POST['sodienthoai']) ? trim($_POST['sodienthoai']) : '';
$email       = isset($_POST['email']) ? trim($_POST['email']) : '';
$diachi      = isset($_POST['diachi']) ? trim($_POST['diachi']) : '';

// Validate required
if ($tendangnhap === '' || $hoten === '' || $matkhau === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc (tendangnhap, hoten, matkhau, email)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Password complexity: min 8 chars, at least one lowercase, one uppercase, one digit, one special char
$pw_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
if (!preg_match($pw_pattern, $matkhau)) {
    echo json_encode([
        'success' => false,
        'message' => 'Mật khẩu phải có ít nhất 8 ký tự, gồm chữ hoa, chữ thường, chữ số và ký tự đặc biệt'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra trùng tendangnhap hoặc email
$check_sql = "SELECT id FROM `user` WHERE tendangnhap = ? OR email = ? LIMIT 1";
$stmt = $conn->prepare($check_sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống (prepare check)'], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->bind_param('ss', $tendangnhap, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->bind_result($existing_id);
    $stmt->fetch();
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Tên đăng nhập hoặc email đã tồn tại'], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->close();

// Mã hóa mật khẩu
$hash = password_hash($matkhau, PASSWORD_DEFAULT);

// role_id mặc định (2 = khách hàng)
$role_id = 2;

// Insert user (tendangnhap, hoten, matkhau, sodienthoai, email, diachi, ngaytao, role_id)
$insert_sql = "INSERT INTO `user` (tendangnhap, hoten, matkhau, sodienthoai, email, diachi, ngaytao, role_id)
               VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
$stmt = $conn->prepare($insert_sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống (prepare insert)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 6 strings + 1 int => 'ssssssi'
$stmt->bind_param('ssssssi', $tendangnhap, $hoten, $hash, $sodienthoai, $email, $diachi, $role_id);
if ($stmt->execute()) {
    $new_id = $stmt->insert_id;
    echo json_encode([
        'success' => true,
        'message' => 'Đăng kí thành công',
        'user_id' => $new_id
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu dữ liệu: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
}
$stmt->close();
$conn->close();
?>