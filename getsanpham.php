<?php
//lay vef toan bo san pham trong bang san pham
include 'connect.php';
$page=$_GET['page']; //mobile gui len so trang can lay
$idsp=$_POST['idsanpham'];//mobile gui len id loai san pham can lay
$space=5; //so san pham tren 1 trang
$limit=($page-1)*$space; //vi tri bat dau lay san pham tren trang can lay: vị trí bắt đầu = (số trang - 1) * số sản phẩm trên 1 trang
$mangsanpham=array();
$query="SELECT * FROM sanpham WHERE idsanpham=$idsp LIMIT $limit,$space";//câu lệnh truy vấn 
$data=mysqli_query($conn,$query);//thực thi câu lệnh truy vấn
while($row=mysqli_fetch_assoc($data))//lấy dữ liệu từ data theo từng dòng và trả về mảng
{
    array_push($mangsanpham,new Sanpham(
        $row['id'],$row['tensanpham'],$row['giasanpham'],$row['hinhanhsanpham'],
        $row['motasanpham'],$row['idsanpham']));
}
echo json_encode($mangsanpham);//chuyển định dạng dữ liệu sang json

class Sanpham //định nghĩa class Sanpham đúng tên thuộc tính và hàm khởi tạo
{
    public $id;
    public $tensanpham;
    public $giasanpham;
    public $hinhsanpham;
    public $motasanpham;
    public $idsanpham;

    function __construct($id,$tensanpham,$giasanpham,$hinhsanpham,$motasanpham,$idsanpham)
    {
        $this->id=$id;
        $this->tensanpham=$tensanpham;
        $this->giasanpham=$giasanpham;
        $this->hinhsanpham=$hinhsanpham;
        $this->motasanpham=$motasanpham;
        $this->idsanpham=$idsanpham;
    }
}
