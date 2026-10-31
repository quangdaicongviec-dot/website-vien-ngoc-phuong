<?php
/**
 * ============================================================
 *  DANH SÁCH SẢN PHẨM
 * ============================================================
 *  ► Đã sửa mục này trong TRANG QUẢN TRỊ (/quan-tri/) thì website dùng dữ liệu trong data/noi-dung/ – file này chỉ còn là bản mẫu.
 *  Đây là sản phẩm MẪU — hãy thay tên, giá, ảnh, mô tả bằng sản phẩm thật của bạn.
 *
 *  Cách thêm sản phẩm: sao chép nguyên một khối [ ... ], dán xuống dưới và sửa lại.
 *
 *  'slug'        Đường dẫn sản phẩm: chữ thường, không dấu, nối bằng dấu "-". KHÔNG được trùng nhau.
 *  'name'        Tên sản phẩm.
 *  'category'    Mã danh mục (xem data/categories.php): vong-ngoc, mat-day-chuyen, nhan-ngoc,
 *                bong-tai, chuoi-hat, vat-pham-phong-thuy
 *  'price'       Giá bán (số, không dấu chấm). Đặt 0 để hiện "Liên hệ".
 *  'old_price'   Giá gốc trước giảm (0 nếu không giảm giá).
 *  'images'      Danh sách ảnh, tính từ thư mục assets/img/ — ảnh đầu tiên là ảnh đại diện.
 *                Nên dùng ảnh vuông (ví dụ 1000x1000), định dạng .jpg hoặc .webp.
 *  'color'       Màu ngọc.
 *  'menh'        Mệnh hợp: 'kim', 'moc', 'thuy', 'hoa', 'tho' (có thể chọn nhiều).
 *                ĐẶT HÀNH CỦA MÀU NGỌC LÊN ĐẦU: xanh lá → ['moc', 'hoa'], trắng → ['kim', 'thuy'],
 *                tím/đỏ/hồng → ['hoa', 'tho'], vàng/nâu → ['tho', 'kim'], đen/xanh dương → ['thuy', 'moc'].
 *                (Phần tử đầu dùng để tránh gợi ý quà có màu kỵ mệnh người nhận.)
 *  'size'        Kích thước / ni.
 *  'material'    Chất liệu.
 *  'stock'       'con-hang' | 'het-hang' | 'dat-truoc' | 'da-ban'
 *                'da-ban' = món độc bản đã có chủ: vẫn hiển thị để tham khảo, nút đổi thành "Đặt mẫu tương tự".
 *  'doc_ban'     true = món độc bản (chỉ 1 chiếc): hiện nhãn "Độc bản", mỗi đơn chỉ mua được 1.
 *  'ni'          (vòng tay) đường kính trong, đơn vị mm, ví dụ 56 — dùng cho bộ lọc "Tìm vòng vừa tay".
 *  'ni_nhan'     (nhẫn) các ni nhẫn có thể làm / điều chỉnh: [ni nhỏ nhất, ni lớn nhất], ví dụ [10, 18];
 *                nhẫn chỉ có 1 ni thì ghi [14, 14] — dùng cho "Tìm nhẫn vừa tay" và ô tính ni trên trang sản phẩm.
 *  'dip'         Dịp tặng quà: 'trao-dau', 'mung-tho', 'tang-me', 'cho-be', 'tan-gia', 'nguoi-thuong'
 *                (xem data/collections.php).
 *  'cert'        Mã giấy kiểm định (khai báo chi tiết trong data/certificates.php). Để '' nếu chưa có.
 *  'video'       Video thật: đường dẫn file .mp4 trong assets/ (ví dụ 'videos/vong-xanh.mp4')
 *                hoặc link YouTube. Để '' nếu chưa có.
 *  'featured'    true = hiện ở mục "Sản phẩm nổi bật" trên trang chủ.
 *  'new'         true = hiện ở mục "Sản phẩm mới" và có nhãn "Mới".
 *  'short'       Mô tả ngắn (1–2 câu).
 *  'description' Mô tả chi tiết. Xuống dòng 2 lần để tách đoạn.
 */
return [

    // ======================= SẢN PHẨM CÓ ẢNH THẬT =======================
    // Tên, màu, mệnh hợp đặt theo ảnh. GIÁ đang là GIÁ MÔ PHỎNG (5–20 triệu) để chạy thử đặt cọc, gợi ý quà…;
    // KÍCH THƯỚC đang điền tạm – hãy sửa cả hai cho đúng trước khi bán thật.
    [
        'slug'      => 'vong-ngoc-trang-hoa-xanh',
        'name'      => 'Vòng ngọc phỉ thúy trắng hoa xanh',
        'category'  => 'vong-ngoc',
        'price'     => 16800000, // GIÁ MÔ PHỎNG – thay bằng giá thật
        'old_price' => 0,
        'images'    => ['products/vong-ngoc-trang-hoa-xanh-1.jpg', 'products/vong-ngoc-trang-hoa-xanh-2.jpg'],
        'color'     => 'Trắng, điểm hoa xanh lục',
        'menh'      => ['kim', 'thuy', 'moc'],
        'size'      => '(cập nhật ni vòng)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['trao-dau', 'tang-me', 'nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Nền ngọc trắng trong, điểm những mảng hoa xanh lục tự nhiên – mỗi chiếc một vân riêng không trùng lặp.',
        'description' => "Vòng được mài nguyên khối từ ngọc phỉ thúy trắng có hoa xanh lục tự nhiên – kiểu vân “phiêu hoa” được nhiều người yêu thích vì vẻ tươi tắn, thanh thoát.

Bản vòng tròn đầy, đánh bóng thủ công, mặt trong mịn ôm tay. Màu xanh phân bố tự nhiên nên mỗi chiếc là độc bản.

Đi kèm giấy kiểm định và hộp quà.",
    ],
    [
        'slug'      => 'mat-rong-ngoc-phi-thuy-trang-diem-xanh',
        'name'      => 'Mặt rồng ngọc phỉ thúy trắng điểm xanh',
        'category'  => 'mat-day-chuyen',
        'price'     => 12500000, // GIÁ MÔ PHỎNG – thay bằng giá thật
        'old_price' => 0,
        'images'    => ['products/mat-ngoc-cham-khac-1.jpg', 'products/mat-ngoc-cham-khac-2.jpg'],
        'color'     => 'Trắng ngà, điểm xanh lục',
        'menh'      => ['kim', 'thuy'],
        'size'      => '(cập nhật kích thước)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['mung-tho', 'nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => true,
        'short'     => 'Rồng chạm nổi tinh xảo trên nền ngọc trắng ngà trong, điểm viên châu xanh lục tự nhiên.',
        'description' => "Mặt ngọc được chạm khắc thủ công trên khối ngọc phỉ thúy trắng ngà: thân rồng uốn lượn với từng đường vảy, mây cuộn và viên châu được chạm nổi sắc nét.

Điểm nhấn là sắc xanh lục tự nhiên ở góc trên. Mặt ngọc dày dặn, các cạnh bo tròn mềm mại khi đeo – mỗi mặt ngọc là độc bản về màu và vân.

Đi kèm giấy kiểm định và hộp quà.",
    ],
    [
        'slug'      => 'mat-binh-an-khau-tim',
        'name'      => 'Mặt bình an khấu ngọc tím chạm mây',
        'category'  => 'mat-day-chuyen',
        'price'     => 8900000, // GIÁ MÔ PHỎNG – thay bằng giá thật
        'old_price' => 0,
        'images'    => ['products/mat-binh-an-khau-tim-1.jpg', 'products/mat-binh-an-khau-tim-2.jpg'],
        'color'     => 'Tím lavender nhạt',
        'menh'      => ['hoa', 'tho'],
        'size'      => '(cập nhật kích thước)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['cho-be', 'tang-me', 'nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Bình an khấu ngọc tím chạm vân mây như ý bao quanh, lòng khấu chạm hồi văn tinh xảo.',
        'description' => "Mặt bình an khấu được chạm khắc kín bề mặt: vân mây như ý uốn lượn đối xứng, nền chấm nhám làm nổi họa tiết, quanh lỗ giữa là vòng hồi văn sắc nét.

Theo quan niệm xưa, bình an khấu hình tròn khép kín tượng trưng cho sự bình an, viên mãn; vân mây như ý mang ý nghĩa mọi điều thuận lợi.

Đi kèm giấy kiểm định và hộp quà.",
    ],
    [
        'slug'      => 'vong-tay-ty-huu-ngoc-mat-xich',
        'name'      => 'Vòng tay tỳ hưu ngọc phỉ thúy mắt xích',
        'category'  => 'vong-ngoc',
        'price'     => 18500000, // GIÁ MÔ PHỎNG – thay bằng giá thật
        'old_price' => 0,
        'images'    => ['products/vong-tay-ty-huu-ngoc-mat-xich-1.jpg', 'products/vong-tay-ty-huu-ngoc-mat-xich-2.jpg'],
        'color'     => 'Xanh đậm vân trắng',
        'menh'      => ['moc', 'hoa'],
        'size'      => '(cập nhật kích thước)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Vòng mắt xích chạm liền khối, điểm nhấn tỳ hưu chạm nổi – kiểu dáng độc đáo, mạnh mẽ.',
        'description' => "Vòng tay gồm các mắt xích ngọc lồng vào nhau, kết thúc bằng linh vật tỳ hưu chạm nổi chi tiết. Sắc xanh đậm xen vân trắng, bề mặt bóng sâu.

Theo quan niệm phong thủy, tỳ hưu là linh vật chiêu tài, giữ lộc.

Đi kèm giấy kiểm định và hộp quà.",
    ],
    [
        'slug'      => 'vong-ngoc-cham-hoi-van',
        'name'      => 'Vòng ngọc phỉ thúy chạm hồi văn',
        'category'  => 'vong-ngoc',
        'price'     => 14200000, // GIÁ MÔ PHỎNG – thay bằng giá thật
        'old_price' => 0,
        'images'    => ['products/vong-ngoc-cham-hoi-van-1.jpg', 'products/vong-ngoc-cham-hoi-van-2.jpg'],
        'color'     => 'Xanh xám vân đen',
        'menh'      => ['thuy', 'moc'],
        'size'      => '(cập nhật ni vòng)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['mung-tho', 'nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Vòng bản dẹt chạm hoa văn hồi văn cổ điển, sắc xanh xám điểm vân đen tự nhiên.',
        'description' => "Mặt vòng được chạm dải hồi văn liên tục – họa tiết cổ tượng trưng cho sự bền vững, trường cửu. Màu ngọc xanh xám điểm những mảng vân đen tự nhiên tạo vẻ trầm mặc, sang trọng.

Phù hợp cả nam và nữ.

Đi kèm giấy kiểm định và hộp quà.",
    ],
    [
        'slug'      => 'ngoc-boi-phuong-hoang-mau-don',
        'name'      => 'Ngọc bội phượng hoàng mẫu đơn',
        'category'  => 'mat-day-chuyen',
        'price'     => 19500000, // GIÁ MÔ PHỎNG – thay bằng giá thật
        'old_price' => 0,
        'images'    => ['products/ngoc-boi-phuong-hoang-mau-don-1.jpg', 'products/ngoc-boi-phuong-hoang-mau-don-2.jpg'],
        'color'     => 'Xanh lục đậm',
        'menh'      => ['moc', 'hoa'],
        'size'      => '(cập nhật kích thước)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['tang-me', 'nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Phượng hoàng và hoa mẫu đơn chạm nổi nhiều lớp trong khung tròn – tác phẩm chạm khắc công phu.',
        'description' => "Ngọc bội tròn với vành dày, lòng chạm nổi phượng hoàng xòe đuôi bên đóa mẫu đơn nở rộ, lá và nụ hoa đan lớp sâu tạo chiều không gian.

Theo quan niệm xưa, phượng hoàng tượng trưng cho điềm lành, mẫu đơn là hoa của phú quý.

Đi kèm giấy kiểm định và hộp quà.",
    ],
    [
        'slug'      => 'mat-phat-di-lac-ngoc-phi-thuy',
        'name'      => 'Mặt Phật Di Lặc ngọc phỉ thúy',
        'category'  => 'mat-day-chuyen',
        'price'     => 9600000, // GIÁ MÔ PHỎNG – thay bằng giá thật
        'old_price' => 0,
        'images'    => ['products/mat-phat-di-lac-ngoc-phi-thuy-1.jpg', 'products/mat-phat-di-lac-ngoc-phi-thuy-2.jpg'],
        'color'     => 'Trắng ngà ánh xanh',
        'menh'      => ['kim', 'thuy'],
        'size'      => '(cập nhật kích thước)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['tang-me', 'mung-tho'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Di Lặc ngồi tựa túi càn khôn, nụ cười hoan hỷ – ngọc trắng ngà ánh xanh trong trẻo.',
        'description' => "Tượng Di Lặc được chạm tròn đầy đặn: nụ cười rạng rỡ, bụng tròn, tay cầm túi càn khôn, nếp áo mềm mại. Chất ngọc trắng ngà ánh xanh, bề mặt bóng mịn.

Theo quan niệm dân gian, Di Lặc mang ý nghĩa an vui, hoan hỷ và đủ đầy.

Đi kèm giấy kiểm định và hộp quà.",
    ],
    [
        'slug'      => 'mat-ngoc-hong-phi-cham-may',
        'name'      => 'Mặt ngọc hồng phỉ chạm mây',
        'category'  => 'mat-day-chuyen',
        'price'     => 6800000, // GIÁ MÔ PHỎNG – thay bằng giá thật
        'old_price' => 0,
        'images'    => ['products/mat-ngoc-hong-phi-cham-may-1.jpg', 'products/mat-ngoc-hong-phi-cham-may-2.jpg'],
        'color'     => 'Đỏ cam (hồng phỉ)',
        'menh'      => ['hoa', 'tho'],
        'size'      => '(cập nhật kích thước)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => true,
        'short'     => 'Sắc đỏ cam rực rỡ của hồng phỉ, chạm kín vân mây cuộn mềm mại.',
        'description' => "Mặt ngọc tròn đầy, chạm kín bề mặt bằng những cuộn vân mây đan xen. Sắc đỏ cam rực rỡ đặc trưng của hồng phỉ – dòng phỉ thúy màu ấm ít gặp.

Theo ngũ hành, sắc đỏ thuộc hành Hỏa.

Đi kèm giấy kiểm định và hộp quà.",
    ],
    [
        'slug'      => 'ngoc-phi-thuy-giu-vo-cham-rong',
        'name'      => 'Ngọc phỉ thúy giữ vỏ chạm rồng',
        'category'  => 'vat-pham-phong-thuy',
        'price'     => 15000000, // GIÁ MÔ PHỎNG – thay bằng giá thật
        'old_price' => 0,
        'images'    => ['products/ngoc-phi-thuy-giu-vo-cham-rong-1.jpg', 'products/ngoc-phi-thuy-giu-vo-cham-rong-2.jpg'],
        'color'     => 'Vỏ nâu đỏ, lõi vàng xanh',
        'menh'      => ['tho', 'kim'],
        'size'      => '(cập nhật kích thước)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['tan-gia', 'mung-tho'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => true,
        'short'     => 'Khối ngọc giữ nguyên lớp vỏ nâu tự nhiên, lõi vàng xanh được chạm rồng uốn lượn – độc bản để bày trưng.',
        'description' => "Nghệ nhân giữ lại lớp vỏ nâu đỏ tự nhiên của khối ngọc thô và khai lộ phần lõi vàng – xanh để chạm hình rồng – cách chạm “tùy sắc” tận dụng màu sẵn có của khối đá.

Thích hợp bày bàn làm việc, phòng khách hoặc làm quà tân gia, khai trương.

Đi kèm giấy kiểm định.",
    ],


    // ======================= VÒNG TAY NGỌC =======================
    [
        'slug'      => 'vong-ngoc-phi-thuy-xanh-luc-de-vuong',
        'name'      => 'Vòng ngọc phỉ thúy xanh lục đế vương',
        'category'  => 'vong-ngoc',
        'price'     => 45000000,
        'old_price' => 0,
        'images'    => ['products/vong-ngoc-phi-thuy-xanh-luc-de-vuong-1.svg', 'products/vong-ngoc-phi-thuy-xanh-luc-de-vuong-2.svg'],
        'color'     => 'Xanh lục đế vương',
        'menh'      => ['moc', 'hoa'],
        'size'      => 'Ni 56 mm (đường kính trong)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'ni'        => 56,
        'dip'       => ['trao-dau', 'mung-tho', 'tang-me'],
        'cert'      => 'VNP-2610-0001',
        'video'     => 'videos/vong-ngoc-xanh-luc-mau.mp4',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Sắc xanh lục đậm, đều màu, thủy tốt – dòng vòng ngọc cao cấp dành cho người sưu tầm.',
        'description' => "Vòng được mài nguyên khối từ một khối ngọc phỉ thúy tự nhiên, sắc xanh lục đậm và đều – gam màu được xem là quý nhất của phỉ thúy.

Bề mặt đánh bóng thủ công, độ trong (thủy) tốt, ánh ngọc lên đều khi soi dưới ánh sáng. Mỗi chiếc vòng là độc bản: màu sắc và vân ngọc có thể khác đôi chút so với ảnh.

Sản phẩm đi kèm giấy kiểm định và hộp quà sang trọng.",
    ],
    [
        'slug'      => 'vong-ngoc-phi-thuy-trang-bang',
        'name'      => 'Vòng ngọc phỉ thúy trắng băng',
        'category'  => 'vong-ngoc',
        'price'     => 18500000,
        'old_price' => 0,
        'images'    => ['products/vong-ngoc-phi-thuy-trang-bang-1.svg', 'products/vong-ngoc-phi-thuy-trang-bang-2.svg'],
        'color'     => 'Trắng băng',
        'menh'      => ['kim', 'thuy'],
        'size'      => 'Ni 55 mm (đường kính trong)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'ni'        => 55,
        'dip'       => ['trao-dau', 'tang-me', 'nguoi-thuong'],
        'cert'      => 'VNP-2610-0002',
        'video'     => '',
        'featured'  => true,
        'new'       => true,
        'short'     => 'Trong như băng, ánh sáng xuyên qua mềm mại – thanh khiết và tinh tế.',
        'description' => "Ngọc trắng băng có độ trong cao, khi đeo lên tay ánh lên vẻ thanh khiết, nhẹ nhàng. Kiểu dáng bản tròn cổ điển, dễ phối với trang phục hằng ngày lẫn áo dài.

Vòng mài nguyên khối, đánh bóng thủ công. Đi kèm giấy kiểm định và hộp quà.",
    ],
    [
        'slug'      => 'vong-ngoc-phi-thuy-tim-lavender',
        'name'      => 'Vòng ngọc phỉ thúy tím lavender',
        'category'  => 'vong-ngoc',
        'price'     => 22000000,
        'old_price' => 25000000,
        'images'    => ['products/vong-ngoc-phi-thuy-tim-lavender-1.svg', 'products/vong-ngoc-phi-thuy-tim-lavender-2.svg'],
        'color'     => 'Tím lavender',
        'menh'      => ['hoa', 'tho'],
        'size'      => 'Ni 57 mm (đường kính trong)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'ni'        => 57,
        'dip'       => ['trao-dau', 'nguoi-thuong'],
        'cert'      => 'VNP-2610-0003',
        'video'     => '',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Sắc tím oải hương dịu dàng, hiếm gặp – món quà ý nghĩa dành cho phái nữ.',
        'description' => "Phỉ thúy tím tự nhiên với sắc tím lavender nhẹ nhàng, nữ tính. Màu tím đều, chất ngọc mịn, ánh sáng lan tỏa đẹp.

Vòng nguyên khối, đi kèm giấy kiểm định và hộp quà sang trọng.",
    ],
    [
        'slug'      => 'vong-ngoc-phi-thuy-xanh-non-chuoi',
        'name'      => 'Vòng ngọc phỉ thúy xanh nõn chuối',
        'category'  => 'vong-ngoc',
        'price'     => 9800000,
        'old_price' => 0,
        'images'    => ['products/vong-ngoc-phi-thuy-xanh-non-chuoi-1.svg', 'products/vong-ngoc-phi-thuy-xanh-non-chuoi-2.svg'],
        'color'     => 'Xanh nõn chuối',
        'menh'      => ['moc', 'hoa'],
        'size'      => 'Ni 54 mm (đường kính trong)',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'ni'        => 54,
        'dip'       => ['tang-me', 'mung-tho'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => true,
        'short'     => 'Xanh non tươi mát xen vân mây trắng tự nhiên, trẻ trung và dễ đeo.',
        'description' => "Sắc xanh nõn chuối tươi mát, điểm xuyết vân mây trắng tự nhiên – mỗi chiếc mang một vẻ riêng.

Phù hợp đeo hằng ngày, làm quà tặng mẹ, tặng người thân. Đi kèm giấy kiểm định và hộp quà.",
    ],
    [
        'slug'      => 'vong-ngoc-phi-thuy-mac-thuy',
        'name'      => 'Vòng ngọc phỉ thúy mặc thúy (đen)',
        'category'  => 'vong-ngoc',
        'price'     => 7500000,
        'old_price' => 0,
        'images'    => ['products/vong-ngoc-phi-thuy-mac-thuy-1.svg', 'products/vong-ngoc-phi-thuy-mac-thuy-2.svg'],
        'color'     => 'Đen (mặc thúy)',
        'menh'      => ['thuy', 'moc'],
        'size'      => 'Ni 60 mm (đường kính trong) – phù hợp nam',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'ni'        => 60,
        'dip'       => ['mung-tho'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => false,
        'short'     => 'Đen bóng mạnh mẽ, soi đèn ánh lên sắc xanh lục – lựa chọn được nam giới ưa chuộng.',
        'description' => "Mặc thúy là dòng phỉ thúy có màu đen bóng khi nhìn thường, nhưng ánh lên sắc xanh lục khi soi đèn mạnh.

Kiểu dáng bản dày, mạnh mẽ, phù hợp cả nam và nữ. Đi kèm giấy kiểm định và hộp quà.",
    ],

    // ======================= MẶT DÂY CHUYỀN =======================
    [
        'slug'      => 'mat-binh-an-khau-xanh-tao',
        'name'      => 'Mặt bình an khấu ngọc xanh táo',
        'category'  => 'mat-day-chuyen',
        'price'     => 3200000,
        'old_price' => 0,
        'images'    => ['products/mat-binh-an-khau-xanh-tao-1.svg', 'products/mat-binh-an-khau-xanh-tao-2.svg'],
        'color'     => 'Xanh táo',
        'menh'      => ['moc', 'hoa'],
        'size'      => 'Đường kính khoảng 20 mm',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A, dây lụa đỏ',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['cho-be', 'tang-me'],
        'cert'      => 'VNP-2610-0004',
        'video'     => '',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Bình an khấu hình tròn khép kín – theo quan niệm xưa mang ý nghĩa bình an, viên mãn.',
        'description' => "Bình an khấu (còn gọi là khóa bình an) có hình tròn, ở giữa có lỗ tròn, tượng trưng cho sự trọn vẹn và bình an.

Mặt ngọc xanh táo tươi sáng, đi kèm dây lụa đỏ có thể điều chỉnh độ dài. Có thể đổi sang dây vàng hoặc bạc theo yêu cầu.",
    ],
    [
        'slug'      => 'mat-ho-lo-ngoc-phi-thuy-xanh-luc',
        'name'      => 'Mặt hồ lô ngọc phỉ thúy xanh lục',
        'category'  => 'mat-day-chuyen',
        'price'     => 4500000,
        'old_price' => 0,
        'images'    => ['products/mat-ho-lo-ngoc-phi-thuy-xanh-luc-1.svg', 'products/mat-ho-lo-ngoc-phi-thuy-xanh-luc-2.svg'],
        'color'     => 'Xanh lục',
        'menh'      => ['moc', 'hoa'],
        'size'      => 'Cao khoảng 28 mm',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A, móc vàng 18K',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['tang-me', 'mung-tho'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => true,
        'short'     => 'Hồ lô – biểu tượng của phúc lộc theo quan niệm dân gian, chế tác mềm mại, tinh xảo.',
        'description' => "Mặt hồ lô được chạm khắc từ ngọc phỉ thúy xanh lục, đường nét bo tròn mềm mại.

Theo quan niệm dân gian, hồ lô tượng trưng cho phúc lộc và sự sung túc. Móc vàng 18K chắc chắn, đi kèm dây đeo.",
    ],
    [
        'slug'      => 'mat-la-bo-de-ngoc-trang',
        'name'      => 'Mặt lá bồ đề ngọc trắng',
        'category'  => 'mat-day-chuyen',
        'price'     => 2800000,
        'old_price' => 0,
        'images'    => ['products/mat-la-bo-de-ngoc-trang-1.svg', 'products/mat-la-bo-de-ngoc-trang-2.svg'],
        'color'     => 'Trắng',
        'menh'      => ['kim', 'thuy'],
        'size'      => 'Cao khoảng 30 mm',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A, móc vàng 18K',
        'stock'     => 'da-ban',
        'doc_ban'   => true,
        'dip'       => ['nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => false,
        'short'     => 'Lá bồ đề chạm gân lá tinh tế trên nền ngọc trắng trong trẻo.',
        'description' => "Mặt lá bồ đề chạm khắc tỉ mỉ từng đường gân lá trên nền ngọc trắng.

Thiết kế thanh nhã, phù hợp đeo hằng ngày. Móc vàng 18K, đi kèm dây chuyền.",
    ],
    [
        'slug'      => 'mat-giot-nuoc-ngoc-vang',
        'name'      => 'Mặt giọt nước ngọc hoàng phỉ bọc vàng 18K',
        'category'  => 'mat-day-chuyen',
        'price'     => 3600000,
        'old_price' => 4200000,
        'images'    => ['products/mat-giot-nuoc-ngoc-vang-1.svg', 'products/mat-giot-nuoc-ngoc-vang-2.svg'],
        'color'     => 'Vàng mật ong (hoàng phỉ)',
        'menh'      => ['tho', 'kim'],
        'size'      => 'Cao khoảng 18 mm',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A, viền vàng 18K',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['tang-me', 'nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Giọt ngọc vàng mật ong ấm áp trong khung viền vàng 18K thanh mảnh.',
        'description' => "Viên ngọc hoàng phỉ mài giọt nước, sắc vàng mật ong ấm áp, được bọc trong khung viền vàng 18K.

Kiểu dáng nhỏ gọn, sang trọng, dễ phối cùng dây chuyền vàng.",
    ],

    // ======================= NHẪN NGỌC =======================
    [
        'slug'      => 'nhan-ngoc-phi-thuy-xanh-luc-bo-vang',
        'name'      => 'Nhẫn ngọc phỉ thúy xanh lục bọc vàng 18K',
        'category'  => 'nhan-ngoc',
        'price'     => 8900000,
        'old_price' => 0,
        'images'    => ['products/nhan-ngoc-phi-thuy-xanh-luc-bo-vang-1.svg', 'products/nhan-ngoc-phi-thuy-xanh-luc-bo-vang-2.svg'],
        'color'     => 'Xanh lục',
        'menh'      => ['moc', 'hoa'],
        'size'      => 'Ni 10 – 18 (làm theo ni tay)',
        'ni_nhan'   => [10, 18],
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A, vàng 18K',
        'stock'     => 'dat-truoc',
        'doc_ban'   => true,
        'dip'       => ['trao-dau', 'nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Viên ngọc oval xanh lục căng mọng trên đai nhẫn vàng 18K cổ điển.',
        'description' => "Viên ngọc mài cabochon oval, sắc xanh lục căng mọng, được giữ chắc chắn trên đai nhẫn vàng 18K.

Nhẫn được làm theo ni tay của bạn, thời gian hoàn thiện khoảng 5–7 ngày.",
    ],
    [
        'slug'      => 'nhan-ngoc-trang-bang-bac-925',
        'name'      => 'Nhẫn ngọc trắng băng bạc 925',
        'category'  => 'nhan-ngoc',
        'price'     => 2400000,
        'old_price' => 0,
        'images'    => ['products/nhan-ngoc-trang-bang-bac-925-1.svg', 'products/nhan-ngoc-trang-bang-bac-925-2.svg'],
        'color'     => 'Trắng băng',
        'menh'      => ['kim', 'thuy'],
        'size'      => 'Ni 9 – 16 (có thể điều chỉnh)',
        'ni_nhan'   => [9, 16],
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A, bạc 925',
        'stock'     => 'con-hang',
        'doc_ban'   => false,
        'dip'       => ['nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => true,
        'short'     => 'Nhẫn bạc 925 đính ngọc trắng băng, nhẹ nhàng và hiện đại.',
        'description' => "Ngọc trắng băng trong trẻo trên đai nhẫn bạc 925 thiết kế tối giản, hiện đại.

Phù hợp đeo hằng ngày, đi làm, đi chơi.",
    ],
    [
        'slug'      => 'nhan-ban-ngoc-nguyen-khoi-xanh',
        'name'      => 'Nhẫn bản ngọc nguyên khối xanh nhạt',
        'category'  => 'nhan-ngoc',
        'price'     => 1800000,
        'old_price' => 0,
        'images'    => ['products/nhan-ban-ngoc-nguyen-khoi-xanh-1.svg', 'products/nhan-ban-ngoc-nguyen-khoi-xanh-2.svg'],
        'color'     => 'Xanh nhạt',
        'menh'      => ['moc', 'hoa'],
        'size'      => 'Ni 10 – 20',
        'ni_nhan'   => [10, 20],
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => false,
        'dip'       => ['nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => false,
        'short'     => 'Nhẫn mài liền khối từ ngọc, đeo đơn hoặc đeo kèm nhẫn khác đều đẹp.',
        'description' => "Nhẫn bản được mài liền khối từ một mảnh ngọc phỉ thúy, không dùng kim loại.

Bề mặt bóng mịn, ôm tay. Vui lòng cung cấp ni tay khi đặt hàng.",
    ],

    // ======================= BÔNG TAI =======================
    [
        'slug'      => 'bong-tai-ngoc-giot-nuoc-xanh',
        'name'      => 'Bông tai ngọc giọt nước xanh lục',
        'category'  => 'bong-tai',
        'price'     => 3500000,
        'old_price' => 0,
        'images'    => ['products/bong-tai-ngoc-giot-nuoc-xanh-1.svg', 'products/bong-tai-ngoc-giot-nuoc-xanh-2.svg'],
        'color'     => 'Xanh lục',
        'menh'      => ['moc', 'hoa'],
        'size'      => 'Giọt ngọc cao khoảng 12 mm',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A, móc vàng 18K',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['tang-me', 'nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => false,
        'short'     => 'Đôi giọt ngọc xanh lục đung đưa nhẹ nhàng, tôn nét thanh lịch.',
        'description' => "Đôi bông tai giọt nước ngọc xanh lục, móc vàng 18K an toàn cho da.

Kiểu dáng thanh lịch, phù hợp áo dài, đầm dự tiệc và cả trang phục công sở.",
    ],
    [
        'slug'      => 'bong-tai-ngoc-tron-trang-bac',
        'name'      => 'Bông tai ngọc tròn trắng bạc 925',
        'category'  => 'bong-tai',
        'price'     => 1650000,
        'old_price' => 0,
        'images'    => ['products/bong-tai-ngoc-tron-trang-bac-1.svg', 'products/bong-tai-ngoc-tron-trang-bac-2.svg'],
        'color'     => 'Trắng',
        'menh'      => ['kim', 'thuy'],
        'size'      => 'Đường kính khoảng 8 mm',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A, bạc 925',
        'stock'     => 'con-hang',
        'doc_ban'   => false,
        'dip'       => ['nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => true,
        'short'     => 'Bông tai nụ ngọc trắng nhỏ gọn, tinh tế cho mọi ngày.',
        'description' => "Bông tai nụ đính ngọc trắng mài tròn, khung bạc 925.

Nhỏ gọn, nhẹ, đeo cả ngày không mỏi.",
    ],

    // ======================= CHUỖI HẠT =======================
    [
        'slug'      => 'chuoi-hat-ngoc-phi-thuy-xanh-8mm',
        'name'      => 'Vòng chuỗi hạt ngọc phỉ thúy xanh 8 mm',
        'category'  => 'chuoi-hat',
        'price'     => 4200000,
        'old_price' => 0,
        'images'    => ['products/chuoi-hat-ngoc-phi-thuy-xanh-8mm-1.svg', 'products/chuoi-hat-ngoc-phi-thuy-xanh-8mm-2.svg'],
        'color'     => 'Xanh lục pha',
        'menh'      => ['moc', 'hoa'],
        'size'      => 'Hạt 8 mm, chu vi theo cổ tay',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A, dây co giãn',
        'stock'     => 'con-hang',
        'doc_ban'   => false,
        'dip'       => ['nguoi-thuong', 'tang-me'],
        'cert'      => '',
        'video'     => '',
        'featured'  => true,
        'new'       => true,
        'short'     => 'Hạt ngọc tròn đều 8 mm, sắc xanh pha tự nhiên, xâu dây co giãn bền chắc.',
        'description' => "Vòng chuỗi hạt ngọc phỉ thúy 8 mm, hạt tròn đều, màu xanh pha tự nhiên – mỗi hạt một sắc độ riêng.

Xâu dây co giãn chất lượng cao, được thay dây miễn phí trọn đời. Vui lòng cho biết chu vi cổ tay khi đặt hàng.",
    ],
    [
        'slug'      => 'chuoi-108-hat-ngoc-phi-thuy',
        'name'      => 'Chuỗi 108 hạt ngọc phỉ thúy',
        'category'  => 'chuoi-hat',
        'price'     => 12000000,
        'old_price' => 0,
        'images'    => ['products/chuoi-108-hat-ngoc-phi-thuy-1.svg', 'products/chuoi-108-hat-ngoc-phi-thuy-2.svg'],
        'color'     => 'Trắng pha xanh',
        'menh'      => ['kim', 'thuy', 'hoa'],
        'size'      => 'Hạt 6 mm, 108 hạt',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['mung-tho'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => false,
        'short'     => 'Chuỗi 108 hạt ngọc trắng pha xanh, có thể đeo cổ hoặc quấn nhiều vòng ở tay.',
        'description' => "Chuỗi 108 hạt ngọc phỉ thúy trắng pha xanh, hạt 6 mm tròn đều, kèm tua trang trí.

Có thể đeo cổ hoặc quấn nhiều vòng ở cổ tay.",
    ],
    [
        'slug'      => 'chuoi-hat-ngoc-tim-lavender-6mm',
        'name'      => 'Vòng chuỗi hạt ngọc tím lavender 6 mm',
        'category'  => 'chuoi-hat',
        'price'     => 3800000,
        'old_price' => 0,
        'images'    => ['products/chuoi-hat-ngoc-tim-lavender-6mm-1.svg', 'products/chuoi-hat-ngoc-tim-lavender-6mm-2.svg'],
        'color'     => 'Tím lavender',
        'menh'      => ['hoa', 'tho'],
        'size'      => 'Hạt 6 mm, chu vi theo cổ tay',
        'material'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A, dây co giãn',
        'stock'     => 'con-hang',
        'doc_ban'   => false,
        'dip'       => ['nguoi-thuong'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => false,
        'short'     => 'Hạt ngọc tím nhỏ xinh, nhẹ nhàng nữ tính, dễ đeo cùng vòng ngọc khác.',
        'description' => "Vòng hạt ngọc phỉ thúy tím lavender 6 mm, phù hợp đeo riêng hoặc mix cùng vòng ngọc nguyên khối.

Thay dây miễn phí trọn đời.",
    ],

    // ======================= VẬT PHẨM PHONG THỦY =======================
    [
        'slug'      => 'qua-cau-ngoc-phi-thuy-de-ban',
        'name'      => 'Quả cầu ngọc phỉ thúy để bàn',
        'category'  => 'vat-pham-phong-thuy',
        'price'     => 6500000,
        'old_price' => 0,
        'images'    => ['products/qua-cau-ngoc-phi-thuy-de-ban-1.svg', 'products/qua-cau-ngoc-phi-thuy-de-ban-2.svg'],
        'color'     => 'Xanh lục vân mây',
        'menh'      => ['moc', 'hoa'],
        'size'      => 'Đường kính khoảng 6 cm, kèm đế gỗ',
        'material'  => 'Ngọc phỉ thúy tự nhiên, đế gỗ',
        'stock'     => 'con-hang',
        'doc_ban'   => true,
        'dip'       => ['tan-gia', 'mung-tho'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => true,
        'short'     => 'Quả cầu ngọc vân mây tự nhiên trên đế gỗ chạm, trang trí bàn làm việc, phòng khách.',
        'description' => "Quả cầu được mài tròn từ ngọc phỉ thúy tự nhiên, vân mây xanh trắng đan xen, đặt trên đế gỗ chạm.

Theo quan niệm phong thủy, quả cầu tượng trưng cho sự viên mãn, hanh thông. Thích hợp làm quà tân gia, khai trương.",
    ],
    [
        'slug'      => 'ngoc-boi-binh-an-treo-xe',
        'name'      => 'Ngọc bội bình an treo xe',
        'category'  => 'vat-pham-phong-thuy',
        'price'     => 1950000,
        'old_price' => 0,
        'images'    => ['products/ngoc-boi-binh-an-treo-xe-1.svg', 'products/ngoc-boi-binh-an-treo-xe-2.svg'],
        'color'     => 'Xanh lục',
        'menh'      => ['moc', 'hoa'],
        'size'      => 'Dài khoảng 25 cm (gồm nút thắt và tua)',
        'material'  => 'Ngọc phỉ thúy tự nhiên, dây thắt nút đỏ',
        'stock'     => 'con-hang',
        'doc_ban'   => false,
        'dip'       => ['tan-gia'],
        'cert'      => '',
        'video'     => '',
        'featured'  => false,
        'new'       => false,
        'short'     => 'Bình an khấu ngọc xanh kết nút đỏ và tua dài – món quà mang lời chúc thượng lộ bình an.',
        'description' => "Ngọc bội bình an treo xe gồm bình an khấu ngọc xanh, nút thắt đỏ thủ công và tua dài.

Treo trên gương chiếu hậu ô tô hoặc trang trí trong nhà, mang lời chúc bình an cho mỗi chuyến đi.",
    ],
];
