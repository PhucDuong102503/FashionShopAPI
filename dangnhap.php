<?php
include 'connect.php';
header('Content-Type: application/json; charset=utf-8');

// Đọc input (hỗ trợ form x-www-form-urlencoded và JSON)
$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$request = array_merge($_GET, $_POST, is_array($json) ? $json : []);

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_input = isset($request['tendangnhap']) ? trim($request['tendangnhap']) : '';
$matkhau    = isset($request['matkhau']) ? trim($request['matkhau']) : '';

if (empty($user_input) || empty($matkhau)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tài khoản và mật khẩu'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ⭐ 1. SỬA CÂU SQL: Thêm cột 'banned' vào danh sách các cột cần lấy
$sql = "SELECT id, tendangnhap, matkhau, hoten, sodienthoai, email, diachi, hinhanh, role_id, banned 
        FROM `user`
        WHERE tendangnhap = ? OR email = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống (prepare)'], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->bind_param('ss', $user_input, $user_input);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Tài khoản hoặc email không tồn tại'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// ⭐ 2. THÊM LOGIC KIỂM TRA BANNED
// Kiểm tra ngay sau khi lấy được thông tin người dùng
if (isset($user['banned']) && $user['banned'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit; // Dừng thực thi ngay lập tức
}


// --- Logic kiểm tra mật khẩu của bạn (giữ nguyên, đã rất tốt) ---
$stored_password = $user['matkhau'] ?? '';
$password_ok = false;
if (!empty($stored_password) && password_verify($matkhau, $stored_password)) {
    $password_ok = true;
} elseif ($stored_password === $matkhau && $stored_password !== '') {
    $password_ok = true;
    $newhash = password_hash($matkhau, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE `user` SET matkhau = ? WHERE id = ?");
    if ($upd) {
        $upd->bind_param('si', $newhash, $user['id']);
        $upd->execute();
        $upd->close();
    }
}

if (!$password_ok) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu không đúng'], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}
// --- Hết logic kiểm tra mật khẩu ---


// Dữ liệu trả về nếu đăng nhập thành công
$response_user = [
    'id'          => (string)$user['id'],
    'tendangnhap' => (string)$user['tendangnhap'],
    'hoten'       => (string)$user['hoten'],
    'sodienthoai' => (string)$user['sodienthoai'],
    'email'       => (string)$user['email'],
    'diachi'      => (string)$user['diachi'],
    'hinhanh'     => $user['hinhanh'],
    'role_id'     => (string)$user['role_id']
];

echo json_encode([
    'success' => true,
    'message' => 'Đăng nhập thành công',
    'result'  => [$response_user]
], JSON_UNESCAPED_UNICODE);

$conn->close();
