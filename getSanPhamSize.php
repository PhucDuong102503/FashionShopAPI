<?php
// Giả sử file này tên là getSanPhamSize.php
include 'connect.php'; // File kết nối database của bạn

// Đặt header để client biết đây là dữ liệu JSON
header('Content-Type: application/json; charset=utf-8');

// <<< BƯỚC 1: SỬA TỪ $_GET THÀNH $_POST >>>
// Kiểm tra xem sanpham_id có được gửi lên bằng phương thức POST không
if (!isset($_POST['sanpham_id'])) {
    // <<< BƯỚC 2: SỬA LẠI CẤU TRÚC JSON LỖI >>>
    // Trả về JSON lỗi tương thích với model Android
    $response = [
        'success' => false,
        'message' => 'Lỗi: Thiếu sanpham_id',
        'result' => []
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    die(); // Dừng script
}

// Lấy và làm sạch sanpham_id
$sanpham_id = intval($_POST['sanpham_id']);

// Câu truy vấn SQL JOIN 2 bảng: sanpham_size và size để lấy được tensize
// Câu lệnh này đã đúng, chỉ cần đảm bảo các tên cột và tên bảng chính xác
$sql = "
    SELECT 
        sz.id AS size_id,
        sz.tensize,
        sps.soluong
    FROM sanpham_size AS sps
    INNER JOIN size AS sz ON sps.size_id = sz.id
    WHERE sps.sanpham_id = $sanpham_id
    ORDER BY sz.id ASC
";

$query_result = mysqli_query($conn, $sql);

// Kiểm tra xem câu truy vấn có thành công không
if (!$query_result) {
    $response = [
        'success' => false,
        'message' => 'Lỗi truy vấn SQL: ' . mysqli_error($conn),
        'result' => []
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    die();
}

$data = array();
while ($row = mysqli_fetch_assoc($query_result)) {
    // Chuyển đổi kiểu dữ liệu cho đúng với Model trong Android
    $row['size_id'] = (int)$row['size_id'];
    $row['soluong'] = (int)$row['soluong'];
    $data[] = $row;
}

// <<< BƯỚC 3: SỬA LẠI CẤU TRÚC JSON THÀNH CÔNG >>>
// Kiểm tra xem mảng $data có dữ liệu không và trả về JSON tương thích
if (!empty($data)) {
    $response = [
        'success' => true,
        'message' => 'Thành công',
        'result' => $data
    ];
} else {
    // Nếu không có dòng nào được trả về
    $response = [
        'success' => false,
        'message' => 'Sản phẩm này hiện chưa có size',
        'result' => []
    ];
}

// In ra kết quả cuối cùng
echo json_encode($response, JSON_UNESCAPED_UNICODE);

?>
