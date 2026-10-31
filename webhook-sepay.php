<?php
/**
 * Nhận thông báo tiền vào từ SePay (sepay.vn) và tự động xác nhận – giống webhook-casso.php:
 *  - Mã giữ hàng GIU…  → xác nhận đã nhận cọc (nếu đủ số tiền).
 *  - Mã đơn hàng NP…   → ghi nhận đơn đã thanh toán chuyển khoản.
 * Khai báo trên SePay: Tích hợp → Webhooks → Thêm webhook
 *   - URL: https://tenmiencuaban.vn/webhook-sepay.php
 *   - Kiểu chứng thực: API Key – điền đúng "API Key" đã nhập trong trang quản trị (Cài đặt → Thanh toán).
 * Nhật ký: data/don-hang/giao-dich-YYYY-MM.csv
 */
require __DIR__ . '/includes/init.php';
header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex');

function reply(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

$key = trim((string) cfg('sepay_api_key'));
if ($key === '' || cfg('payment_provider', 'casso') !== 'sepay') {
    reply(503, ['success' => false, 'message' => 'Chưa bật SePay trong trang quản trị']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(405, ['success' => false, 'message' => 'Chỉ nhận POST']);
}
// SePay gửi header "Authorization: Apikey <API_KEY>"
$auth = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if ($auth === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $name => $value) {
        if (strcasecmp($name, 'Authorization') === 0) {
            $auth = (string) $value;
        }
    }
}
if (!preg_match('/^Apikey\s+(.+)$/i', trim($auth), $m) || !hash_equals($key, trim($m[1]))) {
    reply(401, ['success' => false, 'message' => 'Sai API Key']);
}

$raw = (string) file_get_contents('php://input');
$tx  = json_decode($raw, true);
if (!is_array($tx)) {
    $tx = $_POST;               // SePay cũng có thể gửi dạng form
}
if (!isset($tx['transferAmount'])) {
    reply(400, ['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
}

$amount = ($tx['transferType'] ?? 'in') === 'in' ? (int) round((float) $tx['transferAmount']) : 0;
$desc   = trim((string) ($tx['content'] ?? '') . ' ' . (string) ($tx['description'] ?? ''));
$txId   = 'sepay|' . (string) ($tx['id'] ?? '') . '|' . (string) ($tx['referenceCode'] ?? '');
$action = bank_transfer_received($txId, $amount, $desc, (string) ($tx['transactionDate'] ?? date('Y-m-d H:i:s')),
    '', (string) ($tx['referenceCode'] ?? ''));

reply(200, ['success' => true, 'action' => $action]);
