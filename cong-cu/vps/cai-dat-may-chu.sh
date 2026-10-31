#!/bin/bash
# ==================================================================
#  CÀI ĐẶT / CẬP NHẬT MÁY CHỦ CHO WEBSITE VIÊN NGỌC PHƯƠNG (chạy bằng root)
#
#  GitHub Actions tự chạy file này sau mỗi lần đưa mã nguồn lên VPS. Chạy lại bao nhiêu lần cũng được:
#  bước nào đã xong thì giữ nguyên, chỉ thay đổi phần khác đi.
#
#  Chỉ tạo / sửa những thứ RIÊNG của website này:
#    - tài khoản Linux "vienngocphuong" (PHP của website chạy bằng tài khoản này)
#    - dịch vụ PHP-FPM riêng "vienngocphuong-php" (không đụng tới php8.3-fpm của website khác)
#    - /etc/nginx/sites-available/vienngocphuong.com + /etc/nginx/snippets/vienngocphuong.conf
#    - khóa mã hóa /etc/vienngocphuong/khoa-bi-mat, lịch chạy /etc/cron.d/vienngocphuong,
#      /etc/logrotate.d/vienngocphuong, /usr/local/sbin/vienngocphuong-ssl
#  Không sửa php.ini / nginx.conf dùng chung, không khởi động lại dịch vụ của website khác
#  (Nginx chỉ "reload" êm khi cấu hình đổi, và chỉ khi "nginx -t" báo ổn).
# ==================================================================
set -euo pipefail
DOMAIN=vienngocphuong.com
U=vienngocphuong
WEB=/var/www/vienngocphuong
PHPV=8.3
SRC=$WEB/cong-cu/vps

[ "$(id -u)" = 0 ] || { echo "Cần chạy bằng root." >&2; exit 1; }
command -v "php-fpm$PHPV" >/dev/null || { echo "Máy chủ thiếu php$PHPV-fpm." >&2; exit 1; }
# Lấy danh sách 1 lần rồi tìm trong đó (không dùng "php -m | grep -q": grep thoát sớm làm php bị ngắt → báo nhầm là thiếu)
MODS=$(php -m)
for m in curl gd mbstring zip dom openssl; do
    grep -qix "$m" <<<"$MODS" || { echo "Máy chủ thiếu tiện ích PHP: $m (cài gói php$PHPV-$m)." >&2; exit 1; }
done

# Chép tệp khi nội dung khác; trả về 0 nếu có thay đổi
put() {   # put <nguồn> <đích> <quyền>
    if cmp -s "$1" "$2"; then return 1; fi
    install -m "$3" "$1" "$2"
    return 0
}

echo "== 1. Tài khoản & thư mục"
id "$U" >/dev/null 2>&1 || useradd --system --user-group --home-dir "$WEB" --no-create-home --shell /usr/sbin/nologin "$U"
install -d -m 755 "$WEB"
for d in data/don-hang data/noi-dung data/quan-tri; do install -d -o "$U" -g "$U" -m 750 "$WEB/$d"; done
for d in assets/img/uploads assets/videos/uploads; do install -d -o "$U" -g "$U" -m 755 "$WEB/$d"; done
install -d -o "$U" -g "$U" -m 700 "/var/lib/$U" "/var/lib/$U/phien" "/var/lib/$U/tam"
install -d -o "$U" -g "$U" -m 750 "/var/log/$U"
install -d -o root -g "$U" -m 750 "/etc/$U"

echo "== 2. Khóa mã hóa kho bí mật (giữ nguyên nếu đã có)"
if [ ! -s "/etc/$U/khoa-bi-mat" ]; then
    (umask 027; openssl rand -hex 32 > "/etc/$U/khoa-bi-mat")
    echo "   đã tạo khóa mới"
fi
chown root:"$U" "/etc/$U/khoa-bi-mat"
chmod 640 "/etc/$U/khoa-bi-mat"

echo "== 3. PHP-FPM riêng (vienngocphuong-php)"
FPM_CHANGED=0
put "$SRC/php-fpm.conf" "/etc/$U/php-fpm.conf" 644 && FPM_CHANGED=1
if put "$SRC/vienngocphuong-php.service" "/etc/systemd/system/vienngocphuong-php.service" 644; then
    systemctl daemon-reload
    FPM_CHANGED=1
fi
"php-fpm$PHPV" -t -y "/etc/$U/php-fpm.conf" 2>&1 | grep -v "test is successful" || true
"php-fpm$PHPV" -t -y "/etc/$U/php-fpm.conf" >/dev/null 2>&1 || { echo "Cấu hình PHP-FPM lỗi – dừng." >&2; exit 1; }
systemctl enable --quiet vienngocphuong-php
if ! systemctl is-active --quiet vienngocphuong-php; then
    systemctl start vienngocphuong-php
    echo "   đã khởi động"
elif [ "$FPM_CHANGED" = 1 ]; then
    systemctl reload vienngocphuong-php
    echo "   đã nạp lại cấu hình"
fi

echo "== 4. Lịch chạy, xoay vòng nhật ký, tự lấy SSL"
put "$SRC/tu-dong-ssl.sh" "/usr/local/sbin/vienngocphuong-ssl" 755 || true
cat > "/tmp/$U.cron" <<EOF
# Website Viên Ngọc Phương – tạo bởi $SRC/cai-dat-may-chu.sh (đừng sửa tay)
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# Sao lưu toàn bộ dữ liệu mỗi đêm lúc 2:10 (gửi lên Cloudflare R2 nếu đã bật trong trang quản trị)
10 2 * * * $U VNP_SECRET_FILE=/etc/$U/khoa-bi-mat /usr/bin/php $WEB/cong-cu/sao-luu-tu-dong.php >> /var/log/$U/sao-luu.log 2>&1
# Tự lấy chứng chỉ SSL khi tên miền đã trỏ về máy chủ (xong rồi thì không làm gì)
*/10 * * * * root /usr/local/sbin/vienngocphuong-ssl >> /var/log/$U/ssl.log 2>&1
EOF
put "/tmp/$U.cron" "/etc/cron.d/$U" 644 || true
rm -f "/tmp/$U.cron"
# Xoay vòng nhật ký (nhật ký Nginx của website đã có logrotate chung của Nginx lo)
cat > "/tmp/$U.logrotate" <<EOF
/var/log/$U/*.log {
    weekly
    rotate 8
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
    su $U $U
}
/var/log/php-fpm-$U.log {
    weekly
    rotate 8
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
}
EOF
put "/tmp/$U.logrotate" "/etc/logrotate.d/$U" 644 || true
rm -f "/tmp/$U.logrotate"

echo "== 5. Nginx"
bash "$SRC/cau-hinh-nginx.sh"

echo "== 6. SSL (tự lấy nếu tên miền đã trỏ về máy chủ)"
/usr/local/sbin/vienngocphuong-ssl || true
[ -s "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ] && echo "   đã có chứng chỉ SSL" || echo "   chưa có chứng chỉ – sẽ tự lấy khi tên miền trỏ về $(ip -4 -o addr show scope global | awk '{print $4}' | cut -d/ -f1 | head -1)"

echo "== 7. Kiểm tra website"
if [ -s "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    BASE="https://$DOMAIN"; RES="--resolve $DOMAIN:443:127.0.0.1"
else
    BASE="http://$DOMAIN"; RES="--resolve $DOMAIN:80:127.0.0.1"
fi
code() { curl -s -o /dev/null -w '%{http_code}' --max-time 20 $RES "$BASE$1" || true; }
sleep 2   # chờ Nginx nạp lại cấu hình (nếu vừa đổi)
FAIL=0
for p in / /san-pham /lien-he /robots.txt /sitemap.xml; do
    c=$(code "$p"); echo "   $p → $c"; [ "$c" = 200 ] || FAIL=1
done
# Trang quản trị: 200 (trang tạo tài khoản / đăng nhập) hoặc 303 (đã có tài khoản → chuyển sang đăng nhập)
c=$(code "/quan-tri/"); echo "   /quan-tri/ → $c"; [ "$c" = 200 ] || [ "$c" = 303 ] || FAIL=1
for p in /data/don-hang/don-hang.json /data/quan-tri/tai-khoan.json /includes/init.php /config.php /quan-tri/lib.php /cong-cu/vps/cai-dat-may-chu.sh /.git/config; do
    c=$(code "$p"); [ "$c" = 403 ] || [ "$c" = 404 ] || { echo "   NGUY HIỂM: $p mở được ($c)"; FAIL=1; }
done
[ "$FAIL" = 0 ] && echo "   Website chạy tốt, dữ liệu được bảo vệ." || { echo "Có lỗi – xem chi tiết ở trên." >&2; exit 1; }
