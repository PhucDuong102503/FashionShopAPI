<?php
include 'connect.php';

header('Content-Type: application/json; charset=utf-8');

// Lấy và validate input
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
if ($page < 1) $page = 1;
$total = 5; // số item trên 1 trang
$pos = ($page - 1) * $total;

if (!isset($_POST['idloaisanpham'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số idloaisanpham', 'result' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$idloaisanpham = (int)$_POST['idloaisanpham'];

// Tạo base_url động (nếu muốn, thay bằng IP/domain cố định)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$base_url = $scheme . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
if (empty($base_url)) {
    $base_url = 'http://localhost/FashionShop/';
}

// Sử dụng prepared statement để an toàn
$stmt = $conn->prepare("SELECT id, tensanpham, giasanpham, hinhanhsanpham, motasanpham, idloaisanpham FROM sanpham WHERE idloaisanpham = ? LIMIT ?, ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error, 'result' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param('iii', $idloaisanpham, $pos, $total);
$stmt->execute();
$result = $stmt->get_result();

$mangspmoinhat = [];

while ($row = $result->fetch_assoc()) {
    // Xử lý đường dẫn ảnh
    $hinhanh = isset($row['hinhanhsanpham']) ? trim($row['hinhanhsanpham']) : '';
    if ($hinhanh === '' || $hinhanh === null) {
        $hinhanh = $base_url . 'uploads/no_image.png'; // ảnh mặc định nếu rỗng
    } elseif (!preg_match('/^https?:\/\//i', $hinhanh)) {
        // nếu là đường dẫn tương đối trong server, gắn base_url
        $hinhanh = $base_url . ltrim($hinhanh, '/');
    }

    $mangspmoinhat[] = new Sanphammoinhat(
        (int)$row['id'],
        $row['tensanpham'],
        $row['giasanpham'],
        $hinhanh,
        $row['motasanpham'],
        (int)$row['idloaisanpham']
    );
}

if (!empty($mangspmoinhat)) {
    $arr = [
        'success' => true,
        'message' => 'Lấy sản phẩm thành công',
        'result'  => $mangspmoinhat
    ];
} else {
    $arr = [
        'success' => false,
        'message' => 'Không có sản phẩm',
        'result'  => []
    ];
}

echo json_encode($arr, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();

// Model class
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
