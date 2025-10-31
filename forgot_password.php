<?php
include 'connect.php';
header('Content-Type: application/json; charset=utf-8');

// require PHPMailer (bạn để đúng đường dẫn thư mục PHPMailer/src)
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// đọc input JSON từ Postman
$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? trim($data['email']) : '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email']);
    exit;
}

// CHÚ Ý: dùng backtick để an toàn với tên bảng 'user'
$stmt = $conn->prepare("SELECT id, hoten, tendangnhap FROM `user` WHERE email = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare SELECT failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Email không tồn tại trong hệ thống']);
    $stmt->close();
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// tạo OTP 6 chữ số, hết hạn sau 5 phút
$otp = random_int(100000, 999999);
$expire = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// hash OTP trước khi lưu (tăng bảo mật)
$otp_hash = password_hash((string)$otp, PASSWORD_DEFAULT);

// cập nhật DB (lưu hash và expire)
$update = $conn->prepare("UPDATE `user` SET reset_token = ?, reset_token_expire = ? WHERE email = ?");
if (!$update) {
    echo json_encode(['success' => false, 'message' => 'Prepare UPDATE failed: ' . $conn->error]);
    exit;
}
$update->bind_param("sss", $otp_hash, $expire, $email);
if (!$update->execute()) {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật token: ' . $update->error]);
    $update->close();
    exit;
}
$update->close();

// gửi mail OTP bằng PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'phucdq2003@gmail.com';       // Gmail bạn dùng để gửi
    $mail->Password = 'blzf ywwm hesl kpve';        // Mật khẩu ứng dụng
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom('phucdq2003@gmail.com', 'Fashion Shop');
    $mail->addAddress($email, $user['hoten'] ?? $user['tendangnhap'] ?? '');
    $mail->Subject = 'Mã OTP đặt lại mật khẩu - Fashion Shop';
    // gửi nội dung plain text (không in token ra API)
    $mail->Body = "Xin chào " . ($user['hoten'] ?? $user['tendangnhap'] ?? '') . ",\n\n"
        . "Mã OTP đặt lại mật khẩu của bạn là: {$otp}\n"
        . "Mã có hiệu lực trong 5 phút.\n\n"
        . "Nếu bạn không yêu cầu, hãy bỏ qua email này.\n\n"
        . "Fashion Shop";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Mã OTP đã được gửi tới email của bạn.']);
} catch (Exception $e) {
    // nếu gửi mail lỗi, bạn có thể xóa token đã lưu nếu muốn:
    $clear = $conn->prepare("UPDATE `user` SET reset_token = NULL, reset_token_expire = NULL WHERE email = ?");
    if ($clear) {
        $clear->bind_param("s", $email);
        $clear->execute();
        $clear->close();
    }

    echo json_encode(['success' => false, 'message' => 'Lỗi gửi email: ' . $mail->ErrorInfo]);
}

$conn->close();
