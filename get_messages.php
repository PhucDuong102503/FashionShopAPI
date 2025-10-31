<?php
include 'connect.php';
header('Content-Type: application/json; charset=utf-8');

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$admin_id = isset($_POST['admin_id']) ? (int)$_POST['admin_id'] : 0;

if ($user_id == 0 || $admin_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID người dùng hoặc admin']);
    exit;
}

$query = "SELECT id, sender_id, receiver_id, content, created_at FROM `messages` 
          WHERE (sender_id = $user_id AND receiver_id = $admin_id) 
             OR (sender_id = $admin_id AND receiver_id = $user_id) 
          ORDER BY created_at ASC";

$data = mysqli_query($conn, $query);
$result = array();
while ($row = mysqli_fetch_assoc($data)) {
    $result[] = ($row);
}

if (!empty($result)) {
    $arr = ['success' => true, 'message' => 'Thành công', 'result' => $result];
} else {
    $arr = ['success' => false, 'message' => 'Chưa có tin nhắn', 'result' => $result];
}
echo json_encode($arr);
