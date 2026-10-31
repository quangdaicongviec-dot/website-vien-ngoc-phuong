<?php
/**
 * GỬI EMAIL QUA SMTP (Gmail, Zoho, email theo tên miền…)
 * Dùng khi máy chủ không tự gửi được email (VPS thường không có sẵn dịch vụ gửi thư).
 * Cài trong Trang quản trị → Cài đặt → Thông báo đơn mới → Email. Mật khẩu lưu mã hóa trong kho bí mật.
 */

/** Đã cài máy chủ gửi thư SMTP chưa */
function smtp_enabled(): bool
{
    return trim((string) cfg('smtp_host')) !== '';
}

/** Máy chủ có tự gửi được email bằng hàm mail() không (có chương trình sendmail) */
function mail_function_usable(): bool
{
    if (!function_exists('mail')) {
        return false;
    }
    $bin = strtok(trim((string) ini_get('sendmail_path')), ' ');
    return $bin !== false && $bin !== '' && @is_executable($bin);   // @: máy chủ giới hạn open_basedir → coi như không có
}

/**
 * Gửi 1 email HTML qua SMTP. Trả về '' nếu thành công, hoặc câu báo lỗi dễ hiểu.
 * $html là toàn bộ thân thư (đã có <html>…).
 */
function smtp_send(string $to, string $subject, string $html, string $from, string $fromName, string $replyTo = ''): string
{
    $host   = trim((string) cfg('smtp_host'));
    $port   = (int) cfg('smtp_port') ?: 587;
    $secure = (string) cfg('smtp_secure') ?: ($port === 465 ? 'ssl' : 'tls');
    $user   = trim((string) cfg('smtp_user'));
    $pass   = secret_get('smtp_pass');
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $from = filter_var($user, FILTER_VALIDATE_EMAIL) ? $user : '';
    }
    if ($host === '' || $from === '') {
        return 'Chưa cài đủ máy chủ SMTP và email gửi đi.';
    }

    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host, 'SNI_enabled' => true]]);
    $fp  = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        return 'Không kết nối được máy chủ email ' . $host . ':' . $port . ($errstr ? ' (' . $errstr . ')' : '') . '.';
    }
    stream_set_timeout($fp, 20);
    $read = function () use ($fp): array {
        $code = 0;
        $text = '';
        while (($line = fgets($fp, 2048)) !== false) {
            $text .= $line;
            $code  = (int) substr($line, 0, 3);
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        return [$code, trim($text)];
    };
    $send = function (string $cmd, array $ok) use ($fp, $read): string {
        fwrite($fp, $cmd . "\r\n");
        [$code, $text] = $read();
        return in_array($code, $ok, true) ? '' : ($text !== '' ? $text : 'máy chủ email không phản hồi');
    };
    $fail = function (string $msg) use ($fp): string {
        @fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return $msg;
    };

    [$code, $text] = $read();
    if ($code !== 220) {
        return $fail('Máy chủ email từ chối kết nối: ' . $text);
    }
    $ehlo = 'EHLO ' . (parse_url(site_url(), PHP_URL_HOST) ?: 'localhost');
    if (($e = $send($ehlo, [250])) !== '') {
        return $fail('Máy chủ email báo lỗi: ' . $e);
    }
    if ($secure === 'tls') {
        if (($e = $send('STARTTLS', [220])) !== '') {
            return $fail('Máy chủ email không hỗ trợ mã hóa TLS – thử chọn “SSL” với cổng 465. (' . $e . ')');
        }
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
            return $fail('Không bật được mã hóa TLS với máy chủ email.');
        }
        if (($e = $send($ehlo, [250])) !== '') {
            return $fail('Máy chủ email báo lỗi: ' . $e);
        }
    }
    if ($user !== '') {
        if (($e = $send('AUTH LOGIN', [334])) !== '') {
            return $fail('Máy chủ email không cho đăng nhập: ' . $e);
        }
        if (($e = $send(base64_encode($user), [334])) !== '' || ($e = $send(base64_encode($pass), [235])) !== '') {
            return $fail('Sai tên đăng nhập hoặc mật khẩu email (Gmail phải dùng “Mật khẩu ứng dụng”, không phải mật khẩu Gmail). ' . $e);
        }
    }
    if (($e = $send('MAIL FROM:<' . $from . '>', [250])) !== '') {
        return $fail('Máy chủ email không nhận địa chỉ gửi ' . $from . ': ' . $e);
    }
    if (($e = $send('RCPT TO:<' . $to . '>', [250, 251])) !== '') {
        return $fail('Máy chủ email không nhận địa chỉ ' . $to . ': ' . $e);
    }
    if (($e = $send('DATA', [354])) !== '') {
        return $fail('Máy chủ email báo lỗi: ' . $e);
    }
    $domain  = substr(strrchr($from, '@'), 1) ?: 'localhost';
    $headers = [
        'Date: ' . date('r'),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
        'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>',
        'To: <' . $to . '>',
        'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    // Thân thư mã hóa base64 → không có dòng bắt đầu bằng dấu chấm, không cần "dot-stuffing"
    $data = implode("\r\n", $headers) . "\r\n\r\n" . rtrim(chunk_split(base64_encode($html), 76, "\r\n")) . "\r\n.";
    if (($e = $send($data, [250])) !== '') {
        return $fail('Máy chủ email không nhận thư: ' . $e);
    }
    $send('QUIT', [221]);
    fclose($fp);
    return '';
}
