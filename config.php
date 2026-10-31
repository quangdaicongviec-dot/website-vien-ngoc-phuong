<?php
/**
 * ============================================================
 *  THÔNG TIN CỬA HÀNG — CHỈ CẦN SỬA FILE NÀY
 * ============================================================
 *  - Giữ nguyên dấu nháy đơn ' ' bao quanh mỗi giá trị.
 *  - Để trống '' nếu chưa có (mục đó sẽ tự ẩn trên website).
 *  - Sửa xong lưu lại, tải lên hosting là website cập nhật ngay.
 *  - CÁCH DỄ HƠN: Trang quản trị (/quan-tri/) → Cài đặt cửa hàng. Mục nào đã lưu trong trang quản trị
 *    (data/noi-dung/cai-dat.json) sẽ được dùng thay cho giá trị tương ứng trong file này.
 */
return [

    // ---- Thương hiệu ----
    'site_name'    => 'Viên Ngọc Phương',
    'tagline'      => 'Ngọc Phỉ Thúy Việt Nam',
    'description'  => 'Viên Ngọc Phương – trang sức ngọc phỉ thúy (Jadeite) tự nhiên loại A: vòng ngọc, mặt dây chuyền, nhẫn, bông tai, chuỗi hạt và vật phẩm phong thủy. Có giấy kiểm định, bảo hành trọn đời.',

    // Tên miền chính thức, KHÔNG có dấu / ở cuối. Ví dụ: 'https://vienngocphuong.vn'
    // Để trống thì website tự nhận diện.
    'site_url'     => '',

    // ---- Liên hệ ----
    'hotline'      => '0900 000 000',          // hiển thị trên website
    'zalo'         => '0900000000',            // số Zalo (viết liền, không khoảng trắng)
    'email'        => 'lienhe@tenmiencuaban.vn',
    'address'      => 'Số nhà, Tên đường, Phường/Xã, Quận/Huyện, Tỉnh/Thành phố',
    'working_hours'=> '8:00 – 21:00, Thứ 2 – Chủ nhật',
    // Bản đồ: vào Google Maps > Chia sẻ > Nhúng bản đồ > copy phần link trong src="..."
    'map_embed'    => '',

    // ---- Mạng xã hội (dán đường link đầy đủ, để trống nếu không có) ----
    'facebook'     => '',
    'messenger'    => '',                      // ví dụ: https://m.me/tenfanpage
    'tiktok'       => '',
    'youtube'      => '',
    'instagram'    => '',
    'shopee'       => '',

    // ---- Thông tin pháp lý (hiển thị chân trang) ----
    // Ghi ĐÚNG như trên giấy đăng ký – các mục này còn được in trên phiếu xác nhận gửi khách.
    'company_name' => 'HỘ KINH DOANH VIÊN NGỌC PHƯƠNG',
    // Mã số thuế. Hộ kinh doanh: số định danh cá nhân (12 số, số CCCD) của chủ hộ – dùng thay mã số thuế từ 1/7/2025.
    // Công ty: mã số doanh nghiệp (10 số).
    'tax_code'     => '',
    'license_info' => '',                      // Giấy đăng ký: số, ngày cấp, nơi cấp. Ví dụ: 'GCN ĐKHKD số … cấp ngày … tại …'

    // ---- Nhận đơn hàng ----
    // Email nhận thông báo khi có đơn hàng / liên hệ mới.
    'order_email'  => 'lienhe@tenmiencuaban.vn',
    // Email gửi đi — nên là email thuộc tên miền của bạn (tạo trong cPanel), tránh vào thư rác.
    'mail_from'    => 'no-reply@tenmiencuaban.vn',

    'shipping_fee'       => 0,                 // Phí giao hàng (0 = miễn phí)
    'free_ship_from'     => 0,                 // Miễn phí giao khi đơn từ số tiền này (0 = luôn miễn phí)

    // ---- Chuyển khoản (để trống 'account' nếu chỉ nhận COD) ----
    // Mã QR VietQR dùng được với hầu hết ngân hàng Việt Nam – chỉ cần đổi bank_id / bank / account / holder.
    // Nên nhập trong Trang quản trị → Cài đặt → Thanh toán & ngân hàng (chọn ngân hàng trong danh sách, có mã QR thử).
    // bank_id: mã ngân hàng theo VietQR, ví dụ: VCB, TCB, MB, ACB, BIDV, ICB (VietinBank), VBA (Agribank), TPB, VPB, STB, TIMO
    // holder: tên chủ tài khoản, VIẾT HOA KHÔNG DẤU (ví dụ 'NGUYEN VAN A')
    'bank' => [
        'bank_id' => '',
        'bank'    => '',
        'account' => '',
        'holder'  => '',
        'branch'  => '',
    ],

    // ---- Zalo OA (trang Zalo của cửa hàng) — hiện ở chân trang để khách "Quan tâm" nhận ưu đãi ----
    // Ví dụ: 'https://zalo.me/1234567890123456789'. Để trống thì dùng số Zalo ở trên.
    'zalo_oa'      => '',

    // ---- Giữ hàng / đặt cọc (cho món giá trị cao) ----
    // Quy trình: khách gửi yêu cầu → món được giữ TẠM trong 'hold_pending_minutes' phút để khách chuyển cọc
    // → shop kiểm tra tài khoản, bấm "Xác nhận đã nhận cọc" (link gửi qua Telegram/email) → giữ chính thức 'hold_hours' giờ.
    'hold_min_price'       => 10000000,    // Nút "Giữ hàng" hiện với sản phẩm từ giá này trở lên
    'hold_pending_minutes' => 60,          // Thời gian chờ khách chuyển cọc (phút)
    'hold_hours'           => 24,          // Số giờ giữ hàng sau khi shop xác nhận đã nhận cọc
    'hold_deposit'         => 10,          // Tiền cọc = % giá sản phẩm
    'hold_extend_hours'    => 24,          // Khách báo trước khi hết hạn → cửa hàng gia hạn được 1 lần thêm số giờ này
    // Chính sách đặt cọc (trang Chính sách → "Chính sách đặt cọc – giữ hàng"). Khách phải đánh dấu đồng ý khi giữ hàng.
    // Đổi nội dung chính sách thì tăng số phiên bản – phiếu đã lập vẫn ghi phiên bản khách đã đồng ý.
    'deposit_policy_version' => '1.0 – 20/10/2026',
    'deposit_credit_months'  => 6,         // Khách đổi ý (báo trước hạn giữ): tiền cọc thành phiếu mua hàng dùng trong số tháng này
    'refund_days'            => 3,         // Hoàn tiền (khi được hoàn) trong số ngày làm việc này

    // ---- Đơn chuyển khoản có món độc bản ----
    // Món độc bản trong đơn chuyển khoản được giữ riêng chờ khách chuyển tiền (không ai đặt trùng được).
    'order_pending_minutes' => 60,         // Thời gian chờ chuyển khoản (phút); hết giờ chưa có tiền → mở bán lại
    'order_paid_hold_days'  => 7,          // Đã nhận tiền / đã xác nhận → giữ món tới khi cửa hàng đánh dấu "Đã có chủ" (ngày)
    // Đơn COD có món độc bản: giữ món chờ cửa hàng gọi khách và bấm "Xác nhận đơn" (giờ). Quá giờ chưa xác nhận → mở bán lại.
    'order_cod_hours'       => 24,
    // Lưu đơn hàng bao lâu để khách tra cứu (ngày). Đơn đã có phiếu xác nhận thì phiếu được lưu vĩnh viễn.
    'order_keep_days'       => 730,

    // ---- Phiếu xác nhận gửi khách (bằng chứng đã đặt cọc / thanh toán) ----
    // Khách nhận phiếu khi: cọc được xác nhận · đơn chuyển khoản đã nhận đủ tiền · cửa hàng xác nhận đơn COD.
    // Phiếu xem / tải PDF / lưu ảnh được, có mã QR để kiểm tra phiếu thật trên website.
    // Tên, mã số thuế, địa chỉ in trên phiếu lấy từ mục "Liên hệ" và "Thông tin pháp lý" ở trên.
    // Mỗi phiếu lưu lại điều khoản lúc lập – sửa ở đây chỉ áp dụng cho các phiếu lập sau.
    // Có thể dùng: {SITE_NAME} {HOTLINE} {EMAIL} {WEBSITE} {GIO_GIU} (số giờ giữ hàng) {PHAN_TRAM_COC}
    'receipt_terms' => [
        'coc' => [
            'Sản phẩm được giữ riêng, giữ nguyên giá đến hết thời hạn ghi trên phiếu; gia hạn được 1 lần (thêm {GIO_GIA_HAN} giờ) nếu Quý khách báo trước khi hết hạn. Tiền cọc được trừ vào giá khi mua.',
            'Hoàn 100% tiền cọc nếu sản phẩm không đúng mô tả, giấy kiểm định, hoặc không thể bán vì lý do khách quan.',
            'Cửa hàng hủy giao dịch do lỗi của cửa hàng: hoàn tiền cọc và trả thêm một khoản bằng tiền cọc.',
            'Quý khách đổi ý và báo trước khi hết hạn giữ: 100% tiền cọc chuyển thành phiếu mua hàng dùng trong {THANG_PHIEU_MUA} tháng. Không báo và không đến mua: tiền cọc thuộc về cửa hàng.',
            'Tiền hoàn được chuyển khoản về tài khoản đã đặt cọc trong {NGAY_HOAN_TIEN} ngày làm việc. Chi tiết: {LINK_CHINH_SACH} (Chính sách đặt cọc – giữ hàng).',
        ],
        'thanh-toan' => [
            'Sản phẩm được đóng hộp kèm giấy kiểm định và giao theo lịch hẹn với Quý khách.',
            'Nếu món độc bản đã có khách khác mua trước khi tiền về: cửa hàng hoàn 100% trong 1 ngày làm việc, hoặc Quý khách chọn mẫu khác.',
            'Đổi trả, bảo hành theo chính sách tại {LINK_CHINH_SACH}. Thắc mắc, khiếu nại: {HOTLINE}.',
        ],
        'don-hang' => [
            'Quý khách kiểm tra sản phẩm rồi mới thanh toán cho nhân viên giao hàng.',
            'Đổi trả, bảo hành theo chính sách tại {LINK_CHINH_SACH}. Thắc mắc, khiếu nại: {HOTLINE}.',
        ],
    ],
    'receipt_note' => 'Phiếu điện tử do website lập tự động. Giá đã bao gồm thuế, phí (nếu có). Phiếu này không phải hóa đơn và không thay thế hóa đơn.',

    // ---- Tự động xác nhận tiền vào qua Casso (casso.vn – hỗ trợ nhiều ngân hàng, kể cả Timo) ----
    // Trước khi đăng ký, kiểm tra ngân hàng nhận tiền của bạn có trong danh sách Casso hỗ trợ.
    // Casso theo dõi tài khoản ngân hàng và báo về website mỗi khi có tiền vào → website tự xác nhận
    // cọc giữ hàng / đơn chuyển khoản theo mã trong nội dung chuyển khoản. Xem hướng dẫn trong HUONG-DAN.md.
    // Điền "Key bảo mật" của tích hợp Webhook V2 trên Casso. Để trống = tắt (shop xác nhận thủ công).
    'casso_webhook_key' => '',

    // ---- Dịch vụ quà tặng khi đặt hàng (giá 0 = miễn phí) ----
    'gift_options' => [
        'thiep'   => ['label' => 'Thiệp viết tay theo lời nhắn của bạn', 'price' => 0],
        'hop_qua' => ['label' => 'Hộp quà cao cấp & túi giấy sang trọng', 'price' => 0],
        'hoa_toc' => ['label' => 'Giao hỏa tốc trong 2–4 giờ (nội thành)', 'price' => 50000],
    ],

    // ---- Đặt lịch xem ngọc tại cửa hàng ----
    'appointment_slots' => ['09:00', '10:30', '14:00', '15:30', '17:00', '19:00'],
    'appointment_days'  => 30,             // Cho đặt trước tối đa bao nhiêu ngày

    // ---- Báo đơn hàng qua Telegram (xem hướng dẫn trong HUONG-DAN.md) ----
    'telegram_bot_token' => '',
    'telegram_chat_id'   => '',

    // ---- Gửi email qua SMTP (VPS thường không tự gửi được email) ----
    // Cài trong Trang quản trị → Cài đặt → Thông báo đơn mới. Mật khẩu SMTP lưu mã hóa, không ghi ở đây.
    'smtp_host'   => '',                   // VD: smtp.gmail.com – để trống = dùng chức năng gửi thư của hosting
    'smtp_port'   => 587,
    'smtp_secure' => 'tls',                // tls (cổng 587) | ssl (cổng 465) | none
    'smtp_user'   => '',

    // ---- Lưu trữ Cloudflare R2 ----
    // Account ID, Access Key ID, Secret Access Key, Bucket: nhập trong Trang quản trị → Cài đặt → Lưu trữ Cloudflare R2
    // (lưu mã hóa, không ghi ở đây).
    'r2_public_url' => '',                 // Địa chỉ công khai của bucket, VD https://pub-xxxx.r2.dev
    'r2_upload'     => true,               // Tự đưa ảnh / video mới tải lên vào R2 (khi đã nhập thông tin kết nối)
    'r2_serve'      => false,              // Khách xem ảnh / video từ R2
    'r2_backup'     => true,               // Sao lưu dữ liệu lên R2 mỗi đêm

    // ---- Đo lường & quảng cáo (để trống nếu chưa dùng) ----
    'ga4_id'          => '',               // Google Analytics 4, ví dụ 'G-XXXXXXXXXX'
    'fb_pixel_id'     => '',               // Facebook Pixel, ví dụ '123456789012345'
    'tiktok_pixel_id' => '',               // TikTok Pixel, ví dụ 'CXXXXXXXXXXXXXXXXXX'

    // ---- Kỹ thuật ----
    // true: đường dẫn đẹp (/san-pham/ten-san-pham). Nếu hosting báo lỗi 404, đổi thành false.
    'pretty_urls'  => true,
];
