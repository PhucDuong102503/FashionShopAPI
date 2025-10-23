<?php
include 'connect.php';
header('Content-Type: application/json; charset=utf-8');

// đọc input (hỗ trợ form x-www-form-urlencoded và JSON)
$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$request = array_merge($_GET, $_POST, is_array($json) ? $json : []);

// chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phải dùng POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

// đổi tên biến cho dễ hiểu: user_input có thể là email hoặc tên đăng nhập
$user_input = isset($request['tendangnhap']) ? trim($request['tendangnhap']) : '';
$matkhau    = isset($request['matkhau']) ? trim($request['matkhau']) : '';
$debug      = isset($request['debug']) ? (bool)$request['debug'] : false;

if ($user_input === '' || $matkhau === '') {
    echo json_encode(['success' => false, 'message' => 'Thiếu tài khoản hoặc mật khẩu'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Câu truy vấn: cho phép tìm theo tên đăng nhập hoặc email
$sql = "SELECT id, tendangnhap, matkhau, hoten, sodienthoai, email, diachi, role_id 
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

$stored = $user['matkhau'] ?? '';

// Kiểm tra mật khẩu
$ok = false;
if (!empty($stored) && password_verify($matkhau, $stored)) {
    $ok = true;
} elseif ($stored === $matkhau && $stored !== '') {
    // fallback cho mật khẩu lưu plaintext (tự động cập nhật thành hash)
    $ok = true;
    $newhash = password_hash($matkhau, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE [user] SET matkhau = ? WHERE id = ?");
    if ($upd) {
        $upd->bind_param('si', $newhash, $user['id']);
        $upd->execute();
        $upd->close();
    }
}

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu không đúng'], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}

// Trả về thông tin user (ẩn mật khẩu)
$response_user = [
    'id' => (string)$user['id'],
    'tendangnhap' => (string)$user['tendangnhap'],
    'hoten' => (string)$user['hoten'],
    'sodienthoai' => (string)$user['sodienthoai'],
    'email' => (string)$user['email'],
    'diachi' => (string)$user['diachi'],
    'role_id' => (string)$user['role_id']
];

echo json_encode([
    'success' => true,
    'message' => 'Đăng nhập thành công',
    'result'  => [$response_user]
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
