<?php

/**
 * File này chứa các hàm để gửi thông báo qua Firebase Cloud Messaging (FCM) API v1.
 * Đây là phiên bản production, sẵn sàng để được các file khác gọi đến.
 *
 * Yêu cầu:
 * 1. Đã chạy `composer require google/apiclient`.
 * 2. Có file service account JSON cùng cấp với file này.
 */

// 1. NẠP THƯ VIỆN CỦA GOOGLE
// ================================================================
require_once __DIR__ . '/vendor/autoload.php';

// Khai báo lớp Google Client để sử dụng.
use Google\Client as Google_Client;

// 2. CÁC HÀM CHỨC NĂNG
// ================================================================

/**
 * Lấy Access Token từ Google bằng cách sử dụng file service account JSON.
 * @return string Access Token để xác thực với FCM API.
 * @throws \Google\Exception Nếu có lỗi xảy ra trong quá trình xác thực.
 */
function getAccessToken()
{
    // Đường dẫn tuyệt đối đến file JSON. Hãy đảm bảo tên file chính xác 100%.
    $key_file_path = __DIR__ . '/appbanhang-fd9f6-firebase-adminsdk-fbsvc-c1b8f6ff88.json';

    if (!file_exists($key_file_path)) {
        error_log("FCM Error: Không tìm thấy file JSON xác thực tại: " . $key_file_path);
        throw new Exception("Không tìm thấy file JSON xác thực.");
    }

    $client = new Google_Client();
    $client->setAuthConfig($key_file_path);
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
    $client->refreshTokenWithAssertion();
    $token = $client->getAccessToken();
    return $token['access_token'];
}

/**
 * Gửi thông báo FCM đến một thiết bị cụ thể.
 * @param string $userToken FCM Token của thiết bị người nhận.
 * @param string $title Tiêu đề thông báo.
 * @param string $body Nội dung thông báo.
 * @param string $senderId ID của người gửi.
 * @param string $projectId Project ID của dự án Firebase.
 * @return bool|string Kết quả trả về từ Firebase.
 * @throws \Google\Exception
 */
function sendFCM_V1($userToken, $title, $body, $senderId, $projectId)
{
    $accessToken = getAccessToken();
    $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';

    $fields = [
        'message' => [
            'token' => $userToken,
            'data' => [
                'title'     => $title,
                'body'      => $body,
                'sender_id' => (string)$senderId
            ]
        ]
    ];

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('cURL error khi gửi FCM: ' . curl_error($ch));
    }
    curl_close($ch);
    return $result;
}

// Lấy Project ID từ Firebase Console.
$projectId = 'appbanhang'; // <-- ĐÃ ĐÚNG VỚI DỰ ÁN CỦA BẠN. GIỮ NGUYÊN.
