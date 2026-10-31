# Hướng dẫn website Viên Ngọc Phương

Website viết bằng PHP thuần, **không cần cơ sở dữ liệu**. Chạy được trên hầu hết hosting cPanel/DirectAdmin tại Việt Nam.

**Website đang chạy tại https://vienngocphuong.com** (VPS riêng, cập nhật tự động qua GitHub – xem mục 1).

**Yêu cầu máy chủ:** PHP 8.0 trở lên (nên dùng 8.1+) với các tiện ích curl, gd, mbstring, zip, dom; máy chủ Nginx (VPS – đã có sẵn cấu hình trong `cong-cu/vps/`) hoặc Apache / LiteSpeed (hosting cPanel – dùng file `.htaccess`).

---

## 0. Trang quản trị – sửa mọi thứ mà không cần đụng vào file

Mở **https://vienngocphuong.com/quan-tri/** (chạy thử trên máy: http://localhost:8000/quan-tri/). Tài khoản đã tạo khi chạy thử trên máy đã được chuyển lên website thật – đăng nhập bằng đúng tên và mật khẩu đó.

**Lần đầu:** trang yêu cầu tạo tài khoản Quản lý (tên hiển thị, tên đăng nhập, mật khẩu ≥ 8 ký tự).
- Chạy thử trên máy: tạo ngay.
- Trên hosting / VPS: cần **mã cài đặt** trong file `data/quan-tri/ma-cai-dat.txt` (hosting: cPanel → File Manager; VPS: `cat /var/www/vienngocphuong/data/quan-tri/ma-cai-dat.txt`). Để người lạ không tạo tài khoản trước bạn.
- Nếu đã tạo tài khoản khi chạy thử trên máy, tải thư mục `data/` lên hosting là dùng được luôn tài khoản đó.

**Các mục trong trang quản trị**

| Nhóm | Làm được gì |
|---|---|
| Tổng quan | Đơn cần xử lý, giữ hàng, lịch hẹn, tin nhắn mới, doanh thu tháng; danh sách **việc cần làm** và **cài đặt còn thiếu** (thông tin mẫu, giá mô phỏng, chưa bật Telegram…) |
| Đơn hàng | Lọc theo trạng thái / tháng, tìm theo mã – tên – số điện thoại, tải Excel. Trong từng đơn: **Xác nhận đã nhận tiền**, **Xác nhận đơn COD**, **Đang giao**, **Hoàn tất** (tự chuyển món độc bản sang “Đã có chủ”), **Hủy đơn**, ghi chú nội bộ, sao chép link gửi khách |
| Giữ hàng – đặt cọc | Xác nhận đã nhận cọc, gia hạn 1 lần, **Khách đã mua** (món chuyển “Đã có chủ”), hủy giữ hàng |
| Phiếu xác nhận | Xem mọi phiếu đã phát; đổi trạng thái: đã hoàn tất mua / đã hoàn tiền / chuyển thành phiếu mua hàng / đã hủy (khách mở link phiếu sẽ thấy) |
| Lịch hẹn, Tin nhắn liên hệ | Danh sách từ trang Đặt lịch / Liên hệ, đánh dấu đã gọi, đã đến, đã trả lời… |
| Giao dịch & đăng ký | Tiền vào do Casso/SePay báo về, email đăng ký nhận tin, tải các file Excel gốc theo tháng |
| Sản phẩm | Thêm / sửa / nhân bản / ẩn / xóa; tải nhiều ảnh (tự thu nhỏ), kéo thả đổi thứ tự ảnh; video (YouTube hoặc tải MP4); ni vòng, ni nhẫn, mệnh, dịp, giấy kiểm định; sắp xếp thứ tự hiển thị; đánh dấu nhanh “Đã có chủ” |
| Danh mục, Giấy kiểm định, Bộ sưu tập quà tặng | Thêm / sửa / sắp xếp; giấy kiểm định gắn thẳng với sản phẩm |
| Banner trang chủ | Ảnh máy tính 1920×942 + ảnh dọc điện thoại 1080×1350, link khi bấm, bật/tắt, sắp xếp |
| Cẩm nang, Chính sách | Trình soạn thảo có tiêu đề, đậm, danh sách, liên kết, ảnh, bảng; bài viết có bản nháp |
| Khách đeo thật | Ảnh & cảm nhận khách (mục trên trang chủ tự hiện khi có) |
| Chữ trên các trang | Tiêu đề, đoạn giới thiệu, cam kết… của trang chủ, Giới thiệu, chân trang, trang sản phẩm, giỏ hàng và các trang khác |
| Ngũ hành & quà tặng | Gợi ý màu ngọc theo mệnh, các khoảng giá của bộ lọc, người nhận trong “Chọn quà giúp tôi” |
| Cài đặt cửa hàng | Thông tin cửa hàng, logo, pháp lý, link Bộ Công Thương · **ngân hàng nhận tiền** (chọn trong danh sách, có mã QR thử) · COD bật/tắt · **Casso hoặc SePay** · **VAT** (giá đã gồm / cộng thêm) · phí giao hàng, dịch vụ quà tặng · các con số giữ hàng – đơn hàng · điều khoản trên phiếu · Telegram (có nút tìm Chat ID, gửi thử) · **email gửi đi qua SMTP** (Gmail / email tên miền, có nút gửi thử) · khung giờ đặt lịch · mạng xã hội, mã quảng cáo · **lưu trữ Cloudflare R2** (Account ID, Access Key ID, Secret Access Key, Bucket – đổi lúc nào cũng được, có nút kiểm tra kết nối) · tên miền |
| Tài khoản | Đổi mật khẩu; Quản lý thêm tài khoản **Nhân viên bán hàng** (chỉ xử lý đơn, giữ hàng, phiếu, lịch hẹn, tin nhắn), khóa / đặt lại mật khẩu |
| Sao lưu & khôi phục | Tải 1 file .zip toàn bộ dữ liệu, khôi phục từ file; **máy chủ tự sao lưu mỗi đêm** (giữ 7 bản, tải về được) và gửi lên Cloudflare R2 nếu đã kết nối; **lịch sử thay đổi** từng mục – lỡ tay xóa/sửa sai thì quay lại bản cũ |
| Nhật ký thao tác | Ai làm gì, lúc nào |
| Hướng dẫn & tài liệu | Tài liệu **Chức năng website** và **Hướng dẫn sử dụng trang quản trị** ngay trong trang quản trị: mục lục, tìm kiếm, in / lưu PDF. Mỗi trang quản trị có nút **Hướng dẫn** mở đúng phần liên quan. Quản lý sửa được nội dung (con số tự lấy theo Cài đặt), thêm tài liệu riêng của cửa hàng; nhân viên chỉ xem. Nội dung gốc: `includes/tai-lieu-mac-dinh.php` |

**Dữ liệu lưu ở đâu:** nội dung đã sửa trong trang quản trị nằm ở `data/noi-dung/` (file `.json`), tài khoản & nhật ký ở `data/quan-tri/`, ảnh tải lên ở `assets/img/uploads/`. Mục nào **chưa từng sửa** trong trang quản trị thì website vẫn đọc file mẫu `data/*.php` / `config.php` như cũ; **đã sửa** thì file mẫu không còn được dùng cho mục đó.

> ⚠️ Các thư mục `data/don-hang/`, `data/noi-dung/`, `data/quan-tri/`, `assets/img/uploads/`, `assets/videos/uploads/` là **dữ liệu của bạn** (đơn hàng, nội dung, tài khoản, ảnh). Chúng không nằm trên GitHub và việc cập nhật tự động không bao giờ ghi đè / xóa chúng. Nếu tự tải mã nguồn lên hosting bằng tay, cũng **đừng ghi đè** các thư mục này.

**Bảo mật:** đăng nhập sai 5 lần (theo máy) hoặc 10 lần (theo tên đăng nhập) bị khóa 15 phút; không thao tác 8 giờ phải đăng nhập lại (trừ khi chọn “Ghi nhớ đăng nhập” 30 ngày). Quên mật khẩu: nhờ tài khoản Quản lý khác đặt lại, hoặc xóa file `data/quan-tri/tai-khoan.json` qua cPanel rồi tạo tài khoản mới (đơn hàng, sản phẩm không bị ảnh hưởng).

**Hosting giới hạn kích thước tải lên** (thường 2–64 MB): ảnh được tự thu nhỏ trên trình duyệt trước khi gửi nên hầu như không vướng; video lớn nên đăng YouTube rồi dán link.

---

## 1. Website trên VPS & cập nhật qua GitHub

**Đang chạy:** https://vienngocphuong.com – VPS `180.93.117.186` (Ubuntu 24.04, Nginx, PHP 8.3). Trên VPS còn các dự án khác; website này được cài **tách riêng** để không ảnh hưởng tới chúng:

| Thành phần | Ở đâu |
|---|---|
| Mã nguồn | `/var/www/vienngocphuong` (thuộc root – website không tự sửa được mã của nó) |
| Dữ liệu (đơn hàng, nội dung, tài khoản, ảnh tải lên) | `data/don-hang`, `data/noi-dung`, `data/quan-tri`, `assets/img/uploads`, `assets/videos/uploads` trong thư mục trên (thuộc tài khoản `vienngocphuong`) |
| PHP riêng | dịch vụ `vienngocphuong-php` (cấu hình `/etc/vienngocphuong/php-fpm.conf`) – không dùng chung `php8.3-fpm` của dự án khác |
| Nginx | `/etc/nginx/sites-available/vienngocphuong.com` + `/etc/nginx/snippets/vienngocphuong.conf` (tạo từ `cong-cu/vps/nginx-quy-tac.conf`) |
| SSL | Let's Encrypt, tự gia hạn (certbot.timer của máy chủ) |
| Sao lưu tự động | 2:10 sáng mỗi ngày → `data/quan-tri/sao-luu/` (7 bản) + Cloudflare R2 nếu đã bật |
| Khóa giải mã mật khẩu SMTP / khóa R2 | `/etc/vienngocphuong/khoa-bi-mat` (nằm ngoài thư mục website, không có trong bản sao lưu) |
| Nhật ký | `/var/log/vienngocphuong/` (lỗi PHP, sao lưu, SSL), `/var/log/nginx/vienngocphuong.*.log` |

**Tên miền** (quản lý DNS ở Nhân Hòa): 2 bản ghi A – `@` và `www` → `180.93.117.186`. Mở `http://`, `www.` đều tự chuyển về `https://vienngocphuong.com`.

**Cập nhật website (sửa giao diện, thêm tính năng):**
1. Sửa mã nguồn trên máy → commit → push lên nhánh `main` của repo GitHub `quangdaicongviec-dot/website-vien-ngoc-phuong`.
2. GitHub Actions tự chạy (tab **Actions** của repo, khoảng 1–2 phút): kiểm tra lỗi cú pháp → đưa mã nguồn lên VPS → cài đặt / kiểm tra → báo ✅ hoặc ❌.
3. Dữ liệu trên VPS giữ nguyên. Danh sách không đưa lên: `cong-cu/vps/khong-dua-len.txt`.

Workflow dùng các Secrets đã có của repo: `SSH_HOST`, `SSH_PORT`, `SSH_USERNAME`, `SSH_PASSWORD` (Settings → Secrets and variables → Actions). **Đổi mật khẩu root của VPS thì nhớ sửa `SSH_PASSWORD`.**

**Lệnh hữu ích trên VPS** (đăng nhập SSH bằng root):
- Cài lại / kiểm tra toàn bộ: `bash /var/www/vienngocphuong/cong-cu/vps/cai-dat-may-chu.sh` (chạy lại bao nhiêu lần cũng được; cuối cùng in kết quả kiểm tra website).
- Trạng thái PHP của website: `systemctl status vienngocphuong-php` · khởi động lại (chỉ website này): `systemctl restart vienngocphuong-php`.
- Xem lỗi PHP: `tail -50 /var/log/vienngocphuong/php-loi.log`.
- Sao lưu ngay: `runuser -u vienngocphuong -- env VNP_SECRET_FILE=/etc/vienngocphuong/khoa-bi-mat php /var/www/vienngocphuong/cong-cu/sao-luu-tu-dong.php`.
- Quên mật khẩu quản trị và không còn tài khoản Quản lý nào khác: xóa `data/quan-tri/tai-khoan.json` rồi mở `/quan-tri/` tạo tài khoản mới bằng mã trong `data/quan-tri/ma-cai-dat.txt`.

**Chuyển sang VPS khác:** chạy `cai-dat-may-chu.sh` trên máy mới (cần Nginx, PHP 8.3-FPM, certbot), chép thư mục dữ liệu ở trên và file `/etc/vienngocphuong/khoa-bi-mat` (không chép khóa thì chỉ cần nhập lại mật khẩu SMTP, khóa R2 trong trang quản trị).

### Email gửi đi (SMTP)

VPS không tự gửi được email. Vào **Cài đặt → Thông báo đơn mới → Máy chủ gửi thư (SMTP)**. Ví dụ dùng Gmail:
1. Tài khoản Google → Bảo mật → bật **Xác minh 2 bước**.
2. Mở **myaccount.google.com/apppasswords** → tạo mật khẩu ứng dụng (đặt tên "Website") → nhận 16 ký tự.
3. Điền: Máy chủ `smtp.gmail.com` · Cổng `587` · Mã hóa `TLS` · Tên đăng nhập = địa chỉ Gmail · Mật khẩu = 16 ký tự vừa tạo · "Email gửi đi" = chính địa chỉ Gmail đó → **Lưu** → **Gửi email thử**.

Mật khẩu SMTP được lưu **mã hóa**. Gmail cá nhân gửi tối đa khoảng 500 thư/ngày – dư cho cửa hàng.

### Lưu trữ Cloudflare R2 (ảnh, video, bản sao lưu)

Vào **Cài đặt → Lưu trữ Cloudflare R2** (có hướng dẫn từng bước ngay trên trang):
- Nhập **Account ID, Access Key ID, Secret Access Key, tên Bucket** → Lưu → website tự kiểm tra kết nối và báo đúng / sai chỗ nào. Đổi khóa lúc nào cũng được, không cần sửa code hay VPS – website đọc lại thông tin mỗi lần tải tệp lên R2. Các khóa được lưu **mã hóa** (khóa giải mã nằm ngoài thư mục website).
- **Tự đưa ảnh / video mới tải lên vào R2** – VPS vẫn giữ 1 bản dự phòng. Nút **Đưa ảnh / video cũ lên R2** cho các tệp tải lên trước khi kết nối.
- **Khách xem ảnh / video từ R2** – cần “Địa chỉ công khai” của bucket (bucket → Settings → Public access). Địa chỉ `….r2.dev` Cloudflare chỉ khuyên dùng để thử; dùng lâu dài nên gắn tên miền riêng (VD `cdn.vienngocphuong.com` – cần chuyển DNS tên miền sang Cloudflare).
- **Sao lưu lên R2 mỗi đêm** – bản sao lưu nằm trong thư mục `sao-luu-<mã ngẫu nhiên>/` của bucket (khó đoán đường dẫn, kể cả khi bucket để công khai). R2 giữ 31 bản (mỗi ngày trong tháng 1 bản).

---

## 1b. (Dự phòng) Đưa website lên hosting cPanel

1. Nén **toàn bộ thư mục** thành file `.zip`. Có thể bỏ ra ngoài các file không cần thiết: `DESIGNMAU.md`, `SKILLMAU.md`, `HUONG-DAN.md`, file logo gốc `Logo Ngọc Phương xanh ngọc vàng sang trọng.png` thư mục `anh-goc/` (ảnh gốc dung lượng lớn) `tai-lieu/` (tài liệu giới thiệu tính năng) và `cong-cu/` (công cụ xuất bản chạy thử).
   > Trên macOS, file `.htaccess` bị ẩn. Hãy nén bằng Terminal để không bị sót: `cd "đường-dẫn-thư-mục" && zip -r ../website.zip . -x "*.DS_Store"`
2. Đăng nhập cPanel → **File Manager** → mở thư mục `public_html`.
3. Bấm **Upload**, chọn file `.zip`, rồi bấm chuột phải vào file → **Extract**.
4. Kiểm tra trong `public_html` đã có `index.php`, `.htaccess`, các thư mục `assets`, `data`, `includes`. (Bật "Show Hidden Files" trong Settings của File Manager để thấy `.htaccess`.)
5. Bật SSL (cPanel → **SSL/TLS Status** → Run AutoSSL). Sau khi có https, mở file `.htaccess`, bỏ dấu `#` ở 2 dòng `RewriteCond %{HTTPS} off` và `RewriteRule ^ https://...` để luôn chạy https.
6. Mở `config.php`, điền `'site_url' => 'https://tenmiencuaban.vn'` (không có dấu `/` ở cuối).

**Nếu bấm vào sản phẩm bị lỗi 404:** hosting không hỗ trợ đường dẫn đẹp. Mở `config.php`, đổi `'pretty_urls' => true` thành `false`.

**Nếu đặt website trong thư mục con** (ví dụ `tenmien.vn/shop/`): trong `.htaccess`, bỏ `#` ở dòng `RewriteBase /shop/` và sửa `ErrorDocument 404 /shop/404.php`.

---

## 2. Sửa thông tin cửa hàng — `config.php`

> **Cách dễ hơn:** sửa trong **Trang quản trị → Cài đặt cửa hàng** (mục 0). Phần dưới đây dành cho người muốn sửa trực tiếp file.

Mọi thông tin hiển thị chung đều nằm ở đây: tên, hotline, Zalo, email, địa chỉ, giờ làm việc, link Facebook/TikTok/Shopee, tên công ty, mã số thuế, tài khoản ngân hàng.

- Giữ nguyên dấu nháy đơn `' '` bao quanh giá trị.
- Để trống `''` thì mục đó tự ẩn (ví dụ chưa có Messenger thì nút Messenger không hiện).
- Bản đồ: Google Maps → Chia sẻ → **Nhúng bản đồ** → copy phần link nằm trong `src="..."` dán vào `map_embed`.

> **Lưu ý khi sửa file PHP:** chỉ sửa phần chữ bên trong dấu nháy. Nếu lỡ xóa dấu `,` hoặc `'` website sẽ báo lỗi trắng trang — mở lại file và kiểm tra dòng vừa sửa.

---

## 3. Sản phẩm — `data/products.php`

> **Cách dễ hơn:** sửa trong **Trang quản trị → Sản phẩm** (mục 0). Phần dưới đây dành cho người muốn sửa trực tiếp file.

Mỗi sản phẩm là một khối `[ ... ],`. Muốn thêm: sao chép nguyên một khối, dán xuống dưới và sửa. Ý nghĩa từng mục được ghi ngay đầu file.

Quan trọng:
- `slug` là đường dẫn sản phẩm: **chữ thường, không dấu, nối bằng dấu `-`**, không trùng nhau. Ví dụ `vong-ngoc-xanh-luc-56`.
- `price` ghi số liền, không dấu chấm: `12500000`. Ghi `0` để hiện "Liên hệ".
- `stock`: `'con-hang'`, `'het-hang'` (ẩn nút mua), `'dat-truoc'` hoặc **`'da-ban'`** (đã có chủ).
  Món `'da-ban'` vẫn hiển thị để khách tham khảo (xếp cuối danh sách), nút mua đổi thành **"Đặt mẫu tương tự"**. Đừng xóa món đã bán – nó cho khách thấy cửa hàng bán được nhiều.
- `featured => true` để hiện ở "Sản phẩm nổi bật" trang chủ; `new => true` để hiện ở "Sản phẩm mới".
- `doc_ban => true`: món **độc bản** – hiện nhãn "Độc bản", mỗi đơn chỉ mua được 1 chiếc.
- `ni => 56`: (chỉ vòng tay) đường kính trong, mm – dùng cho bộ lọc **"Tìm vòng vừa tay"** và công cụ tính ni.
- `ni_nhan => [10, 18]`: (chỉ nhẫn) các ni nhẫn làm/điều chỉnh được, [nhỏ nhất, lớn nhất]; nhẫn 1 ni ghi `[14, 14]` – dùng cho ô **"Tìm nhẫn vừa tay"** (trang Nhẫn ngọc) và tab **"Chọn ni nhẫn"** trên trang sản phẩm. Khách nhập chu vi ngón tay (mm) hoặc đường kính lòng nhẫn cũ (mm); web quy đổi theo bảng ni Việt Nam (ni 12 ≈ chu vi 52,4 mm, mỗi ni hơn kém ~1 mm). Chuỗi hạt có tab **"Chọn cỡ chuỗi"** (đo cổ tay).
- `dip => ['tang-me', 'mung-tho']`: gắn sản phẩm vào **bộ sưu tập quà tặng theo dịp** (xem mục 4).
  Mã dịp hợp lệ: `trao-dau`, `mung-tho`, `tang-me`, `cho-be`, `tan-gia`, `nguoi-thuong` – gõ sai mã thì sản phẩm sẽ không hiện ở dịp đó.

> **Để sản phẩm mới được "Chọn quà giúp tôi" gợi ý đúng**, cần điền đủ 3 mục:
> 1. `dip` – quyết định món quà hợp với ai (mẹ, người thương, mừng thọ…). Thiếu mục này, sản phẩm chỉ được gợi ý khi khách chọn "Bản thân".
> 2. `menh` – món hợp mệnh người nhận được xếp lên trước; món có màu **kỵ** mệnh người nhận bị loại. **Phần tử đầu tiên phải là hành của màu ngọc** (xanh lá → `moc`, trắng → `kim`, tím/đỏ/hồng → `hoa`, vàng/nâu → `tho`, đen/xanh dương → `thuy`).
> 3. `price` – khi khách chọn ngân sách, chỉ món có giá nằm trong khoảng đó được gợi ý. Món để giá `0` ("Liên hệ") không lọt vào khoảng giá nào; trang gợi ý sẽ báo "Còn X mẫu giá liên hệ" kèm link xem.
- `cert => 'VNP-2610-0001'`: mã **giấy kiểm định** (khai báo trong `data/certificates.php`).
- `video => 'videos/ten-video.mp4'` hoặc link YouTube: **video thật** hiện cạnh ảnh sản phẩm.

### Video sản phẩm
- Quay 10–15 giây: xoay ngọc chậm dưới ánh sáng tự nhiên, rồi soi đèn pin để thấy độ trong.
- Xuất file **.mp4**, khung vuông hoặc dọc, 720p, dưới khoảng 8 MB. Tải vào `assets/videos/` và ghi `'video' => 'videos/ten-file.mp4'`.
- Hoặc đăng lên YouTube (có thể để chế độ "Không công khai") rồi dán link: `'video' => 'https://youtu.be/xxxxxxxxxxx'`.
- Video hiện có (`vong-ngoc-xanh-luc-mau.mp4`) chỉ là **video mẫu** dựng từ hình minh họa – hãy thay bằng video thật.

### Ảnh sản phẩm
Ảnh hiện tại là **hình minh họa** (file `.svg`) — hãy thay bằng ảnh chụp thật (Google Shopping cũng **không nhận ảnh .svg**):
1. Chuẩn bị ảnh **vuông** (ví dụ 1000×1000 px), dạng `.jpg` hoặc `.webp`, dung lượng dưới ~300 KB (nén tại squoosh.app hoặc tinypng.com).
2. Tải ảnh vào thư mục `assets/img/products/`.
3. Sửa mục `images` của sản phẩm, ví dụ: `'images' => ['products/vong-xanh-luc-1.jpg', 'products/vong-xanh-luc-2.jpg'],` — ảnh đầu tiên là ảnh đại diện, ảnh thứ hai hiện khi rê chuột.

### Chụp ảnh sản phẩm đồng bộ (quan trọng nhất để website trông cao cấp)
- **Cùng một nền** cho mọi sản phẩm: giấy/vải màu kem hoặc trắng ngà cho ảnh 1; nền xanh rêu đậm hoặc đá tối cho ảnh 2.
- **Ánh sáng tự nhiên** gần cửa sổ (tránh nắng gắt), thêm tấm hắt sáng trắng phía đối diện để ngọc trong và không bị bóng gắt.
- **Cùng góc chụp và khoảng cách**: vòng chụp nghiêng khoảng 30°; mặt dây treo thẳng; sản phẩm chiếm khoảng 65% khung hình, đặt chính giữa.
- **Ảnh vuông 1000×1000 px**, không chèn chữ hay logo lên ảnh.
- Ảnh thứ 2 nên là ảnh **đeo trên tay/cổ** hoặc soi đèn – ảnh này hiện khi khách rê chuột.
- Không chỉnh màu quá tay: ngọc phải giống thật để khách tin tưởng.

---

## 4. Các nội dung khác

| Nội dung | File |
|---|---|
| Danh mục sản phẩm | `data/categories.php` |
| Banner trượt trang chủ | `data/banners.php` — 2 kiểu: **ảnh nguyên tấm** thiết kế sẵn (Canva…; các banner ảnh phải **cùng kích thước**, ví dụ 1672×941 hoặc 1920×1080; banner rộng hết màn hình và luôn hiện trọn ảnh) hoặc **chữ + hình minh họa**. **Ảnh dọc cho điện thoại** (ô `image_mobile`, cỡ 1080×1350): chỉ hiện trên điện thoại cầm dọc, máy tính không đổi. Khi banner đầu tiên có ảnh dọc, banner ảnh nào chưa có ảnh dọc sẽ **tạm ẩn trên điện thoại** (thêm ảnh dọc là tự hiện lại); banner chữ tự xếp dọc. Thời gian mỗi slide: số `DELAY` trong `assets/js/main.js` |
| Bài viết Cẩm nang | `data/articles.php` |
| Chính sách bảo hành, đổi trả, vận chuyển, thanh toán, bảo mật | `data/policies.php` |
| **Khách đeo thật** – ảnh & cảm nhận khách (tự ẩn khi trống) | `data/feedback.php` — ảnh dọc 4:5 đặt trong `assets/img/khach-hang/`. Chỉ đăng ảnh/cảm nhận thật, đã được khách đồng ý |
| **Bộ sưu tập quà tặng theo dịp** | `data/collections.php` (tên, mô tả, ảnh). Gắn sản phẩm bằng mục `dip` trong `data/products.php` |
| **Giấy kiểm định** (trang Tra cứu) | `data/certificates.php` + ảnh scan giấy trong `assets/img/certificates/`. ⚠️ 4 mã hiện có là **dữ liệu mẫu** – xóa và thay bằng giấy thật |
| Khung giờ **đặt lịch xem ngọc** | `config.php` → `appointment_slots`, `appointment_days` |
| **Giữ hàng / đặt cọc** | `config.php` → `hold_min_price` (giá tối thiểu), `hold_pending_minutes` (thời gian chờ cọc), `hold_hours` (giữ sau khi nhận cọc), `hold_deposit` (% tiền cọc) – xem quy trình ở mục 5 |
| **Dịch vụ quà tặng** khi đặt hàng | `config.php` → `gift_options` (thiệp, hộp quà, giao hỏa tốc + phí) |
| Trang Giới thiệu | `gioi-thieu.php` |
| Màu sắc, kiểu chữ | `assets/css/style.css` (các biến màu ở đầu file) |
| **Hoạ tiết hoa sen** (nhận diện thương hiệu) | `assets/css/brand-lotus.css` + hình trong `assets/img/brand/`. Muốn gỡ: xóa dòng nạp `brand-lotus.css` trong `includes/header.php` |

**Hãy đọc lại `data/policies.php`** và sửa cho đúng với cách cửa hàng bạn thực sự làm (thời gian đổi trả, phí ship, bảo hành…).

---

## 5. Nhận đơn hàng

Khi khách đặt hàng, giữ hàng, đặt lịch hoặc gửi liên hệ:
1. **Báo ngay qua Telegram** (nếu đã cài – xem bên dưới). Đây là cách chắc chắn nhất để không bỏ sót đơn.
2. **Email** được gửi tới địa chỉ `order_email` trong `config.php`. Khách có nhập email sẽ nhận được email xác nhận.
3. **Lưu vào file Excel (CSV)** trong thư mục `data/don-hang/`:
   - `don-hang-2026-10.csv` — đơn hàng theo tháng (kèm dịch vụ quà tặng, lời nhắn thiệp)
   - `giu-hang-…csv` — yêu cầu giữ hàng / đặt cọc
   - `giao-dich-…csv` — tiền vào tài khoản (khi đã cài Casso)
   - `dat-lich-…csv` — lịch hẹn xem ngọc
   - `lien-he-…csv` — tin nhắn liên hệ
   - ⚠️ **Đừng mở trực tiếp các file CSV này bằng Excel / WPS trong lúc website đang chạy trên máy** – chương trình sẽ khóa file. Hãy sao chép file ra chỗ khác rồi mở bản sao (trên hosting: tải file về máy rồi mở). Nếu file đang bị khóa, website ghi tạm đơn vào file `…-ghi-tam.csv` bên cạnh (không mất đơn) – nhớ xem cả file này.

### Trang giỏ hàng & đặt hàng
- Máy tính: cột trái là sản phẩm + thông tin nhận hàng, cột phải là **tóm tắt đơn + nút Đặt hàng luôn hiện** khi cuộn.
- Điện thoại: **thanh "Tổng cộng + Đặt hàng" luôn ở đáy màn hình** (tự ẩn khi nút Đặt hàng chính đang hiện).
- Chỉ các ô bắt buộc (họ tên, số điện thoại, địa chỉ) hiện sẵn; email/ghi chú và dịch vụ quà tặng thu gọn – bấm để mở.
- **Hình thức thanh toán không chọn sẵn** (khi có tài khoản ngân hàng): khách phải tự chọn COD hay chuyển khoản; chưa chọn mà bấm Đặt hàng → trang cuộn tới phần thanh toán và báo rõ. Lựa chọn hiện ở ô tóm tắt và thanh đáy màn hình. Chưa cài tài khoản ngân hàng thì chỉ có COD và được chọn sẵn.
- Trang giỏ hàng, đặt hàng, giữ hàng dùng **chân trang gọn** (một dòng hotline + chính sách). Muốn trang khác cũng dùng chân trang gọn: thêm dòng `$mini_footer = true;` ở đầu file trang đó.

### Đơn hàng chuyển khoản
- Sau khi đặt, khách thấy **"Đã nhận đơn hàng – vui lòng chuyển khoản"** (trạng thái *Chờ thanh toán*) kèm mã QR. Đơn COD vẫn hiện "Đặt hàng thành công".
- **Món độc bản** trong đơn chuyển khoản được **giữ riêng `order_pending_minutes` phút** (mặc định 60) chờ tiền – khách khác không đặt trùng được. Hết giờ chưa có tiền → tự mở bán lại.
- Tiền về đủ → trang của khách **tự chuyển sang "Đã nhận thanh toán"** (tự động qua Casso, hoặc cửa hàng bấm **"Xác nhận đã nhận đủ tiền"** trong **link quản lý đơn** gửi qua Telegram/email). Món độc bản tiếp tục được giữ `order_paid_hold_days` ngày – hãy đổi `'stock'` thành `'da-ban'` sau khi giao.
- Chuyển thiếu → trang khách báo số tiền còn thiếu, cửa hàng nhận cảnh báo.
- Khách chuyển muộn khi món đã có người khác đặt → trang khách báo cửa hàng sẽ liên hệ đổi mẫu / hoàn tiền, cửa hàng nhận cảnh báo.
- Link quản lý đơn có nút **Hủy đơn** (mở bán lại món độc bản ngay).
- **Đơn COD có món độc bản**: món được giữ riêng cho khách tối đa `order_cod_hours` giờ (mặc định 24). Sau khi gọi khách, mở **link quản lý đơn** → bấm **"Xác nhận đơn (đã gọi khách)"** → món tiếp tục được giữ tới khi giao (`order_paid_hold_days` ngày). Quá giờ chưa xác nhận → món tự mở bán lại (vẫn bấm xác nhận muộn được nếu món chưa có người khác đặt).

### Quy trình giữ hàng – đặt cọc
1. Khách bấm **"Giữ hàng"** trên sản phẩm (từ giá `hold_min_price`), điền tên + số điện thoại (email không bắt buộc – để nhận phiếu) và **đánh dấu đồng ý "Chính sách đặt cọc – giữ hàng"** → món ngọc được **giữ tạm** `hold_pending_minutes` phút (mặc định 60) và khách thấy **mã QR chuyển cọc** (cần điền mục `bank` trong `config.php`). Website lưu lại thời điểm và phiên bản chính sách khách đã đồng ý.
2. Shop nhận tin báo (Telegram / email) kèm **link quản lý**. Khi thấy tiền cọc với đúng nội dung (mã `GIU…`) về tài khoản, mở link và bấm **"Xác nhận đã nhận cọc"** → món được **giữ chính thức** `hold_hours` giờ (mặc định 24), trang của khách tự chuyển sang "Đã nhận cọc".
3. Nếu khách không chuyển cọc, hết thời gian giữ tạm món ngọc **tự mở bán lại**. Muốn mở bán ngay: bấm **"Hủy giữ hàng"** trong link quản lý.
4. Khách báo trước khi hết hạn và muốn thêm thời gian: mở link quản lý → **"Gia hạn thêm 24 giờ"** (`hold_extend_hours`; mỗi lượt giữ chỉ gia hạn **1 lần**, đúng như chính sách). Phiếu đặt cọc của khách tự cập nhật hạn giữ mới.
- Link quản lý có mã bảo mật riêng cho từng lượt giữ – **đừng chia sẻ** link này cho khách. Mã được tạo từ file `data/don-hang/.secret` (tự sinh, đã khóa không cho truy cập từ ngoài) – đừng xóa file này, nếu không các link quản lý cũ sẽ hết hiệu lực.
- Mỗi số điện thoại chỉ có 1 yêu cầu chờ cọc cùng lúc (chống giữ hàng ảo).
- Cần báo Telegram hoặc email hoạt động để nhận link quản lý. Nếu lỡ mất link: xem mã `GIU…` trong file `giu-hang-…csv`, rồi xóa khối tương ứng trong `data/don-hang/giu-hang.json` để mở bán lại.
**Khi khách đã mua món độc bản:** đổi `'stock'` của sản phẩm thành `'da-ban'`.

### Phiếu xác nhận cho khách (bằng chứng đã đặt cọc / thanh toán)
Website **tự lập phiếu** – khách không cần chờ cửa hàng gửi:
| Khi nào | Phiếu |
|---|---|
| Cọc giữ hàng được xác nhận (tự động hoặc cửa hàng bấm) | **Phiếu xác nhận đặt cọc** – ghi rõ giữ hàng đến lúc nào, tiền cọc, phần còn lại, điều khoản cọc |
| Đơn chuyển khoản đã nhận đủ tiền | **Phiếu xác nhận thanh toán** |
| Cửa hàng bấm "Xác nhận đơn" cho đơn COD | **Phiếu xác nhận đơn hàng** – số tiền khách trả khi nhận hàng |

- **Nút "Xem phiếu xác nhận" có ngay sau khi khách đặt đơn / gửi giữ hàng.** Cửa hàng chưa xác nhận → link mở trang trạng thái (Chờ thanh toán / Chờ cửa hàng xác nhận / Chờ chuyển cọc) và **tự chuyển thành phiếu** khi cửa hàng xác nhận; đã xác nhận → mở phiếu luôn. Mỗi đơn chỉ có **một link phiếu cố định**.
- Khách nhận phiếu qua: nút **"Xem & tải phiếu…"** trên trang đơn hàng / giữ hàng, và **email** (nếu khách để lại email). Tin báo Telegram cũng kèm **link phiếu** – **dán link này gửi khách qua Zalo** (đa số khách không để lại email). Link phiếu cũng có trong link quản lý (nút "Mở phiếu" / "Sao chép link").
- Trên trang phiếu khách bấm **Tải PDF**, **Lưu ảnh** hoặc **In phiếu** – chạy ngay trên điện thoại, không cần cài thêm gì trên hosting.
- Phiếu có **mã QR kiểm tra phiếu thật**: quét ra trang trên website hiện đúng dữ liệu cửa hàng đã lưu → ai sửa ảnh/PDF phiếu cũng bị phát hiện. Link phiếu có mã bảo mật riêng (khác link quản lý – khách không mở được trang quản lý).
- Phiếu in: tên & mã số thuế cửa hàng, số phiếu, khách hàng (số điện thoại và địa chỉ được che bớt), sản phẩm kèm màu/kích thước/chất liệu và **mã giấy kiểm định**, số tiền (kèm **bằng chữ**), tài khoản nhận, mã giao dịch ngân hàng (khi có Casso), điều khoản, dòng "không thay thế hóa đơn".
- Đơn / lượt giữ bị hủy → phiếu vẫn còn (là bằng chứng khách đã trả tiền) nhưng hiện **"ĐÃ HỦY"**.
- Phiếu lưu vĩnh viễn trong `data/don-hang/phieu/` (mỗi phiếu 1 file) – dùng để đối chiếu khi bảo hành, đổi trả. **Đừng xóa thư mục này.**
- Sửa nội dung điều khoản in trên phiếu: `receipt_terms` và `receipt_note` trong `config.php`. Phiếu đã lập giữ nguyên điều khoản lúc lập.
- **Chính sách đặt cọc – giữ hàng** (trang Chính sách): các con số (giá áp dụng, % cọc, số giờ giữ, gia hạn, số tháng phiếu mua hàng, số ngày hoàn tiền) tự lấy từ `config.php`. Khi đổi nội dung chính sách, tăng `deposit_policy_version`.
- "Phiếu xác nhận" **không phải hóa đơn**. Hóa đơn điện tử (khi pháp luật yêu cầu hoặc khách cần) xuất qua phần mềm hóa đơn / kế toán như bình thường.

### Khách xem lại đơn & nhận phiếu khi đã rời website
- **Trang "Tra cứu đơn hàng"** (`/tra-cuu-don-hang`, có trên **thanh menu** – mục "Tra cứu đơn" – và ở chân trang): khách nhập **mã đơn / mã giữ hàng + số điện thoại** → mở trang đơn, trang giữ hàng hoặc phiếu. Mã đơn cũng chính là nội dung chuyển khoản nên khách luôn tìm lại được trong lịch sử ngân hàng. Mỗi máy tối đa 10 lần tra sai mỗi giờ (chống dò mã).
- **Link xem đơn riêng cho khách** (có mã bảo mật, dùng được trên mọi máy, bất cứ lúc nào): gửi trong email xác nhận đơn / giữ hàng; cửa hàng sao chép được trong link quản lý (nút "Sao chép link xem đơn").
- **Website nhớ đơn trên thiết bị của khách**: khách quay lại website (vài ngày sau cũng được) sẽ thấy thông báo "Đơn … đã được xác nhận – Xem phiếu xác nhận".
- **Cửa hàng gửi phiếu qua Zalo**: tin Telegram và link quản lý có nút/đường dẫn **"Nhắn Zalo cho khách"** (mở thẳng cuộc trò chuyện theo số điện thoại khách) – sao chép link phiếu rồi dán gửi.
- Thời gian lưu: đơn hàng giữ `order_keep_days` ngày (mặc định 730 = 2 năm); **phiếu xác nhận lưu vĩnh viễn** nên đơn đã xác nhận / đã thanh toán tra cứu được mãi (dùng khi bảo hành). Nhớ **sao lưu định kỳ** thư mục `data/don-hang/` và mang theo khi chuyển hosting. Nếu lỡ mất file `.secret`, link phiếu / mã QR cũ hết hiệu lực nhưng **tra cứu bằng mã đơn + số điện thoại vẫn dùng được**.

### Tự động xác nhận tiền vào (qua dịch vụ theo dõi tài khoản ngân hàng)
Khi bật, website **tự xác nhận** cọc giữ hàng và đơn chuyển khoản ngay khi tiền về tài khoản – shop không phải bấm tay.
- Cọc giữ hàng (nội dung chứa mã `GIU…`, đủ số tiền) → tự xác nhận, giữ hàng `hold_hours` giờ. Thiếu tiền → không xác nhận, báo Telegram để shop xử lý.
- Đơn chuyển khoản (nội dung chứa mã `NP…`) → ghi nhận "Đã nhận thanh toán", trang cảm ơn của khách tự cập nhật.
- Tiền vào không có mã → chỉ báo Telegram. Mọi giao dịch được ghi vào `data/don-hang/giao-dich-YYYY-MM.csv`.

Website hiện kết nối với **Casso** (hỗ trợ nhiều ngân hàng nhất, kể cả Timo). Mỗi dịch vụ hỗ trợ một nhóm ngân hàng khác nhau – **chọn theo ngân hàng nhận tiền của bạn**:
- **Casso Flow** (`flow.casso.vn`): nhiều ngân hàng, kể cả Timo; dùng thử 14 ngày, sau đó trả phí theo năm.
- **SePay** (`sepay.vn`): ACB, BIDV, MB, MSB, KienlongBank, OCB, Sacombank, TPBank, VietinBank, VPBank.
- **payOS** (`payos.vn`, miễn phí gói cơ bản): tài khoản cá nhân MB, OCB, KienlongBank, ACB, BIDV, Vietcombank, MSB.
(Nếu chọn SePay hoặc payOS, cần bổ sung kết nối tương ứng – sẽ làm cùng trang quản trị.)

Cài đặt với Casso (chỉ hoạt động khi website đã chạy trên hosting có https):
1. Đăng ký **Casso Flow** tại `flow.casso.vn`.
2. Liên kết tài khoản ngân hàng nhận tiền trong Casso.
3. Vào **Tích hợp → Webhook V2**, thêm link: `https://tenmiencuaban.vn/webhook-casso.php`, rồi bấm tạo **Key bảo mật**.
4. Dán Key bảo mật vào `casso_webhook_key` trong `config.php`.
5. Dùng chức năng "Gửi thử" trên Casso, rồi chuyển thử 1 khoản nhỏ có mã giữ hàng để kiểm tra.
- Website kiểm tra chữ ký số của mọi thông báo – ai không có Key bảo mật thì không giả mạo được.
- Link quản lý giữ hàng vẫn dùng được để xác nhận / hủy thủ công khi cần.

### Cài báo đơn qua Telegram (miễn phí, khoảng 5 phút)
1. Cài Telegram, tìm **@BotFather** → gửi `/newbot` → đặt tên → nhận **token** (dạng `123456:ABC-xyz…`).
2. Mở bot vừa tạo, bấm **Start** và gửi một tin nhắn bất kỳ.
3. Mở trình duyệt: `https://api.telegram.org/bot<TOKEN>/getUpdates` → tìm `"chat":{"id":…}` → đó là **chat_id**.
   (Muốn cả nhóm cùng nhận: thêm bot vào nhóm, gửi tin trong nhóm rồi làm lại bước 3 – chat_id của nhóm có dấu `-` ở đầu.)
4. Điền `telegram_bot_token` và `telegram_chat_id` trong `config.php`.

   Tải về qua File Manager và mở bằng Excel. Thư mục này đã được khóa, người ngoài không xem được.

**Để email không vào thư rác:** vào cPanel → **Email Accounts**, tạo một email thuộc tên miền của bạn (ví dụ `no-reply@tenmiencuaban.vn`) và điền vào `mail_from` trong `config.php`. Nên thử đặt một đơn hàng sau khi đưa website lên để chắc chắn email đến được.

**Thanh toán chuyển khoản:** điền mục `bank` trong `config.php` (số tài khoản, chủ tài khoản, mã ngân hàng). Khi đó khách sẽ thấy lựa chọn "Chuyển khoản" và trang đặt hàng thành công hiện **mã QR** có sẵn số tiền và nội dung là mã đơn (ảnh QR do dịch vụ VietQR tạo).

---

## 6. Google, quảng cáo & Zalo OA

- **Sơ đồ trang (sitemap):** `https://tenmiencuaban.vn/sitemap.xml` – tự cập nhật. Vào **Google Search Console** → thêm website → mục *Sơ đồ trang web* → gửi `sitemap.xml`.
- **Google Shopping (miễn phí):** tạo tài khoản **Google Merchant Center** → *Sản phẩm* → *Nguồn cấp dữ liệu* → chọn *Tìm nạp theo lịch* và dán link `https://tenmiencuaban.vn/google-shopping.xml`. Cần ảnh sản phẩm thật dạng .jpg/.png; khai báo chính sách giao hàng và đổi trả trong Merchant Center.
- **Google Analytics 4 / Facebook Pixel / TikTok Pixel:** điền mã vào `ga4_id`, `fb_pixel_id`, `tiktok_pixel_id` trong `config.php`. Website tự ghi nhận: xem sản phẩm, thêm giỏ hàng, bắt đầu thanh toán, đặt hàng thành công, thêm yêu thích.
- **Zalo OA:** điền link trang Zalo OA vào `zalo_oa` – chân trang sẽ hiện nút "Quan tâm Zalo OA" thay cho đăng ký email.

---

## 7. Danh sách kiểm tra trước khi chạy chính thức

- [ ] Đã sửa toàn bộ thông tin trong `config.php` (hotline, Zalo, email, địa chỉ, tên công ty, mã số thuế).
- [ ] Thông tin pháp lý in trên phiếu khách: `company_name` (đúng tên trên giấy đăng ký), `tax_code` (hộ kinh doanh: số định danh cá nhân 12 số của chủ hộ; công ty: mã số doanh nghiệp), `license_info` (số, ngày, nơi cấp giấy đăng ký), `address`.
- [ ] Đã đọc lại **Chính sách đặt cọc – giữ hàng** và điều khoản phiếu (`receipt_terms`) – nên nhờ kế toán / luật sư xem qua 1 lần.
- [ ] Đã thay sản phẩm mẫu bằng sản phẩm thật (tên, giá, mô tả, ảnh, video, ni vòng, ni nhẫn).
- [ ] Đã xóa **giấy kiểm định mẫu** trong `data/certificates.php` và video mẫu trong `assets/videos/`.
- [ ] Đã cài báo đơn qua Telegram và thử nhận 1 đơn.
- [ ] Đã cài **email gửi đi (SMTP)** và bấm “Gửi email thử” (VPS không tự gửi được email).
- [ ] Đã kết nối **Cloudflare R2** (khuyên dùng – có thêm bản sao lưu cất ở nơi khác phòng khi VPS hỏng).
- [ ] Đã xóa các **đơn hàng / giữ hàng chạy thử** (đơn của “ĐẠI TRẦN” từ 20–21/10/2026) nếu không muốn giữ.
- [ ] Đã đọc và chỉnh lại các chính sách trong `data/policies.php`.
- [ ] Đã bật SSL (https).
- [ ] Đã thử đặt 1 đơn hàng và 1 tin nhắn liên hệ, kiểm tra email và file CSV.
- [ ] Website bán hàng có chức năng đặt hàng cần **thông báo website thương mại điện tử trước khi hoạt động**. Theo Luật Thương mại điện tử 2025 (hiệu lực 1/7/2026), thủ tục nộp qua Cổng dịch vụ công quốc gia (cơ quan tiếp nhận: Sở Công Thương cấp tỉnh) – nên hỏi lại Sở Công Thương / kế toán cho chắc. Sau khi được xác nhận, gắn biểu tượng "Đã thông báo" (dẫn tới online.gov.vn) vào chân trang.
- [ ] Hỏi kế toán về **hóa đơn điện tử**: từ 2026 hộ kinh doanh có doanh thu trên 1 tỷ/năm phải dùng hóa đơn điện tử (với giá 5–50 triệu/món, chỉ khoảng 20–200 đơn/năm là vượt mốc này).

---

## 8. Bản chạy thử trên Netlify (gửi khách xem thử)

Netlify chỉ chạy website tĩnh (không có PHP), nên dùng công cụ xuất bản để tạo bản chạy thử:

```
python3 cong-cu/xuat-ban-netlify.py https://vien-ngoc-phuong-demo.netlify.app
```

- Kết quả nằm cạnh thư mục dự án: `ban-chay-thu-netlify/site/` (thư mục) và `ban-chay-thu-netlify.zip`.
- Đưa lên: đăng nhập https://app.netlify.com → **Add new site → Deploy manually** → kéo-thả thư mục `site` (hoặc vào https://app.netlify.com/drop).
- Đổi tên miền con: **Site configuration → Change site name** → `vien-ngoc-phuong-demo`. Nếu tên đã có người dùng, chọn tên khác rồi chạy lại công cụ với địa chỉ mới (để ảnh xem trước khi chia sẻ link Zalo/Facebook hiện đúng).
- Mỗi lần sửa sản phẩm/nội dung: chạy lại công cụ và kéo-thả lại (Netlify → Deploys → kéo thả thư mục mới).

Bản chạy thử có thanh thông báo "BẢN CHẠY THỬ", không cho Google lập chỉ mục, dùng **tài khoản ngân hàng thử nghiệm 0000000000**. Mọi tính năng đều dùng được, nhưng **không gửi dữ liệu đi đâu**: đặt hàng, giữ hàng, đặt lịch, liên hệ chỉ hiển thị kết quả mô phỏng. Trang giữ hàng có nút "Mô phỏng: cửa hàng đã nhận cọc" để xem bước xác nhận.

---

## 9. Cấu trúc thư mục

```
index.php          Trang chủ
san-pham.php       Danh sách sản phẩm, lọc theo danh mục / mệnh / giá, tìm kiếm
chi-tiet.php       Chi tiết sản phẩm
gio-hang.php       Giỏ hàng + form đặt hàng (dịch vụ quà tặng)
qua-tang.php       Quà tặng: "Chọn quà giúp tôi" + bộ sưu tập theo dịp
tra-cuu-kiem-dinh.php  Tra cứu giấy kiểm định
dat-lich.php       Đặt lịch xem ngọc tại cửa hàng
giu-hang.php       Giữ hàng / đặt cọc
tra-cuu-don-hang.php  Khách tra cứu đơn hàng / giữ hàng bằng mã + số điện thoại
phieu.php          Phiếu xác nhận cho khách (đặt cọc / thanh toán / đơn hàng) – xem, tải PDF, lưu ảnh, mã QR kiểm tra
yeu-thich.php      Sản phẩm yêu thích & đã xem gần đây
sitemap.php, robots.php, google-shopping.php   Cho Google (sitemap.xml, robots.txt, google-shopping.xml)
webhook-casso.php  Nhận thông báo tiền vào từ Casso (tự xác nhận cọc / thanh toán)
webhook-sepay.php  Nhận thông báo tiền vào từ SePay
demo-data.php      Dữ liệu cho bản chạy thử Netlify (website thật trả về 404)
quan-tri/          Trang quản trị
cong-cu/           Công cụ: xuất bản chạy thử Netlify, sao lưu tự động (sao-luu-tu-dong.php)
cong-cu/vps/       Cài đặt VPS: Nginx, PHP-FPM riêng, tự lấy SSL, danh sách không đưa lên khi deploy
.github/workflows/ Tự đưa website lên VPS khi push lên GitHub
dat-hang.php       Xử lý đặt hàng, trang cảm ơn
cam-nang.php       Danh sách bài viết
bai-viet.php       Chi tiết bài viết
gioi-thieu.php     Giới thiệu
chinh-sach.php     Chính sách
lien-he.php        Liên hệ
dang-ky.php        Xử lý đăng ký nhận tin
404.php            Trang không tìm thấy
config.php         ⭐ Thông tin cửa hàng
data/              ⭐ Sản phẩm, danh mục, bài viết, chính sách, đơn hàng
includes/          Phần đầu trang, chân trang, hàm dùng chung (thường không cần sửa)
                   bi-mat.php (kho mật khẩu mã hóa) · r2.php (Cloudflare R2) · smtp.php (gửi email) · sao-luu.php
assets/            CSS, JavaScript, hình ảnh
```
