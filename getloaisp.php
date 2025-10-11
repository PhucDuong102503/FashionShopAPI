<?php
include 'connect.php';

// Định nghĩa class Loaisp
class Loaisp {
    public $id;
    public $tenloaisanpham;
    public $hinhloaisanpham;

    function __construct($id, $tenloaisanpham, $hinhloaisanpham) {
        $this->id = $id;
        $this->tenloaisanpham = $tenloaisanpham;
        $this->hinhloaisanpham = $hinhloaisanpham;
    }
}

// Truy vấn bảng loaisanpham
$query = "SELECT * FROM loaisanpham";
$data = mysqli_query($conn, $query);
$mangloaisp = array();

while ($row = mysqli_fetch_assoc($data)) {
    $mangloaisp[] = new Loaisp($row['id'], $row['tenloaisanpham'], $row['hinhloaisanpham']);
}

// Kiểm tra và tạo phản hồi JSON
if (!empty($mangloaisp)) {
    $arr = [
        'success' => true,
        'message' => 'thanh cong',
        'result'  => $mangloaisp
    ];
} else {
    $arr = [
        'success' => false,
        'message' => 'khong co du lieu',
        'result'  => []
    ];
}

// Xuất ra JSON
header('Content-Type: application/json');
echo json_encode($arr, JSON_UNESCAPED_UNICODE);
?>
