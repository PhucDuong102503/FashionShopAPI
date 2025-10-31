<?php
ob_start(); // Bắt đầu bộ đệm để kiểm soát output
include "connect.php"; 

$arr = [
    'success' => false,
    'message' => "Có lỗi xảy ra",
    'result' => []
];

if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    // 1. Lấy tất cả đơn hàng của người dùng
    $query_donhang = "SELECT * FROM `donhang` WHERE `user_id` = ? ORDER BY `id` DESC";
    $stmt_donhang = $conn->prepare($query_donhang);

    if ($stmt_donhang) {
        $stmt_donhang->bind_param("i", $user_id);
        $stmt_donhang->execute();
        $result_donhang = $stmt_donhang->get_result();

        $donHangArr = array();
        
        // 2. Với mỗi đơn hàng, lấy chi tiết của nó
        while ($row_donhang = $result_donhang->fetch_assoc()) {
            $donhang_id = $row_donhang['id'];
            
            // Câu truy vấn để lấy chi tiết sản phẩm, kết hợp với bảng sanpham và size
            $query_chitiet = "
                SELECT 
                    ct.soluong, 
                    sp.tensanpham AS tensp, 
                    sp.hinhanhsanpham AS hinhanh,
                    s.tensize AS size
                FROM `chitietdonhang` ct
                JOIN `sanpham` sp ON ct.sanpham_id = sp.id
                LEFT JOIN `size` s ON ct.size_id = s.id
                WHERE ct.donhang_id = ?
            ";
            
            $stmt_chitiet = $conn->prepare($query_chitiet);
            $stmt_chitiet->bind_param("i", $donhang_id);
            $stmt_chitiet->execute();
            $result_chitiet = $stmt_chitiet->get_result();
            
            $chiTietArr = array();
            while ($row_chitiet = $result_chitiet->fetch_assoc()) {
                $chiTietArr[] = $row_chitiet;
            }
            $stmt_chitiet->close();
            
            // Gán mảng chi tiết sản phẩm vào đơn hàng tương ứng
            $row_donhang['chitiet'] = $chiTietArr;
            $donHangArr[] = $row_donhang;
        }
        $stmt_donhang->close();

        if (!empty($donHangArr)) {
            $arr = [
                'success' => true,
                'message' => "Lấy lịch sử đơn hàng thành công",
                'result' => $donHangArr
            ];
        } else {
            $arr = [
                'success' => false,
                'message' => "Bạn chưa có đơn hàng nào",
                'result' => []
            ];
        }
    } else {
        $arr['message'] = "Lỗi truy vấn đơn hàng: " . $conn->error;
    }
} else {
    $arr['message'] = "Không có thông tin người dùng";
}

ob_end_clean(); // Xóa mọi output rác
header('Content-Type: application/json; charset=utf-8');
echo json_encode($arr);
$conn->close();
?>
