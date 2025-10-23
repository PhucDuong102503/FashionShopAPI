<?php
include 'connect.php';
header('Content-Type: application/json; charset=utf-8');

// require PHPMailer
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Nhận dữ liệu từ Android App (dạng Form)
$email = trim($_POST['email'] ?? '');
$username = trim($_POST['tendangnhap'] ?? '');

if (empty($email) || empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu email hoặc tên đăng nhập']);
    exit;
}

// 1. Kiểm tra Email hoặc Username đã tồn tại trong bảng 'user' chưa
$stmt_check = $conn->prepare("SELECT id FROM `user` WHERE email = ? OR tendangnhap = ?");
$stmt_check->bind_param("ss", $email, $username);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email hoặc Tên đăng nhập đã tồn tại']);
    $stmt_check->close();
    exit;
}
$stmt_check->close();

// 2. Tạo và lưu tạm OTP vào bảng temp_users
$otp = random_int(100000, 999999);
$expire = date('Y-m-d H:i:s', strtotime('+5 minutes'));
$otp_hash = password_hash((string)$otp, PASSWORD_DEFAULT);

// Xóa các yêu cầu cũ của cùng email đó để tránh trùng lặp
$stmt_del = $conn->prepare("DELETE FROM `temp_users` WHERE email = ?");
$stmt_del->bind_param("s", $email);
$stmt_del->execute();
$stmt_del->close();

// Lưu OTP mới vào bảng tạm
$stmt_temp = $conn->prepare("INSERT INTO `temp_users` (email, otp_hash, expire_at) VALUES (?, ?, ?)");
$stmt_temp->bind_param("sss", $email, $otp_hash, $expire);
$stmt_temp->execute();
$stmt_temp->close();

// 3. Gửi email OTP
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'phucdq2003@gmail.com'; // <<< THAY BẰNG GMAIL CỦA BẠN
    $mail->Password = 'blzf ywwm hesl kpve';   // <<< THAY BẰNG MẬT KHẨU ỨNG DỤNG CỦA BẠN
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom('phucdq2003@gmail.com', 'Fashion Shop');
    $mail->addAddress($email);
    $mail->Subject = 'Mã OTP Xác thực Đăng ký - Fashion Shop';
    $mail->Body = "Chào bạn,\n\nMã OTP để hoàn tất đăng ký tài khoản của bạn là: {$otp}\n"
        . "Mã có hiệu lực trong 5 phút.\n\n"
        . "Trân trọng,\nFashion Shop";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Mã OTP đã được gửi tới email của bạn.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi gửi email: ' . $mail->ErrorInfo]);
}

$conn->close();
?>
