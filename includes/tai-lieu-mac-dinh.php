<?php
/**
 * NỘI DUNG GỐC của 2 tài liệu trong Trang quản trị → Hướng dẫn & tài liệu.
 * Sửa trong trang quản trị (lưu ở data/noi-dung/tai-lieu.json) – file này chỉ là bản gốc để "Khôi phục nội dung gốc".
 * Các mã {…} tự thay bằng con số trong Cài đặt (xem danh sách trong quan-tri/trang/huong-dan.php).
 */
return [

/* ==================================================================
 * 1. CHỨC NĂNG WEBSITE
 * ================================================================== */
'chuc-nang' => [
    'title'   => 'Chức năng website',
    'content' => <<<'HTML'
<p>Website <strong>{SITE_NAME}</strong> (<strong>{WEBSITE}</strong>) được làm riêng cho việc bán ngọc phỉ thúy: giúp khách tin tưởng, chọn đúng món, thanh toán dễ dàng – và giúp cửa hàng bán an toàn, không lo bán trùng món độc bản. Tài liệu này tóm tắt website làm được gì, để chủ cửa hàng và nhân viên nắm rõ khi tư vấn khách.</p>
<blockquote>Các con số trong tài liệu (% cọc, số giờ giữ hàng…) tự lấy theo <strong>Cài đặt</strong> hiện tại của cửa hàng.</blockquote>

<h2>Tổng quan</h2>
<p>Khách mua ngọc qua mạng thường lo 3 điều: ngọc có thật không, món nào hợp với mình (hoặc người được tặng), chuyển tiền rồi có chắc chắn không. Website trả lời cả 3, bên cạnh đầy đủ chức năng bán hàng:</p>
<ul>
<li><strong>Tạo niềm tin:</strong> giấy kiểm định tra cứu được, video ngọc thật, soi vân, phiếu xác nhận có mã kiểm tra, khách tự tra cứu đơn.</li>
<li><strong>Bán món độc bản an toàn:</strong> tự giữ món khi có khách đặt, giữ hàng bằng tiền cọc, tự mở bán lại khi khách không thanh toán.</li>
<li><strong>Tư vấn thay người bán:</strong> tính ni vòng – ni nhẫn, tra mệnh chọn màu ngọc, gợi ý quà theo người nhận và ngân sách, đặt lịch xem tận tay.</li>
<li><strong>Vận hành nhẹ nhàng:</strong> báo đơn qua Telegram và email, xác nhận bằng một lần bấm, trang quản trị sửa được mọi nội dung, tự sao lưu mỗi đêm.</li>
</ul>

<h2>Bán món độc bản an toàn</h2>
<h3>Nhãn “Độc bản” và món “Đã có chủ”</h3>
<ul>
<li>Món độc bản có nhãn riêng, mỗi đơn chỉ mua được 1 chiếc.</li>
<li>Bán rồi, món ngọc không biến mất mà chuyển thành <strong>“Đã có chủ”</strong>, kèm nút “Tìm mẫu tương tự” – vừa giữ chân khách, vừa cho thấy cửa hàng bán được nhiều.</li>
</ul>
<h3>Tự giữ món khi có khách đặt</h3>
<p>Ngay khi khách bấm đặt hàng, món độc bản được giữ riêng cho khách đó. Khách khác vào xem thấy rõ “Một khách hàng vừa đặt mua món này” hoặc “Đã có khách đặt cọc” – và không đặt trùng được.</p>
<table>
<thead><tr><th>Trường hợp</th><th>Website tự làm gì</th></tr></thead>
<tbody>
<tr><td>Khách đặt và chuyển khoản</td><td>Giữ món {PHUT_CHO_CK} phút chờ tiền về. Đã nhận tiền → giữ tới khi giao hàng (tối đa {NGAY_GIU_DA_TT} ngày).</td></tr>
<tr><td>Khách đặt trả tiền khi nhận (COD)</td><td>Giữ món tối đa {GIO_COD} giờ để cửa hàng gọi xác nhận. Đã xác nhận → giữ tới khi giao.</td></tr>
<tr><td>Khách không thanh toán</td><td>Hết giờ, món tự mở bán lại – không bị khóa mãi.</td></tr>
<tr><td>Khách chuyển tiền quá muộn</td><td>Trang của khách báo cửa hàng sẽ liên hệ đổi mẫu hoặc hoàn tiền; cửa hàng nhận cảnh báo ngay.</td></tr>
</tbody>
</table>
<h3>Giữ hàng – đặt cọc</h3>
<p>Với món từ <strong>{GIA_GIU_TU}</strong>, khách bấm “Giữ hàng” và đặt cọc <strong>{PHAN_TRAM_COC}%</strong>:</p>
<ol>
<li>Khách điền tên, số điện thoại, đồng ý Chính sách đặt cọc → món được giữ tạm {PHUT_CHO_COC} phút.</li>
<li>Khách quét mã QR chuyển cọc. Cửa hàng xác nhận (hoặc tự động khi tiền vào) → món được giữ riêng {GIO_GIU} giờ, gia hạn được thêm {GIO_GIA_HAN} giờ.</li>
<li>Khách nhận phiếu xác nhận đặt cọc. Tiền cọc được trừ vào giá khi mua.</li>
</ol>
<p>Không cọc kịp → món tự mở bán lại. Mỗi số điện thoại chỉ được 1 yêu cầu chờ cọc (chống giữ hàng ảo). Khách đổi ý báo trước hạn → tiền cọc thành phiếu mua hàng dùng trong {THANG_PHIEU_MUA} tháng; hoàn tiền (khi có) trong {NGAY_HOAN_TIEN} ngày làm việc.</p>

<h2>Thanh toán và sau khi mua</h2>
<h3>Thanh toán bằng mã QR</h3>
<ul>
<li>Khách chọn <strong>trả tiền khi nhận hàng (COD)</strong> hoặc <strong>chuyển khoản</strong> (cửa hàng bật / tắt từng cách trong Cài đặt).</li>
<li>Chuyển khoản: khách nhận mã đơn và mã QR có sẵn số tiền, nội dung – không phải gõ tay, không nhầm số. Tiền về là trang của khách tự chuyển sang “Đã nhận thanh toán”; chuyển thiếu thì báo số tiền còn thiếu.</li>
<li>Có thể bật <strong>VAT</strong>: giá đã gồm VAT, hoặc cộng thêm VAT khi đặt hàng.</li>
</ul>
<h3>Tự xác nhận tiền vào (cần cài đặt)</h3>
<p>Kết nối <strong>Casso</strong> hoặc <strong>SePay</strong>: tiền về đúng mã đơn / mã cọc là tự xác nhận – cửa hàng không phải mở app ngân hàng đối chiếu từng khoản. Chưa kết nối vẫn dùng được: cửa hàng bấm “Xác nhận đã nhận đủ tiền” trong trang quản trị hoặc trong tin báo.</p>
<h3>Phiếu xác nhận cho khách</h3>
<p>Khi cửa hàng nhận cọc, nhận đủ tiền hoặc xác nhận đơn COD, website tự lập phiếu xác nhận:</p>
<ul>
<li>Tải PDF hoặc lưu ảnh vào điện thoại bằng một lần bấm.</li>
<li>Ghi rõ món ngọc, số tiền, thời gian, mã giao dịch, điều khoản đổi trả – cọc.</li>
<li>Có mã QR kiểm tra: quét là mở đúng phiếu trên website – phân biệt phiếu thật, phiếu giả.</li>
<li>Số điện thoại, địa chỉ được che bớt – an toàn khi khách chia sẻ. Phiếu lưu lâu dài để đối chiếu khi bảo hành.</li>
</ul>
<h3>Khách tự tra cứu đơn hàng</h3>
<p>Mục “Tra cứu đơn” trên menu: nhập mã đơn + số điện thoại là xem trạng thái, tải lại phiếu – kể cả sau nhiều tháng (đơn lưu {NGAY_LUU_DON} ngày, phiếu lưu lâu dài). Đơn đã đặt trên máy được nhớ sẵn; có chống dò mã.</p>

<h2>Tư vấn chọn ngọc</h2>
<ul>
<li><strong>Tra cứu giấy kiểm định:</strong> khách nhập mã trên giấy, website hiện ảnh giấy, kết luận, màu, kích thước, nơi kiểm định và đúng món ngọc tương ứng.</li>
<li><strong>Soi vân ngọc và video thật:</strong> máy tính rê chuột để phóng to, bấm để soi toàn màn hình; điện thoại chạm để soi, 2 ngón để phóng to. Video ngay cạnh ảnh (tệp MP4 hoặc YouTube).</li>
<li><strong>Chọn màu ngọc hợp mệnh:</strong> nhập năm sinh → biết mệnh, màu hợp, màu nên tránh; lọc sản phẩm theo 5 mệnh.</li>
<li><strong>Tìm vòng, nhẫn vừa tay:</strong> nhập chu vi bàn tay → tính ni vòng; nhập chu vi ngón tay hoặc đường kính nhẫn đang đeo → tính ni nhẫn; nhập cổ tay → gợi ý độ dài chuỗi. Trang sản phẩm báo “vừa / chật / rộng”.</li>
<li><strong>“Chọn quà giúp tôi”:</strong> khách trả lời 3 câu (tặng ai, năm sinh người nhận, ngân sách) → gợi ý món đúng dịp, hợp mệnh, vừa túi tiền.</li>
<li><strong>Bộ sưu tập quà theo dịp</strong> trên menu và trang chủ.</li>
<li><strong>Đặt lịch xem ngọc tận tay:</strong> khách chọn ngày, khung giờ và món quan tâm – cửa hàng nhận tin để chuẩn bị sẵn mẫu.</li>
<li><strong>Dịch vụ quà tặng khi đặt hàng:</strong> thiệp viết tay, hộp quà, giao hỏa tốc – phí tự cộng vào tổng tiền.</li>
<li><strong>Khách đeo thật:</strong> ảnh và cảm nhận của khách đã mua trên trang chủ; tự ẩn khi chưa có.</li>
</ul>

<h2>Mua sắm trên điện thoại</h2>
<ul>
<li>Giao diện điện thoại thiết kế và kiểm tra riêng: chữ không vỡ dòng, nút không bị che, không phải kéo ngang.</li>
<li>Banner dọc riêng cho điện thoại – máy tính vẫn dùng banner ngang.</li>
<li>Vuốt ngang trên thẻ sản phẩm để xem ảnh thứ hai.</li>
<li>Giỏ hàng và đặt hàng trên cùng một trang; thanh tổng tiền + nút “Đặt hàng” luôn ở đáy màn hình.</li>
<li>Nút Zalo, Gọi luôn sẵn; tự ẩn ở trang thanh toán để không che nội dung.</li>
</ul>

<h2>Bán hàng và tiếp thị</h2>
<ul>
<li>Danh mục sản phẩm; lọc theo danh mục, mệnh, khoảng giá; sắp xếp theo mới nhất, giá, tên; tìm kiếm có dấu hay không dấu đều ra.</li>
<li>Mỗi thẻ sản phẩm có nút <strong>Xem chi tiết</strong>, nút thêm giỏ hàng, nút yêu thích (trái tim) và xem nhanh (con mắt).</li>
<li>Giỏ hàng lưu trên máy khách – không cần đăng ký tài khoản. Giá luôn tính lại theo dữ liệu cửa hàng – khách không thể tự sửa giá.</li>
<li>Cẩm nang (bài viết kiến thức ngọc) và các trang chính sách (bảo hành, đổi trả, vận chuyển, thanh toán, đặt cọc, bảo mật).</li>
<li>Google: tiêu đề, mô tả riêng từng trang; sơ đồ trang (sitemap) tự cập nhật; dữ liệu Google Shopping.</li>
<li>Chia sẻ Facebook / Zalo hiện đúng ảnh, tên, giá.</li>
<li>Đo lường quảng cáo (cần cài đặt): Google Analytics, Facebook Pixel, TikTok Pixel; Zalo OA.</li>
</ul>

<h2>Vận hành cho cửa hàng</h2>
<ul>
<li><strong>Trang quản trị</strong> ({WEBSITE}/quan-tri/): sửa mọi chữ, con số, hình ảnh trên website; xử lý đơn hàng, giữ hàng, phiếu, lịch hẹn, tin nhắn – không cần sửa file. Có tài khoản <strong>Nhân viên bán hàng</strong> chỉ được xử lý đơn.</li>
<li><strong>Nhận tin tức thì</strong> qua Telegram (miễn phí) và email khi có đơn hàng, giữ hàng, lịch hẹn, tin nhắn, tiền vào. Mỗi tin báo kèm link quản lý riêng – mở trên điện thoại là thao tác được.</li>
<li><strong>Lưu trữ bằng Excel:</strong> đơn hàng, giữ hàng, lịch hẹn, liên hệ, giao dịch lưu theo tháng, tải về mở bằng Excel.</li>
</ul>

<h2>Lưu trữ, sao lưu và bảo mật</h2>
<ul>
<li>Website chạy trên máy chủ riêng (VPS), có https; cập nhật website tự động qua GitHub mà không ảnh hưởng dữ liệu.</li>
<li><strong>Tự sao lưu mỗi đêm</strong> toàn bộ dữ liệu (đơn hàng, phiếu, nội dung, tài khoản, ảnh). Có thể tải về, khôi phục; mọi mục nội dung có lịch sử thay đổi để quay lại bản cũ.</li>
<li><strong>Cloudflare R2</strong> (cần cài đặt): lưu thêm ảnh / video và bản sao lưu ở nơi khác – phòng khi máy chủ hỏng; khách có thể xem ảnh trực tiếp từ R2.</li>
<li>Mật khẩu email, khóa R2 được lưu mã hóa. Đăng nhập sai nhiều lần bị khóa tạm. Link quản lý, link xem đơn, phiếu xác nhận đều có mã bảo mật – không giả mạo được. Thư mục dữ liệu bị khóa, người ngoài không xem được.</li>
</ul>
HTML,
],

/* ==================================================================
 * 2. HƯỚNG DẪN SỬ DỤNG TRANG QUẢN TRỊ
 * Tiêu đề H2 trùng tên mục trên menu → nút "Hướng dẫn" ở mỗi trang mở đúng phần tương ứng.
 * ================================================================== */
'quan-tri' => [
    'title'   => 'Hướng dẫn sử dụng trang quản trị',
    'content' => <<<'HTML'
<p>Trang quản trị giúp cửa hàng tự làm mọi việc trên website mà không cần sửa file: xử lý đơn hàng, giữ hàng, phiếu xác nhận; thêm sản phẩm, banner, bài viết; đổi chữ, con số, ngân hàng nhận tiền… Mỗi trang trong trang quản trị đều có nút <strong>Hướng dẫn</strong> ở góc trên bên phải – bấm là mở đúng phần hướng dẫn của trang đó.</p>

<h2>Bắt đầu</h2>
<h3>Đăng nhập</h3>
<ul>
<li>Mở <strong>{WEBSITE}/quan-tri/</strong>, nhập tên đăng nhập và mật khẩu. Tick “Ghi nhớ đăng nhập” để 30 ngày không phải đăng nhập lại trên máy đó (chỉ tick trên máy của mình).</li>
<li>Nhập sai mật khẩu 5 lần, tài khoản bị khóa tạm 15 phút – chờ hết giờ rồi thử lại.</li>
<li>Đăng xuất: bấm tên của bạn ở góc trên bên phải → Đăng xuất.</li>
</ul>
<h3>Cách dùng chung</h3>
<ul>
<li><strong>Menu</strong> bên trái chia theo nhóm: Bán hàng, Sản phẩm, Nội dung, Cài đặt, Trợ giúp. Trên điện thoại bấm nút ☰ ở góc trên bên trái để mở menu.</li>
<li><strong>Nút Lưu</strong> luôn nằm ở thanh dưới đáy màn hình. Có thay đổi chưa lưu, thanh báo “Có thay đổi chưa lưu” và trình duyệt nhắc khi bạn rời trang.</li>
<li><strong>Ảnh</strong> được tự thu nhỏ trước khi tải lên – cứ chọn ảnh gốc từ điện thoại / máy ảnh.</li>
<li><strong>Ô tiền</strong> gõ kiểu nào cũng được: 16800000, 16.800.000 hay “16,8 triệu”.</li>
<li><strong>Xem website</strong> (góc trên) mở website trong thẻ mới để kiểm tra kết quả.</li>
<li>Mỗi lần lưu, bản cũ được cất lại – lỡ sửa sai vào <a href="/quan-tri/?p=sao-luu">Sao lưu &amp; khôi phục</a> → Lịch sử thay đổi để quay lại.</li>
</ul>
<h3>Hai loại tài khoản</h3>
<table>
<thead><tr><th>Loại</th><th>Được làm gì</th></tr></thead>
<tbody>
<tr><td>Quản lý</td><td>Mọi việc: sản phẩm, nội dung, cài đặt, tài khoản, sao lưu.</td></tr>
<tr><td>Nhân viên bán hàng</td><td>Chỉ: Tổng quan, Đơn hàng, Giữ hàng, Phiếu xác nhận, Lịch hẹn, Tin nhắn, đổi mật khẩu của mình, đọc hướng dẫn.</td></tr>
</tbody>
</table>
<h3>Việc nên làm mỗi ngày</h3>
<ol>
<li>Mở <strong>Tổng quan</strong> xem “Việc cần làm”.</li>
<li>Xử lý <strong>đơn hàng</strong> mới: gọi xác nhận đơn COD, kiểm tra tiền vào của đơn chuyển khoản, đóng gói – giao hàng.</li>
<li>Xác nhận <strong>tiền cọc giữ hàng</strong> (nếu chưa bật tự xác nhận).</li>
<li>Gọi xác nhận <strong>lịch hẹn</strong>, trả lời <strong>tin nhắn liên hệ</strong>.</li>
</ol>

<h2>Tổng quan</h2>
<ul>
<li>Các ô số: đơn cần xử lý, đang giữ hàng, lịch hẹn mới, tin nhắn mới, doanh thu tháng (đơn đã thanh toán / đã xác nhận), số sản phẩm đang bán. Bấm vào ô để mở danh sách.</li>
<li><strong>Việc cần làm</strong>: đơn, giữ hàng, lịch hẹn, tin nhắn đang chờ – mới nhất ở trên.</li>
<li><strong>Cài đặt còn thiếu</strong>: website tự nhắc những gì chưa hoàn thiện (thông tin mẫu, giá mô phỏng, chưa bật Telegram, chưa cài email…) kèm nút “Sửa ngay”.</li>
</ul>

<h2>Đơn hàng</h2>
<p>Danh sách mặc định lọc “Cần xử lý”. Có thể lọc theo trạng thái (chờ chuyển khoản, COD mới, chờ giao, đang giao, hoàn tất, đã hủy), theo tháng, tìm theo mã đơn – tên – số điện thoại, và <strong>tải Excel</strong>. Bấm vào mã đơn để mở chi tiết.</p>
<h3>Đơn chuyển khoản</h3>
<ol>
<li>Khách đặt → đơn ở trạng thái <strong>Chờ thanh toán</strong>, món độc bản được giữ {PHUT_CHO_CK} phút.</li>
<li>Tiền về: nếu đã kết nối Casso / SePay, đơn tự chuyển “Đã nhận thanh toán”. Nếu chưa, kiểm tra app ngân hàng (nội dung chuyển khoản là mã đơn) rồi bấm <strong>Xác nhận đã nhận đủ tiền</strong>. Website tự lập phiếu xác nhận cho khách.</li>
<li>Gửi hàng → bấm <strong>Đánh dấu đang giao hàng</strong>. Khách nhận hàng → bấm <strong>Hoàn tất – khách đã nhận hàng</strong> (để tick “Chuyển … món độc bản sang Đã có chủ”).</li>
</ol>
<h3>Đơn trả tiền khi nhận (COD)</h3>
<ol>
<li>Khách đặt → đơn ở trạng thái <strong>Chờ xác nhận</strong>, món độc bản được giữ tối đa {GIO_COD} giờ.</li>
<li>Gọi điện xác nhận với khách → bấm <strong>Xác nhận đơn (đã gọi khách)</strong>. Website lập phiếu xác nhận đơn hàng cho khách.</li>
<li>Gửi hàng → <strong>Đánh dấu đang giao hàng</strong> → khách nhận và trả tiền → <strong>Hoàn tất – khách đã nhận hàng</strong>.</li>
</ol>
<h3>Việc khác trong đơn</h3>
<ul>
<li><strong>Hủy đơn</strong>: món độc bản được mở bán lại ngay.</li>
<li><strong>Ghi chú nội bộ</strong>: chỉ cửa hàng thấy (VD: “khách hẹn giao sau 18h”).</li>
<li><strong>Sao chép link đơn gửi khách</strong> / <strong>Sao chép link phiếu</strong>: gửi qua Zalo để khách xem đơn, tải phiếu.</li>
<li>Bấm nhầm “Hoàn tất” hay “Đang giao” thì bấm <strong>Mở lại (chưa giao xong)</strong>.</li>
</ul>

<h2>Giữ hàng – đặt cọc</h2>
<ol>
<li>Khách gửi yêu cầu giữ hàng (món từ {GIA_GIU_TU}) → chờ chuyển cọc {PHAN_TRAM_COC}% trong {PHUT_CHO_COC} phút.</li>
<li>Tiền cọc về → bấm <strong>Xác nhận đã nhận cọc</strong> (hoặc tự động nếu đã kết nối Casso / SePay). Món được giữ {GIO_GIU} giờ, khách nhận phiếu đặt cọc.</li>
<li>Khách cần thêm thời gian → <strong>Gia hạn thêm {GIO_GIA_HAN} giờ</strong> (1 lần).</li>
<li>Khách mua → <strong>Khách đã mua – hoàn tất</strong> (món chuyển “Đã có chủ”, phiếu cọc chuyển “Đã hoàn tất mua”). Khách không mua → <strong>Hủy giữ hàng</strong>, rồi xử lý tiền cọc theo Chính sách đặt cọc (đổi trạng thái phiếu ở mục Phiếu xác nhận).</li>
</ol>

<h2>Phiếu xác nhận</h2>
<ul>
<li>Danh sách mọi phiếu website đã lập (đặt cọc, thanh toán, đơn hàng COD). Bấm để xem đúng phiếu khách đang có.</li>
<li><strong>Đổi trạng thái phiếu</strong>: Còn hiệu lực · Đã hoàn tất mua · Đã chuyển thành phiếu mua hàng · Đã hoàn tiền · Đã hủy – kèm ghi chú. Khách mở link phiếu sẽ thấy trạng thái mới.</li>
</ul>

<h2>Lịch hẹn xem ngọc</h2>
<p>Danh sách khách đặt lịch đến cửa hàng (ngày, giờ, món quan tâm, số điện thoại). Gọi xác nhận rồi đổi trạng thái: <em>Đã gọi xác nhận</em>, <em>Khách đã đến</em>, <em>Hủy / không đến</em>. Khung giờ nhận hẹn sửa ở <a href="/quan-tri/?p=cai-dat&amp;nhom=dat-lich">Cài đặt → Đặt lịch xem ngọc</a>.</p>

<h2>Tin nhắn liên hệ</h2>
<p>Tin khách gửi từ trang Liên hệ. Trả lời khách qua điện thoại / Zalo / email rồi đổi trạng thái: <em>Đã trả lời</em>, <em>Lưu lại</em> hoặc <em>Tin rác</em>.</p>

<h2>Giao dịch &amp; đăng ký</h2>
<p>Các khoản tiền vào do Casso / SePay báo về, email khách đăng ký nhận tin, và các tệp Excel gốc theo tháng để tải về.</p>

<h2>Sản phẩm</h2>
<h3>Thêm sản phẩm mới</h3>
<ol>
<li>Bấm <strong>Thêm sản phẩm</strong>. Điền tên, chọn danh mục, giá (để trống = hiện “Liên hệ”), giá gốc nếu đang giảm giá.</li>
<li><strong>Ảnh</strong>: chọn nhiều ảnh một lần; kéo thả hoặc bấm mũi tên để đổi thứ tự. Ảnh đầu tiên là ảnh đại diện, ảnh thứ hai hiện khi rê chuột / vuốt. Ảnh vuông (VD 1000×1000) đẹp nhất.</li>
<li><strong>Video</strong>: dán link YouTube hoặc tải tệp MP4 ngắn.</li>
<li>Thông tin ngọc: màu sắc, chất liệu, kích thước / ni (chữ hiển thị), <strong>ni vòng / ni nhẫn</strong> (để bộ tính ni tìm đúng món), màu ngọc thuộc hành (mệnh), dịp tặng, giấy kiểm định.</li>
<li>Bật <strong>Độc bản</strong> cho món chỉ có 1 chiếc (tự giữ món khi có khách đặt). Bật <strong>Nổi bật</strong> để hiện ở trang chủ, <strong>Hàng mới</strong> để có nhãn “Mới”.</li>
<li>Sản phẩm mới tạo đang <strong>ẩn</strong> (bản nháp). Soạn xong, tắt công tắc <strong>Ẩn khỏi website</strong> rồi bấm Lưu để đăng.</li>
</ol>
<h3>Việc thường làm</h3>
<ul>
<li><strong>Nhân bản</strong>: tạo món mới từ món có sẵn (bản sao đang ẩn) – nhanh khi nhập các món giống nhau.</li>
<li><strong>Đã có chủ / Bán lại</strong>: đánh dấu nhanh món đã bán hoặc mở bán lại.</li>
<li><strong>Ẩn / Hiện</strong>: tạm cất món không bán.</li>
<li><strong>Sắp xếp</strong>: kéo thả thứ tự hiển thị trên website.</li>
<li>Lọc nhanh: món còn <strong>giá mô phỏng</strong> (giá đặt tạm khi chạy thử – nhớ thay bằng giá thật và tắt công tắc “Đây là giá mô phỏng”), món thiếu kích thước.</li>
<li><strong>Xóa</strong>: xóa hẳn món nhập nhầm (món đã bán nên để “Đã có chủ” thay vì xóa).</li>
</ul>

<h2>Danh mục</h2>
<p>Thêm / đổi tên / sắp xếp danh mục (vòng tay, mặt dây chuyền, nhẫn…). Đổi tên danh mục, các sản phẩm tự theo. Chỉ xóa được danh mục khi không còn sản phẩm nào.</p>

<h2>Giấy kiểm định</h2>
<p>Thêm giấy kiểm định: mã số in trên giấy, ảnh giấy, kết luận, màu, kích thước, nơi kiểm định, và gắn với sản phẩm tương ứng. Khách tra mã trên website sẽ thấy đúng giấy và đúng món ngọc. Nhớ xóa các giấy <strong>mẫu</strong> trước khi bán thật.</p>

<h2>Bộ sưu tập quà tặng</h2>
<p>Các bộ sưu tập theo dịp (trao dâu, mừng thọ, tặng mẹ…): tên, ảnh, mô tả và tick chọn các sản phẩm thuộc bộ sưu tập.</p>

<h2>Banner trang chủ</h2>
<ul>
<li>Loại <strong>ảnh</strong>: ảnh máy tính 1920×942 và <strong>ảnh dọc cho điện thoại 1080×1350</strong> (thiếu ảnh dọc thì banner đó tạm ẩn trên điện thoại). Loại <strong>chữ</strong>: dòng nhỏ, tiêu đề, đoạn mô tả, chữ trên nút.</li>
<li>“Bấm vào banner thì mở”: VD <code>danh-muc/vong-tay</code> hoặc một trang sản phẩm.</li>
<li>Kéo thả để sắp xếp; “Tạm tắt banner này” để ẩn mà không xóa.</li>
</ul>

<h2>Cẩm nang (bài viết)</h2>
<ul>
<li>Trình soạn thảo: <strong>H2</strong> cho mục lớn, <strong>H3</strong> cho mục nhỏ; in đậm, danh sách, trích dẫn, liên kết, chèn ảnh, chèn bảng. Dán chữ từ Word / Google Docs được – định dạng lạ tự bỏ.</li>
<li>Bài mới là <strong>bản nháp</strong> – tắt “Bản nháp – chưa đăng” để đăng. Có ảnh đại diện, ngày đăng, đoạn tóm tắt (để trống thì tự lấy đầu bài).</li>
</ul>

<h2>Chính sách</h2>
<p>Sửa các trang chính sách bằng trình soạn thảo. Dùng mã tự thay như <code>{GIO_GIU}</code>, <code>{PHAN_TRAM_COC}</code> để chính sách luôn khớp với Cài đặt. Sửa <strong>Chính sách đặt cọc</strong> thì nhớ tăng số <strong>phiên bản</strong> (in trên phiếu cọc).</p>

<h2>Khách đeo thật</h2>
<p>Ảnh và cảm nhận của khách đã mua (nên xin phép khách trước). Mục này trên trang chủ tự hiện khi có ít nhất 1 cảm nhận.</p>

<h2>Chữ trên các trang</h2>
<p>Sửa tiêu đề, đoạn giới thiệu, cam kết… của trang chủ, Giới thiệu, chân trang, trang sản phẩm, giỏ hàng và các trang khác. Để trống một ô (hoặc tick “Dùng chữ mặc định”) để quay về chữ gốc. Ô nhiều đoạn: để 1 dòng trống giữa các đoạn; ô danh sách: mỗi dòng 1 mục.</p>

<h2>Ngũ hành &amp; quà tặng</h2>
<p>Gợi ý màu ngọc theo từng mệnh, các khoảng giá của bộ lọc, danh sách người nhận trong “Chọn quà giúp tôi”.</p>

<h2>Cài đặt cửa hàng</h2>
<p>Chọn nhóm ở hàng thẻ đầu trang:</p>
<table>
<thead><tr><th>Nhóm</th><th>Gồm</th></tr></thead>
<tbody>
<tr><td>Cửa hàng &amp; pháp lý</td><td>Tên, khẩu hiệu, logo, ảnh chia sẻ Facebook / Zalo, hotline, Zalo, email, giờ mở cửa, địa chỉ, bản đồ, tên hộ kinh doanh / công ty, mã số thuế, giấy đăng ký, link Bộ Công Thương.</td></tr>
<tr><td>Thanh toán &amp; ngân hàng</td><td>Chọn ngân hàng trong danh sách, số tài khoản, tên chủ tài khoản (có <strong>mã QR thử</strong> để quét kiểm tra), bật / tắt COD, tự xác nhận tiền vào (Casso / SePay), VAT.</td></tr>
<tr><td>Giao hàng &amp; quà tặng</td><td>Phí giao hàng, miễn phí giao từ, dịch vụ thiệp – hộp quà – hỏa tốc.</td></tr>
<tr><td>Giữ hàng &amp; đơn hàng</td><td>Giá từ được giữ hàng, % cọc, các thời hạn giữ món, số ngày lưu đơn.</td></tr>
<tr><td>Phiếu xác nhận</td><td>Điều khoản in trên phiếu cọc / thanh toán / đơn hàng, dòng ghi chú cuối phiếu.</td></tr>
<tr><td>Thông báo đơn mới</td><td>Telegram, email nhận thông báo, <strong>email gửi đi (SMTP)</strong>; nút gửi thử.</td></tr>
<tr><td>Đặt lịch xem ngọc</td><td>Khung giờ nhận hẹn, cho đặt trước tối đa bao nhiêu ngày.</td></tr>
<tr><td>Mạng xã hội &amp; quảng cáo</td><td>Link Facebook, Messenger, Instagram, TikTok, YouTube, Shopee, Zalo OA; mã Google Analytics, Facebook / TikTok Pixel.</td></tr>
<tr><td>Lưu trữ Cloudflare R2</td><td>Kết nối R2 (xem phần riêng bên dưới).</td></tr>
<tr><td>Tên miền &amp; kỹ thuật</td><td>Tên miền chính thức, đường dẫn đẹp.</td></tr>
</tbody>
</table>
<h3>Báo đơn qua Telegram</h3>
<ol>
<li>Mở Telegram → tìm <strong>@BotFather</strong> → gõ <code>/newbot</code> → đặt tên → nhận mã bot dạng <code>123456789:AA…</code>.</li>
<li>Dán mã vào ô “Mã bot Telegram” → Lưu.</li>
<li>Nhắn 1 tin bất kỳ cho bot (hoặc thêm bot vào nhóm của cửa hàng) → bấm <strong>Tìm mã nhận tin (Chat ID)</strong> → bấm <strong>Dùng</strong> → <strong>Gửi tin Telegram thử</strong>.</li>
</ol>
<h3>Email gửi đi (SMTP)</h3>
<p>Máy chủ không tự gửi được email, cần cài SMTP. Dùng Gmail:</p>
<ol>
<li>Tài khoản Google → Bảo mật → bật <strong>Xác minh 2 bước</strong>.</li>
<li>Mở <strong>myaccount.google.com/apppasswords</strong> → tạo mật khẩu ứng dụng → nhận 16 ký tự.</li>
<li>Điền: Máy chủ <code>smtp.gmail.com</code> · Cổng <code>587</code> · Mã hóa TLS · Tên đăng nhập = địa chỉ Gmail · Mật khẩu = 16 ký tự vừa tạo · “Email gửi đi” = chính địa chỉ Gmail đó → Lưu → <strong>Gửi email thử</strong>.</li>
</ol>

<h2>Lưu trữ Cloudflare R2</h2>
<p>R2 là kho lưu trữ của Cloudflare: lưu thêm ảnh / video đã tải lên và bản sao lưu ở nơi khác, phòng khi máy chủ hỏng. Cách kết nối (làm 1 lần trên <strong>dash.cloudflare.com</strong>):</p>
<ol>
<li><em>R2 Object Storage</em> → <em>Create bucket</em> → đặt tên (VD <code>vienngocphuong</code>).</li>
<li>Chép <strong>Account ID</strong> ở trang tổng quan R2.</li>
<li><em>API Tokens → Manage → Create API token</em>, quyền <strong>Object Read &amp; Write</strong>, chọn bucket vừa tạo → chép <strong>Access Key ID</strong> và <strong>Secret Access Key</strong> (Secret chỉ hiện 1 lần).</li>
<li>Vào <a href="/quan-tri/?p=cai-dat&amp;nhom=luu-tru">Lưu trữ Cloudflare R2</a>, dán 4 thông tin → <strong>Lưu cài đặt</strong>. Website tự kiểm tra và báo đúng / sai ở ô nào.</li>
<li>Bấm <strong>Đưa ảnh / video cũ lên R2</strong> cho các ảnh tải lên trước khi kết nối.</li>
</ol>
<p>Muốn khách xem ảnh từ R2: bucket → <em>Settings → Public access</em> → bật R2.dev subdomain (hoặc gắn tên miền riêng) → dán địa chỉ vào ô “Địa chỉ công khai” → bật “Khách xem ảnh / video từ R2”. Đổi khóa lúc nào cũng được – website đọc lại thông tin mỗi lần tải tệp lên.</p>

<h2>Tài khoản</h2>
<ul>
<li>Đổi tên hiển thị, đổi mật khẩu của mình (ít nhất 8 ký tự, không dùng ngày sinh, số điện thoại).</li>
<li>Quản lý: thêm tài khoản <strong>Nhân viên bán hàng</strong> hoặc Quản lý khác, đổi loại tài khoản, khóa / mở khóa, đặt lại mật khẩu, xóa tài khoản. Nhân viên nghỉ việc thì khóa hoặc xóa ngay.</li>
</ul>

<h2>Sao lưu &amp; khôi phục</h2>
<ul>
<li>Máy chủ <strong>tự sao lưu mỗi đêm</strong> (giữ 7 bản, tải về được) và gửi lên Cloudflare R2 nếu đã kết nối.</li>
<li><strong>Tải bản sao lưu</strong>: 1 tệp .zip gồm đơn hàng, phiếu, lịch hẹn, nội dung, cài đặt, tài khoản, ảnh. Nên tải mỗi tuần, cất ở Google Drive. Tệp có thông tin khách hàng – không gửi người lạ.</li>
<li><strong>Khôi phục từ file</strong>: chọn tệp .zip đã tải. Dữ liệu hiện tại được tự sao lưu trước khi ghi đè.</li>
<li><strong>Lịch sử thay đổi</strong>: mỗi mục nội dung giữ 30 bản lưu gần nhất – lỡ xóa / sửa sai thì bấm “Quay lại bản này”.</li>
</ul>

<h2>Nhật ký thao tác</h2>
<p>Ghi lại ai làm gì, lúc nào (xác nhận đơn, sửa sản phẩm, đổi cài đặt…) – dùng để kiểm tra khi có nhiều người cùng quản lý.</p>

<h2>Hướng dẫn &amp; tài liệu</h2>
<p>Chính là trang này. Tài khoản Quản lý bấm <strong>Sửa tài liệu</strong> để cập nhật nội dung (trình soạn thảo giống Cẩm nang), hoặc <strong>Thêm tài liệu</strong> để soạn tài liệu riêng của cửa hàng (quy trình đóng gói, kịch bản tư vấn…). Bấm <strong>In / lưu PDF</strong> để in hoặc lưu thành tệp PDF. Lỡ sửa hỏng 2 tài liệu có sẵn thì bấm “Khôi phục nội dung gốc”.</p>

<h2>Câu hỏi thường gặp</h2>
<h3>Quên mật khẩu?</h3>
<p>Nhờ một tài khoản Quản lý khác vào Tài khoản → Đặt lại mật khẩu. Nếu không còn ai, liên hệ người quản trị kỹ thuật của website.</p>
<h3>Khách báo đã chuyển tiền nhưng đơn vẫn “Chờ thanh toán”?</h3>
<p>Kiểm tra app ngân hàng: đúng số tiền, nội dung có mã đơn chưa. Có tiền rồi thì mở đơn → <strong>Xác nhận đã nhận đủ tiền</strong>. Nếu đã bật Casso / SePay mà vẫn không tự xác nhận, xem mục Giao dịch &amp; đăng ký xem dịch vụ có báo khoản tiền về không.</p>
<h3>Khách không nhận được email?</h3>
<p>Vào Cài đặt → Thông báo đơn mới → <strong>Gửi email thử</strong>; nếu báo lỗi, làm lại các bước SMTP ở trên. Nhắc khách xem cả mục Spam / Quảng cáo.</p>
<h3>Tải ảnh lên bị lỗi?</h3>
<p>Chỉ nhận ảnh JPG, PNG, WEBP, GIF (tối đa 20 MB) và video MP4 / WEBM. Video dài nên đăng YouTube rồi dán link. Nếu báo lỗi Cloudflare R2: ảnh vẫn đã lưu trên máy chủ – vào Lưu trữ Cloudflare R2 bấm Kiểm tra kết nối.</p>
<h3>Lỡ xóa / sửa sai nội dung?</h3>
<p>Vào <strong>Sao lưu &amp; khôi phục → Lịch sử thay đổi</strong>, chọn mục và bấm “Quay lại bản này”.</p>
<h3>Muốn thêm tính năng hoặc sửa giao diện website?</h3>
<p>Việc này cần sửa mã nguồn – liên hệ người làm website. Sau khi sửa, website tự cập nhật (khoảng 1–2 phút), dữ liệu của cửa hàng không bị ảnh hưởng.</p>
HTML,
],

];
