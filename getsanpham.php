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
          LIMIT $offset, $per_page";

$res = mysqli_query($conn, $query);
if (!$res) {
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . mysqli_error($conn), 'result' => []], JSON_UNESCAPED_UNICODE);
    mysqli_close($conn);
    exit;
}

// ⭐ TỰ ĐỘNG LẤY URL GỐC CỦA SERVER
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . "/FashionShop/";

$items = [];
while ($row = mysqli_fetch_assoc($res)) {
    $hinhanh_path = trim($row['hinhanhsanpham']);
    $final_image_url = $hinhanh_path; // Mặc định

    // ⭐ LOGIC QUAN TRỌNG: Nếu đường dẫn ảnh không phải là URL đầy đủ, hãy nối nó với URL gốc
    if (!empty($hinhanh_path) && !preg_match('/^https?:\/\//', $hinhanh_path)) {
        $clean_path = ltrim($hinhanh_path, '/');
        $final_image_url = $base_url . $clean_path;
    }

    $items[] = [
        'id' => (string)$row['id'],
        'tensanpham' => (string)$row['tensanpham'],
        'giasanpham' => (string)$row['giasanpham'],
        'hinhanhsanpham' => $final_image_url, // ✅ Dùng biến đã được xử lý
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
