<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

// ===============================
// 🧾 CẤU HÌNH VNPAY SANDBOX
// ===============================
$vnp_TmnCode = "CA75GMYS"; // Mã website tại VNPAY Sandbox
$vnp_HashSecret = "F2QF0SN8YTQE97E314STQ49AHJ74WURC"; // Chuỗi bí mật
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";

// ⚠️ Địa chỉ IP máy tính chạy XAMPP/Laragon
//$ip_may_tinh = "192.168.1.9"; // Cần sửa nếu đổi WiFi hoặc IP
$ip_may_tinh = "10.0.2.2";
$vnp_Returnurl = "http://" . $ip_may_tinh . "/FashionShop/vnpay_return.php";

// ===============================
// 🧩 LẤY DỮ LIỆU TỪ APP ANDROID
// ===============================
$vnp_TxnRef = time(); // Mã giao dịch duy nhất
$vnp_OrderInfo = "Thanh toan don hang " . $vnp_TxnRef;
$vnp_OrderType = "billpayment";
$vnp_Amount = isset($_POST['amount']) ? (int)$_POST['amount'] * 100 : 0;
$vnp_Locale = "vn";
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Nếu không nhận được amount hợp lệ thì báo lỗi sớm
if ($vnp_Amount <= 0) {
    echo json_encode(['code' => '01', 'message' => 'Invalid amount']);
    exit();
}

// ===============================
// 🛠️ TẠO MẢNG THAM SỐ GỬI ĐẾN VNPAY
// ===============================
$inputData = array(
    "vnp_Version" => "2.1.0",
    "vnp_Command" => "pay",
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_Amount" => $vnp_Amount,
    "vnp_CreateDate" => date('YmdHis'),
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => $vnp_IpAddr,
    "vnp_Locale" => $vnp_Locale,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_OrderType" => $vnp_OrderType,
    "vnp_ReturnUrl" => $vnp_Returnurl,
    "vnp_TxnRef" => $vnp_TxnRef
);

// ===============================
// 🔒 TẠO CHUỖI HASH XÁC THỰC
// ===============================
ksort($inputData);
$query = "";
$hashdata = "";
$i = 0;

foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
}

// ❗ Xóa dấu & cuối cùng để tránh lỗi &&vnp_SecureHash
$query = rtrim($query, '&');

$vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
$vnp_Url = $vnp_Url . "?" . $query . '&vnp_SecureHash=' . $vnp_SecureHash;

// ===============================
// 🧾 GHI LOG KIỂM TRA (TÙY CHỌN)
// ===============================
file_put_contents('vnpay_debug.txt', $vnp_Url . "\n", FILE_APPEND);

// ===============================
// ✅ PHẢN HỒI VỀ APP ANDROID
// ===============================
echo json_encode([
    'code' => '00',
    'message' => 'success',
    'data' => $vnp_Url
]);
exit();
?>
