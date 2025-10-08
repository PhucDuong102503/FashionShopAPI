<?php
//lay vef toan bo san pham trong bang san pham
include 'connect.php';

$mangspmoinhat=array();
$query="SELECT * FROM sanpham ORDER BY ID DESC LIMIT 6";//câu lệnh truy vấn 
$data=mysqli_query($conn,$query);//thực thi câu lệnh truy vấn
while($row=mysqli_fetch_assoc($data))//lấy dữ liệu từ data theo từng dòng và trả về mảng
{
    array_push($mangspmoinhat,new Sanphammoinhat(
        $row['id'],$row['tensanpham'],$row['giasanpham'],$row['hinhanhsanpham'],
        $row['motasanpham'],$row['idsanpham']));
}
echo json_encode($mangspmoinhat);//chuyển định dạng dữ liệu sang json

class Sanphammoinhat //định nghĩa class Sanpham đúng tên thuộc tính và hàm khởi tạo
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
