<?php
include 'connect.php';
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$otp = trim($data['otp'] ?? '');
$new_password = trim($data['new_password'] ?? '');

if (empty($email) || empty($otp) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu']);
    exit;
}

// Lấy token và thời hạn từ DB
$stmt = $conn->prepare("SELECT reset_token, reset_token_expire FROM `user` WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Email không tồn tại']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if (empty($user['reset_token']) || empty($user['reset_token_expire'])) {
    echo json_encode(['success' => false, 'message' => 'Không có yêu cầu đặt lại mật khẩu']);
    exit;
}

// kiểm tra thời gian hết hạn
if (strtotime($user['reset_token_expire']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Mã OTP đã hết hạn']);
    exit;
}

// kiểm tra OTP có đúng không
if (!password_verify($otp, $user['reset_token'])) {
    echo json_encode(['success' => false, 'message' => 'Mã OTP không đúng']);
    exit;
}

// mã đúng → cập nhật mật khẩu mới (hash)
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE `user` SET matkhau = ?, reset_token = NULL, reset_token_expire = NULL WHERE email = ?");
$update->bind_param("ss", $new_password_hash, $email);
$update->execute();
$update->close();

echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
$conn->close();
