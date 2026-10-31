#!/bin/bash
# ==================================================================
#  Tạo / cập nhật cấu hình Nginx cho vienngocphuong.com (chạy bằng root)
#  - Chưa có chứng chỉ SSL: phục vụ website qua http.
#  - Đã có chứng chỉ: http → https, www → tên miền chính.
#  Chỉ nạp lại Nginx (reload êm, không ngắt kết nối) khi cấu hình thay đổi.
#  "nginx -t" báo lỗi → tự trả lại cấu hình cũ, các website khác không bị ảnh hưởng.
# ==================================================================
set -euo pipefail
DOMAIN=vienngocphuong.com
WEB=/var/www/vienngocphuong
SITE=/etc/nginx/sites-available/$DOMAIN
LINK=/etc/nginx/sites-enabled/$DOMAIN
SNIP=/etc/nginx/snippets/vienngocphuong.conf
CERT=/etc/letsencrypt/live/$DOMAIN
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

cp "$WEB/cong-cu/vps/nginx-quy-tac.conf" "$TMP/snip"

# Tên miền có trong chứng chỉ SSL hiện có
SANS=""
if [ -s "$CERT/fullchain.pem" ]; then
    SANS=$(openssl x509 -in "$CERT/fullchain.pem" -noout -ext subjectAltName 2>/dev/null | grep -o 'DNS:[^,]*' | sed 's/DNS://; s/ //g' || true)
fi
has() { grep -qx "$1" <<<"$SANS"; }

ACME='    location ^~ /.well-known/acme-challenge/ {
        root /var/www/letsencrypt;
        default_type text/plain;
        try_files $uri =404;
    }'
SSL="    ssl_certificate $CERT/fullchain.pem;
    ssl_certificate_key $CERT/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;"

{
    echo "# Website Viên Ngọc Phương – tạo tự động bởi $WEB/cong-cu/vps/cau-hinh-nginx.sh (đừng sửa tay)."
    if has "$DOMAIN"; then
        cat <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN www.$DOMAIN;
$ACME
    location / {
        return 301 https://$DOMAIN\$request_uri;
    }
}
EOF
        if has "www.$DOMAIN"; then
            cat <<EOF
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name www.$DOMAIN;
$SSL
    return 301 https://$DOMAIN\$request_uri;
}
EOF
        fi
        cat <<EOF
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name $DOMAIN;
$SSL
    include $SNIP;
}
EOF
    else
        cat <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN www.$DOMAIN;
$ACME
    include $SNIP;
}
EOF
    fi
} > "$TMP/site"

MODE=$(has "$DOMAIN" && echo "https" || echo "http (chưa có SSL)")
if cmp -s "$TMP/site" "$SITE" && cmp -s "$TMP/snip" "$SNIP" && [ -L "$LINK" ]; then
    echo "Nginx: không đổi – $MODE"
    exit 0
fi

# Cất bản cũ để trả lại nếu lỗi
HAD_LINK=0; [ -L "$LINK" ] && HAD_LINK=1
for f in "$SITE" "$SNIP"; do [ -f "$f" ] && cp -p "$f" "$TMP/$(basename "$f").cu"; done

install -m 644 "$TMP/snip" "$SNIP"
install -m 644 "$TMP/site" "$SITE"
ln -sfn "$SITE" "$LINK"

if nginx -t 2>"$TMP/loi"; then
    systemctl reload nginx
    echo "Nginx: đã cập nhật – $MODE"
else
    echo "Nginx báo lỗi – đã trả lại cấu hình cũ:" >&2
    cat "$TMP/loi" >&2
    for f in "$SITE" "$SNIP"; do
        if [ -f "$TMP/$(basename "$f").cu" ]; then cp -p "$TMP/$(basename "$f").cu" "$f"; else rm -f "$f"; fi
    done
    [ "$HAD_LINK" = 0 ] && rm -f "$LINK"
    exit 1
fi
