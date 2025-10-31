<?php
// Sử dụng file connect.php với MySQLi của bạn
include 'connect.php';

// Hàm tiện ích để gửi response JSON và kết thúc script
function send_response($success, $message)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit(); // Dừng script ngay sau khi gửi response
}

// ===================================================================
// BƯỚC 1: LẤY VÀ KIỂM TRA DỮ LIỆU TỪ $_POST
// ===================================================================

// Lấy dữ liệu từ POST (form-data), nhất quán với các file khác
$user_id = $_POST['user_id'] ?? 0;
$diachi = $_POST['diachi'] ?? '';
$sodienthoai = $_POST['sodienthoai'] ?? '';
$email = $_POST['email'] ?? '';
$soluong_tong = $_POST['soluong'] ?? 0; // Tổng số lượng sản phẩm
$tongtien = $_POST['tongtien'] ?? '0';
$chitiet_json = $_POST['chitiet'] ?? '[]';

// Kiểm tra dữ liệu đầu vào cơ bản
if (empty($user_id) || empty($diachi) || empty($sodienthoai) || empty($email) || empty($soluong_tong) || $chitiet_json === '[]') {
    send_response(false, "Thiếu thông tin đơn hàng, vui lòng kiểm tra lại.");
}

$chitiet = json_decode($chitiet_json);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($chitiet) || empty($chitiet)) {
    send_response(false, "Dữ liệu chi tiết đơn hàng không hợp lệ.");
}

// ===================================================================
// BƯỚC 2: BẮT ĐẦU TRANSACTION VÀ KIỂM TRA TỒN KHO
// ===================================================================

// Bắt đầu một Transaction với MySQLi
mysqli_begin_transaction($conn);

try {
    // ---- KIỂM TRA TỒN KHO TRƯỚC KHI XỬ LÝ ----
    foreach ($chitiet as $item) {
        // Kiểm tra tính đầy đủ của từng item trong giỏ hàng
        if (!isset($item->sanpham_id) || !isset($item->size_id) || !isset($item->soluong)) {
            throw new Exception("Dữ liệu chi tiết sản phẩm không đầy đủ.");
        }

        $sanpham_id = $item->sanpham_id;
        $size_id = $item->size_id;
        $soluong_dat = $item->soluong;

        // Câu lệnh SQL để lấy số lượng tồn kho và khóa dòng đó lại để tránh race condition
        $sql_check = "SELECT soluong, (SELECT tensanpham FROM sanpham WHERE id = ?) as tensp FROM `sanpham_size` WHERE sanpham_id = ? AND size_id = ? FOR UPDATE";

        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, 'iii', $sanpham_id, $sanpham_id, $size_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $row = mysqli_fetch_assoc($result_check);

        if ($row === null) {
            throw new Exception("Sản phẩm với ID $sanpham_id và Size ID $size_id không tồn tại trong kho.");
        }

        $soluong_ton = $row['soluong'];
        $tensp = $row['tensp'];

        if ($soluong_dat <= 0) {
            throw new Exception("Số lượng đặt cho sản phẩm '$tensp' phải lớn hơn 0.");
        }

        if ($soluong_dat > $soluong_ton) {
            throw new Exception("Sản phẩm '$tensp' (Size đã chọn) chỉ còn $soluong_ton sản phẩm, không đủ số lượng bạn đặt.");
        }
    }

    // ===================================================================
    // BƯỚC 3: NẾU TẤT CẢ ĐỀU HỢP LỆ, TIẾN HÀNH TẠO ĐƠN HÀNG
    // ===================================================================

    // 3.1. Thêm vào bảng `donhang`. Cột `trangthai` sẽ tự lấy giá trị default là 'Chờ giao hàng'
    $sql_order = "INSERT INTO `donhang` (user_id, diachi, sodienthoai, email, soluong, tongtien) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_order = mysqli_prepare($conn, $sql_order);
    // 'isssis' = integer, string, string, string, integer, string (decimal/double gửi dưới dạng string)
    mysqli_stmt_bind_param($stmt_order, 'isssis', $user_id, $diachi, $sodienthoai, $email, $soluong_tong, $tongtien);
    mysqli_stmt_execute($stmt_order);
    $donhang_id = mysqli_insert_id($conn);

    // 3.2. Thêm vào `chitietdonhang` và cập nhật tồn kho
    $sql_detail = "INSERT INTO `chitietdonhang` (donhang_id, sanpham_id, soluong, gia, size_id) VALUES (?, ?, ?, ?, ?)";
    $sql_update = "UPDATE `sanpham_size` SET `soluong` = `soluong` - ? WHERE `sanpham_id` = ? AND `size_id` = ?";

    $stmt_detail = mysqli_prepare($conn, $sql_detail);
    $stmt_update = mysqli_prepare($conn, $sql_update);

    foreach ($chitiet as $item) {
        // Thêm chi tiết đơn hàng
        // 'iiidi' = integer, integer, integer, double, integer
        mysqli_stmt_bind_param($stmt_detail, 'iiidi', $donhang_id, $item->sanpham_id, $item->soluong, $item->gia, $item->size_id);
        mysqli_stmt_execute($stmt_detail);

        // Cập nhật (trừ) số lượng tồn kho
        // 'iii' = integer, integer, integer
        mysqli_stmt_bind_param($stmt_update, 'iii', $item->soluong, $item->sanpham_id, $item->size_id);
        mysqli_stmt_execute($stmt_update);
    }

    // Nếu mọi thứ thành công, commit transaction
    mysqli_commit($conn);
    send_response(true, "Đặt hàng thành công!");
} catch (Exception $e) {
    // Nếu có bất kỳ lỗi nào, rollback tất cả các thay đổi
    mysqli_rollback($conn);
    // Và gửi thông báo lỗi chi tiết cho client
    send_response(false, $e->getMessage());
}

// Đóng kết nối
mysqli_close($conn);
