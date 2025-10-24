<?php
// donhang.php

// Hàm tiện ích để gửi response JSON
function send_response($success, $message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
}

include 'connect.php'; // Sử dụng file connect.php với MySQLi của bạn

// Lấy dữ liệu JSON từ body của request
$data = json_decode(file_get_contents("php://input"));

// 1. Kiểm tra dữ liệu đầu vào cơ bản
if (empty($data) || !isset($data->user_id) || !isset($data->chitiet) || !is_array($data->chitiet) || empty($data->chitiet)) {
    send_response(false, "Dữ liệu không hợp lệ hoặc giỏ hàng trống.");
    exit();
}

$user_id = $data->user_id;
$diachi = $data->diachi;
$tongtien = $data->tongtien;
$chitiet = $data->chitiet;
$current_date = date("Y-m-d H:i:s");

// Bắt đầu một Transaction với MySQLi
mysqli_begin_transaction($conn);

try {
    // ---- BƯỚC 2: KIỂM TRA TỒN KHO TRƯỚC KHI XỬ LÝ ----
    foreach ($chitiet as $item) {
        if (!isset($item->sanpham_id) || !isset($item->size_id) || !isset($item->soluong)) {
            throw new Exception("Lỗi: Dữ liệu chi tiết sản phẩm không đầy đủ.");
        }
        
        $sanpham_id = $item->sanpham_id;
        $size_id = $item->size_id;
        $soluong_dat = $item->soluong;

        // Câu lệnh SQL JOIN để lấy tên sản phẩm và số lượng tồn
        // Dấu ? là placeholder cho prepared statement trong MySQLi
        $sql_check = "
            SELECT 
                ss.soluong as soluong_ton, 
                sp.tensanpham as tensp 
            FROM `sanpham_size` as ss
            INNER JOIN `sanpham` as sp ON ss.sanpham_id = sp.id
            WHERE ss.sanpham_id = ? AND ss.size_id = ?
            FOR UPDATE;
        ";

        $stmt_check = mysqli_prepare($conn, $sql_check);
        // 'ii' nghĩa là cả 2 biến đều là integer (số nguyên)
        mysqli_stmt_bind_param($stmt_check, 'ii', $sanpham_id, $size_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $row = mysqli_fetch_assoc($result_check);

        // [SỬA LỖI QUAN TRỌNG] Kiểm tra xem sản phẩm + size có tồn tại không
        if ($row === null) {
            throw new Exception("Lỗi: Sản phẩm với ID $sanpham_id và Size ID $size_id không tồn tại trong kho.");
        }

        // Nếu tồn tại, tiếp tục kiểm tra số lượng
        $soluong_ton = $row['soluong_ton'];
        $tensp = $row['tensp']; 

        if ($soluong_dat <= 0) {
            throw new Exception("Lỗi: Số lượng đặt cho sản phẩm '$tensp' phải lớn hơn 0.");
        }

        if ($soluong_dat > $soluong_ton) {
            throw new Exception("Lỗi: Sản phẩm '$tensp' (Size đã chọn) chỉ còn $soluong_ton sản phẩm, không đủ số lượng bạn đặt.");
        }
    }

    // ---- BƯỚC 3: NẾU TẤT CẢ ĐỀU HỢP LỆ, TIẾN HÀNH TẠO ĐƠN HÀNG ----

    // 3.1. Thêm vào bảng `donhang`
    $sql_order = "INSERT INTO `donhang` (user_id, diachi, tongtien, ngaydathang, trangthai) VALUES (?, ?, ?, ?, 0)";
    $stmt_order = mysqli_prepare($conn, $sql_order);
    // 'isds' = integer, string, double, string
    mysqli_stmt_bind_param($stmt_order, 'isds', $user_id, $diachi, $tongtien, $current_date);
    mysqli_stmt_execute($stmt_order);
    $donhang_id = mysqli_insert_id($conn); // Lấy ID của đơn hàng vừa tạo

    // 3.2. Thêm vào `chitietdonhang` và cập nhật tồn kho
    foreach ($chitiet as $item) {
        $sanpham_id = $item->sanpham_id;
        $size_id = $item->size_id;
        $soluong = $item->soluong;
        $gia = $item->gia;

        // Thêm chi tiết đơn hàng
        $sql_detail = "INSERT INTO `chitietdonhang` (donhang_id, sanpham_id, soluong, gia, size_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_detail = mysqli_prepare($conn, $sql_detail);
        // 'iiidi' = integer, integer, integer, double, integer
        mysqli_stmt_bind_param($stmt_detail, 'iiidi', $donhang_id, $sanpham_id, $soluong, $gia, $size_id);
        mysqli_stmt_execute($stmt_detail);

        // Cập nhật (trừ) số lượng tồn kho
        $sql_update = "UPDATE `sanpham_size` SET `soluong` = `soluong` - ? WHERE `sanpham_id` = ? AND `size_id` = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        // 'iii' = integer, integer, integer
        mysqli_stmt_bind_param($stmt_update, 'iii', $soluong, $sanpham_id, $size_id);
        mysqli_stmt_execute($stmt_update);
    }

    // Nếu mọi thứ thành công, commit transaction
    mysqli_commit($conn);
    send_response(true, "Đặt hàng thành công!");

} catch (Exception $e) {
    // Nếu có bất kỳ lỗi nào, rollback tất cả các thay đổi
    mysqli_rollback($conn);
    // Và gửi thông báo lỗi chi tiết
    send_response(false, $e->getMessage());
}

// Đóng kết nối
mysqli_close($conn);

?>
