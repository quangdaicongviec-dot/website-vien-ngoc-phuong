#!/bin/bash
# ==================================================================
#  Tự lấy chứng chỉ SSL miễn phí (Let's Encrypt) cho vienngocphuong.com
#  ngay khi tên miền đã trỏ về VPS này, rồi bật https.
#  Lịch chạy 10 phút / lần (/etc/cron.d/vienngocphuong); đã có chứng chỉ đủ tên miền thì không làm gì.
#  Gia hạn chứng chỉ do certbot.timer sẵn có của máy chủ lo (tự nạp lại Nginx sau khi gia hạn).
# ==================================================================
set -u
DOMAIN=vienngocphuong.com
WEB=/var/www/vienngocphuong
WAIT=/var/lib/vienngocphuong/ssl-cho-thu-lai
CERT=/etc/letsencrypt/live/$DOMAIN/fullchain.pem

exec 9>/run/vienngocphuong-ssl.lock
flock -n 9 || exit 0

IP=$(ip -4 -o addr show scope global | awk '{print $4}' | cut -d/ -f1 | head -1)
points() {   # tên miền đã trỏ về máy chủ này chưa (hỏi DNS công cộng)
    for r in 1.1.1.1 8.8.8.8; do dig +short +time=3 +tries=1 A "$1" @"$r" 2>/dev/null; done | grep -qx "$IP"
}
SANS=""
[ -s "$CERT" ] && SANS=$(openssl x509 -in "$CERT" -noout -ext subjectAltName 2>/dev/null | grep -o 'DNS:[^,]*' | sed 's/DNS://; s/ //g')

WANT=()
for n in "$DOMAIN" "www.$DOMAIN"; do points "$n" && WANT+=("$n"); done
MISSING=0
for n in "${WANT[@]}"; do printf '%s\n' "$SANS" | grep -qx "$n" || MISSING=1; done

if [ "$MISSING" = 1 ] && printf '%s\n' "${WANT[@]}" | grep -qx "$DOMAIN"; then
    LAST=$(cat "$WAIT" 2>/dev/null || echo 0)
    if [ $(( $(date +%s) - LAST )) -ge 1800 ]; then        # lỗi thì chờ 30 phút (tránh bị Let's Encrypt chặn)
        ARGS=(); for n in "${WANT[@]}"; do ARGS+=(-d "$n"); done
        if certbot certonly --webroot -w /var/www/letsencrypt --cert-name "$DOMAIN" "${ARGS[@]}" \
            --non-interactive --agree-tos --expand --deploy-hook "systemctl reload nginx" --quiet; then
            echo "$(date '+%F %T') Đã lấy chứng chỉ SSL cho: ${WANT[*]}"
            rm -f "$WAIT"
        else
            echo "$(date '+%F %T') Lấy chứng chỉ SSL thất bại cho: ${WANT[*]} – thử lại sau 30 phút"
            date +%s > "$WAIT"
        fi
    fi
fi

# Có chứng chỉ → cấu hình Nginx chạy https (chỉ nạp lại khi có thay đổi)
if [ -s "$CERT" ]; then
    bash "$WEB/cong-cu/vps/cau-hinh-nginx.sh" | grep -v "không đổi" || true
fi
