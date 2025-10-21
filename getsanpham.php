<?php
include 'connect.php';

header('Content-Type: application/json; charset=utf-8');

// nhận param từ GET hoặc POST
$request = array_merge($_GET, $_POST);

// phân trang
$page = isset($request['page']) ? (int)$request['page'] : 1;
if ($page < 1) $page = 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

// lấy id loại sản phẩm
if (isset($request['idloaisanpham'])) {
    $cat = (int)$request['idloaisanpham'];
} else {
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số idloaisanpham', 'result' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

// truy vấn với offset, limit
$cat = (int)$cat;
$offset = (int)$offset;
$limit = (int)$per_page;

$query = "SELECT id, tensanpham, giasanpham, hinhanhsanpham, motasanpham, idloaisanpham
          FROM sanpham
          WHERE idloaisanpham = $cat
          LIMIT $offset, $limit";

$res = mysqli_query($conn, $query);
if (!$res) {
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: '.mysqli_error($conn), 'result' => []], JSON_UNESCAPED_UNICODE);
    mysqli_close($conn);
    exit;
}

$items = [];
while ($row = mysqli_fetch_assoc($res)) {
    $items[] = [
        'id' => (string)$row['id'],
        'tensanpham' => (string)$row['tensanpham'],
        'giasanpham' => (string)$row['giasanpham'],
        'hinhanhsanpham' => (string)$row['hinhanhsanpham'],
        'motasanpham' => (string)$row['motasanpham'],
        'idloaisanpham' => (string)$row['idloaisanpham']
    ];
}

$response = [
    'success' => !empty($items),
    'message' => !empty($items) ? 'Lấy sản phẩm mới nhất thành công' : 'Không có sản phẩm',
    'current_page' => $page,
    'per_page' => $per_page,
    'result' => $items
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);

mysqli_free_result($res);
mysqli_close($conn);
?>
