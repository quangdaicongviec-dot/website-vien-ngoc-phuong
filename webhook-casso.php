<?php
/**
 * Nhận thông báo tiền vào từ Casso (Webhook V2) và tự động xác nhận:
 *  - Mã giữ hàng GIU…  → xác nhận đã nhận cọc (nếu đủ số tiền) → món ngọc được giữ chính thức.
 *  - Mã đơn hàng NP…   → ghi nhận đơn đã thanh toán chuyển khoản.
 *  - Không có mã       → chỉ báo cho shop qua Telegram.
 * Link khai báo trên Casso: https://tenmiencuaban.vn/webhook-casso.php
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

$key = trim((string) cfg('casso_webhook_key'));
if ($key === '' || !in_array(cfg('payment_provider', 'casso'), ['casso', ''], true)) {
    reply(503, ['success' => false, 'message' => 'Chưa cấu hình casso_webhook_key']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(405, ['success' => false, 'message' => 'Chỉ nhận POST']);
}

$body = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($body)) {
    reply(400, ['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
}
if (!casso_verify_signature((string) ($_SERVER['HTTP_X_CASSO_SIGNATURE'] ?? ''), $body, $key)) {
    reply(401, ['success' => false, 'message' => 'Sai chữ ký']);
}

// Webhook V2 gửi 1 giao dịch trong "data"; vẫn hỗ trợ dạng danh sách cho chắc chắn
$list = $body['data'] ?? [];
$list = isset($list['amount']) ? [$list] : (is_array($list) ? $list : []);

$results = [];
foreach ($list as $tx) {
    if (!is_array($tx)) {
        continue;
    }
    $txId   = (string) ($tx['id'] ?? '') . '|' . (string) ($tx['reference'] ?? '');
    $action = bank_transfer_received($txId, (int) round((float) ($tx['amount'] ?? 0)), (string) ($tx['description'] ?? ''),
        (string) ($tx['transactionDateTime'] ?? date('Y-m-d H:i:s')), trim((string) ($tx['counterAccountName'] ?? '')), (string) ($tx['reference'] ?? ''));
    $results[] = ['id' => $txId, 'action' => $action];
}

reply(200, ['success' => true, 'results' => $results]);
