<?php
include 'connect.php';

// Câu lệnh truy vấn sản phẩm mới nhất
$query = "SELECT * FROM sanpham ORDER BY ID DESC LIMIT 6";
$data = mysqli_query($conn, $query);

$mangspmoinhat = array();

// Tự động lấy URL gốc của server một cách linh hoạt
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/FashionShop/";

while ($row = mysqli_fetch_assoc($data)) {
    // Lấy đường dẫn ảnh từ database
    $hinhanh = trim($row['hinhanhsanpham']);

    // LOGIC XỬ LÝ HÌNH ẢNH AN TOÀN TUYỆT ĐỐI
    // Nếu $hinhanh không rỗng và không bắt đầu bằng 'http', nó là đường dẫn tương đối.
    if (!empty($hinhanh) && strpos($hinhanh, 'http') !== 0) {
        // Dùng ltrim để đảm bảo nó không có dấu '/' ở đầu trước khi nối chuỗi.
        $hinhanh = $base_url . ltrim($hinhanh, '/');
    }

    array_push($mangspmoinhat, new Sanphammoinhat(
        $row['id'],
        $row['tensanpham'],
        $row['giasanpham'],
        $hinhanh, // Dùng biến đã được xử lý
        $row['motasanpham'],
        $row['idloaisanpham']
    ));
}

// Trả về kết quả dưới dạng JSON
header('Content-Type: application/json; charset=utf-8');

if (!empty($mangspmoinhat)) {
    $arr = [
        'success' => true,
        'message' => 'Lấy sản phẩm mới nhất thành công',
        'result'  => $mangspmoinhat
    ];
} else {
    $arr = [
        'success' => false,
        'message' => 'Không có sản phẩm mới nhất',
        'result'  => []
    ];
}

echo json_encode($arr);

// Class Sanphammoinhat giữ nguyên
class Sanphammoinhat
{
    public $id;
    public $tensanpham;
    public $giasanpham;
    public $hinhanhsanpham;
    public $motasanpham;
    public $idloaisanpham;

    function __construct($id, $tensanpham, $giasanpham, $hinhanhsanpham, $motasanpham, $idloaisanpham)
    {
        $this->id = $id;
        $this->tensanpham = $tensanpham;
        $this->giasanpham = $giasanpham;
        $this->hinhanhsanpham = $hinhanhsanpham;
        $this->motasanpham = $motasanpham;
        $this->idloaisanpham = $idloaisanpham;
    }
}
?>
