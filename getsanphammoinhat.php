<?php
// Đảm bảo file này được lưu với encoding UTF-8
header('Content-Type: application/json; charset=utf-8');
include 'connect.php';

// === BƯỚC 1: XÁC ĐỊNH CHÍNH XÁC URL GỐC CỦA SERVER ===
// Dòng này sẽ tự động lấy scheme (http hoặc https) và host (tên miền hoặc IP)
// Khi chạy trên localhost, $_SERVER['HTTP_HOST'] sẽ là "localhost" hoặc "10.0.2.2" (khi máy ảo truy cập)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// Đây là URL gốc của toàn bộ project PHP của bạn.
// KẾT QUẢ MONG MUỐN: http://10.0.2.2/FashionShop/
$base_url = $protocol . $host . "/FashionShop/";


// === BƯỚC 2: TRUY VẤN DỮ LIỆU SẢN PHẨM ===
$query = "SELECT id, tensanpham, giasanpham, hinhanhsanpham, motasanpham, idloaisanpham FROM sanpham ORDER BY id DESC LIMIT 6";
$data = mysqli_query($conn, $query);
$mangspmoinhat = array();


// === BƯỚC 3: XỬ LÝ URL HÌNH ẢNH CHO TỪNG SẢN PHẨM ===
while ($row = mysqli_fetch_assoc($data)) {
    
    // Lấy đường dẫn hình ảnh thô từ database
    $image_path_from_db = trim($row['hinhanhsanpham']);
    $final_image_url = ''; // Khởi tạo URL cuối cùng là rỗng

    if (!empty($image_path_from_db)) {
        // TRƯỜNG HỢP 1: Nếu đường dẫn đã là một URL đầy đủ (bắt đầu bằng http)
        if (strpos($image_path_from_db, 'http') === 0) {
            // Đây là các ảnh cũ hoặc ảnh từ web khác, chỉ cần dùng nó.
            $final_image_url = $image_path_from_db;
        } 
        
        // TRƯỜNG HỢP 2: Nếu là đường dẫn tương đối (ví dụ: "uploads/anh.png" hoặc "/uploads/anh.png")
        else {
            // Ta sẽ xóa dấu "/" ở đầu chuỗi (nếu có) để tránh tạo ra URL lỗi như http://...//uploads
            $clean_path = ltrim($image_path_from_db, '/');
            // Sau đó, nối URL gốc với đường dẫn sạch này để tạo ra một URL hoàn chỉnh.
            $final_image_url = $base_url . $clean_path;
        }
    }
    
    // Tạo đối tượng sản phẩm với URL hình ảnh đã được xử lý hoàn chỉnh
    array_push($mangspmoinhat, new Sanphammoinhat(
        $row['id'],
        $row['tensanpham'],
        $row['giasanpham'],
        $final_image_url, // Sử dụng URL cuối cùng
        $row['motasanpham'],
        $row['idloaisanpham']
    ));
}


// === BƯỚC 4: TRẢ VỀ KẾT QUẢ CHO APP ===
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

// In ra chuỗi JSON và kết thúc script
echo json_encode($arr, JSON_UNESCAPED_UNICODE);
mysqli_close($conn);


// Class Sanphammoinhat không thay đổi
class Sanphammoinhat {
    public $id;
    public $tensanpham;
    public $giasanpham;
    public $hinhanhsanpham;
    public $motasanpham;
    public $idloaisanpham;

    function __construct($id, $tensanpham, $giasanpham, $hinhanhsanpham, $motasanpham, $idloaisanpham) {
        $this->id = $id;
        $this->tensanpham = $tensanpham;
        $this->giasanpham = $giasanpham;
        $this->hinhanhsanpham = $hinhanhsanpham;
        $this->motasanpham = $motasanpham;
        $this->idloaisanpham = $idloaisanpham;
    }
}
?>
