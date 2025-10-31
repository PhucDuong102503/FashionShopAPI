<?php
include 'connect.php';
header('Content-Type: application/json; charset=utf-8');

// Nhận dữ liệu (dạng Form)
$email = trim($_POST['email'] ?? '');
$otp = trim($_POST['otp'] ?? '');
$username = trim($_POST['tendangnhap'] ?? '');
$password = trim($_POST['matkhau'] ?? '');
$hoten = trim($_POST['hoten'] ?? '');
$sdt = trim($_POST['sodienthoai'] ?? '');
$diachi = trim($_POST['diachi'] ?? '');

if (empty($email) || empty($otp) || empty($username) || empty($password) || empty($hoten)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc.']);
    exit;
}

// 1. Kiểm tra OTP trong bảng tạm
$stmt = $conn->prepare("SELECT otp_hash, expire_at FROM `temp_users` WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ hoặc đã hết hạn.']);
    exit;
}
$temp_user = $result->fetch_assoc();
$stmt->close();

// 2. Kiểm tra OTP hết hạn chưa
if (strtotime($temp_user['expire_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Mã OTP đã hết hạn']);
    exit;
}

// 3. Kiểm tra OTP có đúng không
if (!password_verify($otp, $temp_user['otp_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Mã OTP không đúng']);
    exit;
}

// 4. Mọi thứ hợp lệ -> Tạo tài khoản chính thức trong bảng 'user'
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$role_id = 2; // role_id = 2 cho khách hàng

$stmt_insert = $conn->prepare("INSERT INTO `user` (tendangnhap, matkhau, hoten, sodienthoai, email, diachi, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt_insert->bind_param("ssssssi", $username, $password_hash, $hoten, $sdt, $email, $diachi, $role_id);

if ($stmt_insert->execute()) {
    // Xóa bản ghi tạm trong temp_users sau khi đăng ký thành công
    $stmt_del = $conn->prepare("DELETE FROM `temp_users` WHERE email = ?");
    $stmt_del->bind_param("s", $email);
    $stmt_del->execute();
    $stmt_del->close();
    echo json_encode(['success' => true, 'message' => 'Đăng ký tài khoản thành công!']);
} else {
    // Nếu có lỗi (ví dụ: unique key bị trùng do race condition), báo lỗi
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống khi tạo tài khoản: ' . $stmt_insert->error]);
}

$stmt_insert->close();
$conn->close();
