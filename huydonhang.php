<?php
include "connect.php";
header('Content-Type: application/json; charset=utf-8');

$donhang_id = $_POST['donhang_id'] ?? 0;

if (empty($donhang_id)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID đơn hàng']);
    exit();
}

$conn->begin_transaction();

try {
    // --- Lấy thông tin sản phẩm trong đơn hàng để hoàn lại số lượng ---
    $query_get_items = "SELECT `sanpham_id`, `size_id`, `soluong` FROM `chitietdonhang` WHERE `donhang_id` = ?";
    $stmt_get_items = $conn->prepare($query_get_items);
    $stmt_get_items->bind_param("i", $donhang_id);
    $stmt_get_items->execute();
    $result_items = $stmt_get_items->get_result();

    $items_to_restore = [];
    while ($row = $result_items->fetch_assoc()) {
        $items_to_restore[] = $row;
    }
    $stmt_get_items->close();

    // --- Cập nhật trạng thái đơn hàng thành "Đã hủy đơn" ---
    $query_update_order = "UPDATE `donhang` SET `trangthai` = 'Đã hủy đơn' WHERE `id` = ? AND `trangthai` = 'Chờ giao hàng'";
    $stmt_update_order = $conn->prepare($query_update_order);
    $stmt_update_order->bind_param("i", $donhang_id);
    $stmt_update_order->execute();

    // Kiểm tra xem có đúng 1 dòng được cập nhật không (để đảm bảo chỉ hủy được đơn "Chờ giao hàng")
    if ($stmt_update_order->affected_rows > 0) {
        // --- Hoàn lại số lượng đã mua vào bảng sanpham_size ---
        foreach ($items_to_restore as $item) {
            $query_restore = "UPDATE `sanpham_size` SET `soluong` = `soluong` + ? WHERE `sanpham_id` = ? AND `size_id` = ?";
            $stmt_restore = $conn->prepare($query_restore);
            $stmt_restore->bind_param("iii", $item['soluong'], $item['sanpham_id'], $item['size_id']);
            $stmt_restore->execute();
            $stmt_restore->close();
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Đã hủy đơn hàng thành công']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Không thể hủy đơn hàng này']);
    }

    $stmt_update_order->close();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}

$conn->close();
