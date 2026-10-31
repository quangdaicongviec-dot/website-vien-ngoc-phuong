<?php
/**
 * CHÍNH SÁCH CỬA HÀNG — hãy đọc lại và chỉnh cho đúng với cách cửa hàng bạn thực sự làm.
 *  ► Đã sửa mục này trong TRANG QUẢN TRỊ (/quan-tri/) thì website dùng dữ liệu trong data/noi-dung/ – file này chỉ còn là bản mẫu.
 * Các mã {SITE_NAME}, {HOTLINE}, {EMAIL}, {ADDRESS} sẽ tự động được thay bằng thông tin trong config.php.
 */
return [
    'bao-hanh' => [
        'title'   => 'Chính sách bảo hành',
        'content' => <<<'HTML'
<p>{SITE_NAME} mong muốn mỗi món trang sức ngọc phỉ thúy luôn đồng hành lâu dài cùng bạn. Chính sách dưới đây áp dụng cho các sản phẩm mua tại cửa hàng và trên website của chúng tôi.</p>

<h3>Cam kết ngọc phỉ thúy tự nhiên loại A trọn đời</h3>
<p>Mọi sản phẩm ngọc phỉ thúy do {SITE_NAME} bán ra đều được cam kết trọn đời là ngọc phỉ thúy tự nhiên loại A, đúng như giấy kiểm định đi kèm. Nếu kết quả từ một phòng kiểm định đá quý uy tín chứng minh sản phẩm không đúng như cam kết, chúng tôi <strong>hoàn lại 100% số tiền</strong> bạn đã thanh toán cho sản phẩm đó.</p>
<p>Để được áp dụng, sản phẩm cần còn nguyên viên ngọc gốc, khớp với thông tin trên giấy kiểm định, kèm hóa đơn hoặc thông tin đơn hàng.</p>

<h3>Quyền lợi bảo hành miễn phí</h3>
<ul>
<li>Làm sạch và đánh bóng ngọc miễn phí trọn đời.</li>
<li>Miễn phí xâu lại hoặc thay dây cho chuỗi hạt, vòng hạt và dây đeo mặt dây chuyền.</li>
<li>Hỗ trợ sửa chữa phần kim loại gắn ngọc như ổ chấu, móc khóa, khung bọc. Trường hợp cần thay mới vật tư hoặc linh kiện, có thể phát sinh chi phí; chúng tôi sẽ báo trước để bạn quyết định.</li>
</ul>

<h3>Trường hợp không thuộc phạm vi bảo hành</h3>
<ul>
<li>Ngọc bị nứt, mẻ, vỡ do va đập, rơi rớt hoặc sử dụng, bảo quản không đúng cách.</li>
<li>Hư hỏng do tác động của hóa chất, nhiệt độ cao hoặc ngoại lực khác.</li>
<li>Sản phẩm đã được sửa chữa hoặc gia công lại tại nơi khác.</li>
</ul>
<p>Với những trường hợp này, {SITE_NAME} vẫn sẵn lòng tư vấn và hỗ trợ khắc phục trong khả năng; chi phí (nếu có) sẽ được thông báo trước.</p>

<h3>Cách yêu cầu bảo hành</h3>
<p>Bạn vui lòng liên hệ điện thoại/Zalo {HOTLINE}, gửi email đến {EMAIL} hoặc mang sản phẩm trực tiếp đến cửa hàng tại {ADDRESS}. Khi gửi bảo hành, vui lòng kèm theo giấy kiểm định và thông tin đơn hàng. Chúng tôi sẽ kiểm tra và báo thời gian hoàn thành cụ thể ngay khi tiếp nhận sản phẩm.</p>
HTML,
    ],
    'doi-tra' => [
        'title'   => 'Chính sách đổi trả',
        'content' => <<<'HTML'
<p>{SITE_NAME} mong bạn thật sự hài lòng với món ngọc đã chọn. Nếu sản phẩm chưa như mong đợi, bạn có thể đổi hoặc trả hàng theo các điều kiện dưới đây.</p>

<h3>Điều kiện đổi trả</h3>
<ul>
<li>Yêu cầu đổi trả được gửi trong vòng <strong>7 ngày</strong> kể từ ngày bạn nhận hàng.</li>
<li>Sản phẩm chưa qua sử dụng, không trầy xước, nứt mẻ hay hư hỏng.</li>
<li>Còn đầy đủ giấy kiểm định gốc, hộp, túi và bao bì như khi nhận.</li>
</ul>

<h3>Đổi size vòng tay</h3>
<p>Nếu vòng ngọc không vừa tay, bạn có thể đổi sang ni khác trong thời hạn 7 ngày, tùy theo mẫu vòng còn sẵn tại cửa hàng. Vì mỗi chiếc vòng ngọc tự nhiên là độc bản về màu sắc và vân ngọc, chiếc vòng đổi sẽ là mẫu tương tự chứ không hoàn toàn giống chiếc cũ. Nếu sản phẩm mới có giá cao hơn, bạn vui lòng thanh toán thêm phần chênh lệch.</p>

<h3>Trường hợp không áp dụng đổi trả</h3>
<ul>
<li>Quá 7 ngày kể từ khi nhận hàng.</li>
<li>Sản phẩm đã qua sử dụng, bị trầy xước, nứt mẻ, hoặc thiếu giấy kiểm định, bao bì gốc.</li>
<li>Sản phẩm được chế tác hoặc đặt làm riêng theo yêu cầu (khắc chữ, thay đổi thiết kế…).</li>
</ul>

<h3>Cách gửi yêu cầu đổi trả</h3>
<ol>
<li>Liên hệ {SITE_NAME} qua điện thoại/Zalo {HOTLINE} hoặc email {EMAIL}, cung cấp mã đơn hàng, lý do đổi trả và hình ảnh sản phẩm.</li>
<li>Chúng tôi xác nhận yêu cầu và hướng dẫn bạn cách đóng gói, gửi hàng.</li>
<li>Bạn đóng gói cẩn thận, gửi kèm đầy đủ giấy kiểm định và phụ kiện.</li>
<li>Sau khi nhận và kiểm tra sản phẩm, chúng tôi tiến hành đổi hàng hoặc hoàn tiền.</li>
</ol>

<h3>Thời gian hoàn tiền</h3>
<p>Tiền được hoàn bằng hình thức chuyển khoản vào tài khoản bạn cung cấp, trong vòng 3–7 ngày làm việc kể từ khi {SITE_NAME} nhận lại sản phẩm và xác nhận sản phẩm đủ điều kiện đổi trả.</p>

<h3>Phí vận chuyển khi đổi trả</h3>
<ul>
<li>Nếu lỗi thuộc về cửa hàng (giao sai mẫu, sai size so với đơn đặt, sản phẩm bị lỗi), {SITE_NAME} chịu toàn bộ phí vận chuyển.</li>
<li>Các trường hợp khác (đổi ý, đổi mẫu, đổi size do chọn chưa đúng), bạn vui lòng chịu phí vận chuyển.</li>
</ul>
HTML,
    ],
    'van-chuyen' => [
        'title'   => 'Chính sách vận chuyển',
        'content' => <<<'HTML'
<p>{SITE_NAME} giao hàng trên toàn quốc, với quy trình đóng gói cẩn thận để món ngọc đến tay bạn an toàn, nguyên vẹn.</p>

<h3>Phí vận chuyển</h3>
<p><strong>Miễn phí vận chuyển toàn quốc cho mọi đơn hàng.</strong></p>

<h3>Thời gian giao hàng dự kiến</h3>
<ul>
<li>Nội thành: 1–2 ngày.</li>
<li>Các tỉnh, thành khác: 2–5 ngày.</li>
</ul>
<p>Thời gian trên được tính từ khi đơn hàng được xác nhận và có thể kéo dài hơn vào dịp lễ, Tết, khi thời tiết xấu hoặc với khu vực vùng sâu, vùng xa. Chúng tôi sẽ thông báo cho bạn nếu có thay đổi.</p>

<h3>Đóng gói an toàn</h3>
<p>Mỗi sản phẩm được đặt trong hộp quà, chèn vật liệu chống sốc và đóng gói kín bên ngoài để hạn chế va đập trong quá trình vận chuyển. Giấy kiểm định được gửi kèm cùng sản phẩm.</p>

<h3>Đồng kiểm khi nhận hàng</h3>
<p>Bạn được kiểm tra hàng trước khi thanh toán (đồng kiểm). Khi nhận hàng, vui lòng:</p>
<ul>
<li>Kiểm tra bên ngoài kiện hàng còn nguyên vẹn, không móp méo, rách hay có dấu hiệu bị mở.</li>
<li>Mở hộp, kiểm tra sản phẩm đúng mẫu, đúng size, còn nguyên vẹn và đối chiếu với giấy kiểm định.</li>
<li>Nên quay video khi mở hàng để làm căn cứ nếu cần hỗ trợ sau này.</li>
</ul>

<h3>Nếu kiện hàng có dấu hiệu hư hỏng</h3>
<p>Nếu kiện hàng bị móp méo, rách, có dấu hiệu bị mở hoặc sản phẩm bên trong bị nứt vỡ, bạn vui lòng <strong>từ chối nhận hàng</strong> hoặc đề nghị nhân viên giao hàng ghi nhận tình trạng, đồng thời chụp ảnh, quay video và liên hệ ngay với {SITE_NAME} qua {HOTLINE}. Chúng tôi sẽ làm việc với đơn vị vận chuyển và gửi sản phẩm thay thế hoặc hoàn tiền cho bạn.</p>
HTML,
    ],
    'thanh-toan' => [
        'title'   => 'Hình thức thanh toán',
        'content' => <<<'HTML'
<p>{SITE_NAME} hỗ trợ hai hình thức thanh toán đơn giản và an toàn để bạn yên tâm mua sắm.</p>

<h3>1. Thanh toán khi nhận hàng (COD)</h3>
<p>Bạn thanh toán bằng tiền mặt cho nhân viên giao hàng sau khi đã kiểm tra sản phẩm. Đây là hình thức giúp bạn hoàn toàn yên tâm khi mua sắm trực tuyến.</p>

<h3>2. Chuyển khoản ngân hàng</h3>
<p>Thông tin tài khoản ngân hàng của {SITE_NAME} được hiển thị sau khi bạn đặt hàng thành công và trên trang Liên hệ. Khi chuyển khoản, vui lòng:</p>
<ul>
<li>Ghi <strong>mã đơn hàng</strong> trong nội dung chuyển khoản để chúng tôi đối chiếu và xác nhận nhanh chóng.</li>
<li>Kiểm tra kỹ tên chủ tài khoản và số tài khoản trước khi chuyển.</li>
<li>Có thể gửi ảnh chụp giao dịch qua Zalo {HOTLINE} để được xác nhận sớm hơn.</li>
</ul>
<p>Lưu ý: {SITE_NAME} chỉ nhận chuyển khoản vào tài khoản được công bố trên website. Nếu có ai liên hệ đề nghị bạn chuyển tiền vào một tài khoản khác, vui lòng gọi {HOTLINE} để xác minh trước khi chuyển.</p>

<h3>Đặt cọc giữ hàng với sản phẩm giá trị cao</h3>
<p>Với sản phẩm từ {GIA_GIU_TU}, bạn có thể đặt cọc {PHAN_TRAM_COC}% để {SITE_NAME} giữ riêng món ngọc trong {GIO_GIU} giờ. Khoản cọc được trừ trực tiếp vào giá khi mua. Chi tiết xem <a href="#dat-coc">Chính sách đặt cọc – giữ hàng</a>.</p>

<h3>Xác nhận thanh toán</h3>
<p>Sau khi nhận được thanh toán, website gửi bạn <strong>phiếu xác nhận thanh toán</strong> (xem, tải PDF hoặc lưu ảnh), có mã QR để kiểm tra phiếu thật. Mọi thắc mắc về thanh toán, bạn vui lòng liên hệ {HOTLINE} hoặc {EMAIL}.</p>
HTML,
    ],
    'dat-coc' => [
        'title'   => 'Chính sách đặt cọc – giữ hàng',
        'content' => <<<'HTML'
<p><em>Phiên bản {PHIEN_BAN_COC}.</em> Nhiều món ngọc của {SITE_NAME} là độc bản – chỉ có một chiếc. Chính sách này giúp bạn có thời gian cân nhắc mà không lo người khác mua mất, đồng thời bảo vệ quyền lợi của cả hai bên.</p>

<h3>1. Phạm vi áp dụng</h3>
<p>Áp dụng cho sản phẩm có giá từ {GIA_GIU_TU}. Tiền cọc bằng {PHAN_TRAM_COC}% giá bán, là khoản đặt cọc theo Điều 328 Bộ luật Dân sự 2015 để bảo đảm việc mua bán đúng sản phẩm ghi trên Phiếu xác nhận đặt cọc.</p>

<h3>2. Cách đặt cọc</h3>
<ul>
<li>Bạn gửi yêu cầu giữ hàng trên trang sản phẩm và đánh dấu đồng ý với chính sách này. Món ngọc được giữ tạm {PHUT_CHO_COC} phút để bạn chuyển cọc qua mã QR (đúng số tiền và nội dung).</li>
<li>Việc giữ hàng có hiệu lực khi cửa hàng xác nhận đã nhận tiền cọc. Website gửi bạn <strong>Phiếu xác nhận đặt cọc</strong> có mã QR để kiểm tra phiếu thật.</li>
<li>Nếu tiền cọc đến khi sản phẩm đã có người giữ hoặc mua trước, cửa hàng hoàn 100% trong 1 ngày làm việc.</li>
</ul>

<h3>3. Thời hạn giữ hàng</h3>
<p>Sản phẩm được giữ {GIO_GIU} giờ kể từ khi cửa hàng xác nhận nhận cọc. Trong thời hạn này sản phẩm không bán cho người khác và <strong>giá được giữ nguyên</strong>. Bạn được gia hạn miễn phí 1 lần (thêm {GIO_GIA_HAN} giờ) nếu báo cho cửa hàng trước khi hết hạn.</p>

<h3>4. Hoàn tất mua hàng</h3>
<p>Bạn thanh toán phần còn lại tại cửa hàng, hoặc chuyển khoản trước khi hết hạn giữ để được giao hàng theo Chính sách vận chuyển. Tiền cọc được trừ vào giá bán.</p>

<h3>5. Xử lý tiền cọc</h3>
<ul>
<li><strong>Hoàn 100% tiền cọc</strong> khi sản phẩm không đúng mô tả, hình ảnh, video, thông số hoặc giấy kiểm định (loại ngọc, kích thước, trọng lượng, số giấy, khuyết điểm chưa được thông báo), hoặc cửa hàng không thể bán vì sự kiện bất khả kháng, trở ngại khách quan.</li>
<li><strong>Hoàn tiền cọc và trả thêm một khoản bằng tiền cọc</strong> khi cửa hàng tự ý hủy giao dịch, bán sản phẩm cho người khác trong thời hạn giữ, hoặc không bán đúng giá đã xác nhận.</li>
<li><strong>Bạn đổi ý và báo trước khi hết hạn giữ</strong> (kể cả khi đến xem trực tiếp và quyết định không mua): 100% tiền cọc chuyển thành phiếu mua hàng cùng giá trị, dùng trong {THANG_PHIEU_MUA} tháng cho mọi sản phẩm.</li>
<li><strong>Bạn không báo và không hoàn tất mua khi hết hạn giữ:</strong> tiền cọc thuộc về cửa hàng và sản phẩm được mở bán lại.</li>
</ul>

<h3>6. Hoàn tiền</h3>
<p>Tiền được hoàn trong {NGAY_HOAN_TIEN} ngày làm việc kể từ khi xác nhận trường hợp được hoàn, không trừ phí, bằng chuyển khoản về đúng tài khoản đã chuyển cọc. Cửa hàng chỉ hoàn sang tài khoản khác khi chủ tài khoản đặt cọc yêu cầu bằng văn bản hoặc tin nhắn từ số điện thoại đã đăng ký. Sau khi hoàn, cửa hàng gửi bạn mã giao dịch.</p>

<h3>7. Đơn hàng thanh toán toàn bộ</h3>
<p>Món độc bản trong đơn chuyển khoản được giữ {PHUT_CHO_CK} phút chờ thanh toán. Nếu tiền đến sau khi hết thời gian giữ và sản phẩm đã có khách khác mua, cửa hàng hoàn 100% trong 1 ngày làm việc, hoặc bạn chọn sản phẩm khác. Việc đổi trả sau khi mua áp dụng theo Chính sách đổi trả.</p>

<h3>8. Liên hệ và khiếu nại</h3>
<p>Liên hệ {HOTLINE} – {EMAIL} – {ADDRESS}. Cửa hàng phản hồi trong 2 ngày làm việc và giải quyết trong 7 ngày làm việc. Chính sách này không hạn chế quyền khiếu nại, khởi kiện và các quyền khác của bạn theo pháp luật. Việc thay đổi chính sách không áp dụng cho các phiếu đặt cọc đã phát hành.</p>
HTML,
    ],
    'bao-mat' => [
        'title'   => 'Chính sách bảo mật thông tin',
        'content' => <<<'HTML'
<p>{SITE_NAME} tôn trọng và cam kết bảo vệ thông tin cá nhân của bạn. Chính sách này giải thích chúng tôi thu thập những thông tin nào, sử dụng ra sao và quyền của bạn đối với thông tin đó.</p>

<h3>Thông tin chúng tôi thu thập</h3>
<p>Khi bạn đặt hàng hoặc liên hệ với cửa hàng, chúng tôi có thể thu thập:</p>
<ul>
<li>Họ và tên;</li>
<li>Số điện thoại;</li>
<li>Địa chỉ email;</li>
<li>Địa chỉ nhận hàng;</li>
<li>Ghi chú đơn hàng (ví dụ: ni vòng, yêu cầu gói quà, thời gian nhận hàng mong muốn).</li>
</ul>
<p>Chúng tôi không thu thập thông tin thẻ ngân hàng của bạn trên website.</p>

<h3>Mục đích sử dụng</h3>
<ul>
<li>Xác nhận, xử lý và giao đơn hàng;</li>
<li>Liên hệ tư vấn, hỗ trợ đổi trả, bảo hành và chăm sóc khách hàng.</li>
</ul>

<h3>Chia sẻ thông tin</h3>
<p>{SITE_NAME} <strong>không bán, trao đổi hay cho thuê</strong> thông tin của bạn cho bất kỳ bên thứ ba nào. Thông tin chỉ được cung cấp cho đơn vị vận chuyển ở mức cần thiết để giao hàng (họ tên, số điện thoại, địa chỉ nhận hàng), hoặc khi có yêu cầu của cơ quan nhà nước có thẩm quyền theo quy định pháp luật.</p>

<h3>Lưu trữ và bảo mật</h3>
<p>Thông tin của bạn được lưu trữ cẩn thận và chỉ những người phụ trách xử lý đơn hàng mới được truy cập. Chúng tôi lưu giữ thông tin trong thời gian cần thiết để phục vụ đơn hàng, bảo hành và chăm sóc khách hàng, đồng thời áp dụng các biện pháp hợp lý để bảo vệ thông tin khỏi truy cập trái phép.</p>

<h3>Quyền của bạn</h3>
<p>Bạn có quyền yêu cầu xem, chỉnh sửa hoặc xóa thông tin cá nhân của mình bằng cách liên hệ email {EMAIL} hoặc hotline {HOTLINE}. Chúng tôi sẽ phản hồi và xử lý yêu cầu sớm nhất có thể, trừ những thông tin cần lưu giữ theo quy định pháp luật (ví dụ chứng từ bán hàng).</p>

<h3>Thay đổi chính sách</h3>
<p>Chính sách này có thể được cập nhật khi cần thiết. Mọi thay đổi sẽ được đăng tải trên trang này.</p>
HTML,
    ],
];
