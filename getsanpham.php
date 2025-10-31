<?php
include 'connect.php';

header('Content-Type: application/json; charset=utf-8');

// Lấy tham số từ request
$request = array_merge($_GET, $_POST);
$page = isset($request['page']) ? (int)$request['page'] : 1;
if ($page < 1) $page = 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

if (!isset($request['idloaisanpham'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số idloaisanpham', 'result' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$cat = (int)$request['idloaisanpham'];

// Truy vấn với offset và limit
$query = "SELECT id, tensanpham, giasanpham, hinhanhsanpham, motasanpham, idloaisanpham
          FROM sanpham
          WHERE idloaisanpham = $cat
          LIMIT $offset, $per_page"; // Sửa $limit thành $per_page cho nhất quán

$res = mysqli_query($conn, $query);
if (!$res) {
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . mysqli_error($conn), 'result' => []], JSON_UNESCAPED_UNICODE);
    mysqli_close($conn);
    exit;
}

// ⭐ SỬA LẠI: Tự động lấy URL gốc của server
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/FashionShop/";

$items = [];
while ($row = mysqli_fetch_assoc($res)) {
    $hinhanh = trim($row['hinhanhsanpham']);

    // ⭐ LOGIC QUAN TRỌNG: Nếu đường dẫn ảnh không phải là URL đầy đủ, hãy nối nó với URL gốc
    if (!empty($hinhanh) && !preg_match('/^https?:\/\//', $hinhanh)) {
        $hinhanh = $base_url . ltrim($hinhanh, '/');
    }

    $items[] = [
        'id' => (string)$row['id'],
        'tensanpham' => (string)$row['tensanpham'],
        'giasanpham' => (string)$row['giasanpham'],
        'hinhanhsanpham' => $hinhanh, // ✅ Dùng biến đã được xử lý
        'motasanpham' => (string)$row['motasanpham'],
        'idloaisanpham' => (string)$row['idloaisanpham']
    ];
}

$response = [
    'success' => !empty($items),
    'message' => !empty($items) ? 'Lấy sản phẩm thành công' : 'Không có sản phẩm',
    'current_page' => $page,
    'per_page' => $per_page,
    'result' => $items
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);

mysqli_free_result($res);
mysqli_close($conn);
?>
