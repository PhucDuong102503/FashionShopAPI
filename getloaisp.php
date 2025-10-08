<?php
//lấy dữ liệu trong bảng thể loại sản phẩm
include 'connect.php';

// Định nghĩa class Loaisp đúng tên thuộc tính và hàm khởi tạo
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

$query = "SELECT * FROM loaisanpham";//câu lệnh truy vấn
$data = mysqli_query($conn, $query);//thực thi câu lệnh truy vấn
$mangloaisp = array();
while ($row = mysqli_fetch_assoc($data)) {//lấy dữ liệu từ data theo từng dòng và trả về mảng
    array_push($mangloaisp, new Loaisp($row['id'], $row['tenloaisanpham'], $row['hinhloaisanpham'])); //đưa dữ liệu vào mảng $mangloaisp
}
echo json_encode($mangloaisp);//chuyển định dạng dữ liệu sang json
