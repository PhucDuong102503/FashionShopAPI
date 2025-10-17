<?php
include 'connect.php';

// Câu lệnh truy vấn sản phẩm mới nhất
$query = "SELECT * FROM sanpham ORDER BY ID DESC LIMIT 6";
$data = mysqli_query($conn, $query);

$mangspmoinhat = array();

// URL gốc của server (đường dẫn tới thư mục chứa ảnh)
$base_url = "http://192.168.1.106/FashionShop/"; // 👉 thay bằng domain hoặc IP thật, ví dụ: http://192.168.1.10/FashionShop/

while ($row = mysqli_fetch_assoc($data)) {
    // Lấy đường dẫn ảnh
    $hinhanh = trim($row['hinhanhsanpham']);

    // ✅ Kiểm tra và sửa lỗi đường dẫn
    if (!preg_match('/^https?:\/\//', $hinhanh)) {
        // Nếu ảnh không có http/https → thêm base_url vào trước
        $hinhanh = $base_url . ltrim($hinhanh, '/');
    }

    // ✅ Kiểm tra file có tồn tại trên server không (nếu dùng ảnh lưu local)
    // Nếu bạn lưu ảnh trên server (thư mục ./uploads), có thể kiểm tra như sau:
    // if (!file_exists(__DIR__ . '/' . $row['hinhanhsanpham'])) {
    //     $hinhanh = $base_url . 'uploads/no_image.png'; // ảnh mặc định nếu lỗi
    // }

    array_push($mangspmoinhat, new Sanphammoinhat(
        $row['id'],
        $row['tensanpham'],
        $row['giasanpham'],
        $hinhanh, // ✅ dùng biến đã xử lý
        $row['motasanpham'],
        $row['idloaisanpham']
    ));
}

// Tạo mảng phản hồi JSON
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
