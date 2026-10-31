<?php
/**
 * BANNER TRANG CHỦ (slide trượt) — có 2 kiểu:
 *  ► Đã sửa mục này trong TRANG QUẢN TRỊ (/quan-tri/) thì website dùng dữ liệu trong data/noi-dung/ – file này chỉ còn là bản mẫu.
 *
 * 1) KIỂU ẢNH NGUYÊN TẤM ('type' => 'image'): ảnh banner đã có sẵn chữ (thiết kế bằng Canva…).
 *    - image:        ảnh cho máy tính, tỉ lệ ngang (16:9 như 1672x941, hoặc 2:1), dạng .jpg, tính từ assets/img/
 *    - image_mobile: (tuỳ chọn) ảnh dọc riêng cho điện thoại cầm dọc, cỡ 1080x1350 (tỉ lệ 4:5). Máy tính vẫn dùng 'image'.
 *                    Khi banner ảnh ĐẦU TIÊN có ảnh dọc: banner ảnh nào để trống ô này sẽ TẠM ẨN trên điện thoại
 *                    (ảnh ngang trong khung dọc bị thừa 2 dải trống) → nên làm ảnh dọc cho mọi banner ảnh, cùng cỡ 1080x1350.
 *    - focus:        (ít dùng) vị trí căn ảnh trong khung, mặc định '50% 50%' = chính giữa
 *    Banner rộng hết màn hình, cao theo đúng tỉ lệ ảnh → ảnh luôn hiển thị TRỌN VẸN ở mọi kích thước.
 *    Khung lấy tỉ lệ của banner ảnh đầu tiên → các banner ảnh PHẢI cùng kích thước (ví dụ cùng 1672x941).
 *    - fill:         (tuỳ chọn) màu nền, chỉ thấy khi banner này khác tỉ lệ với banner ảnh đầu tiên.
 *    - title:        mô tả ngắn của banner (dùng cho người khiếm thị và Google, không hiện trên ảnh)
 *
 * 2) KIỂU CHỮ + HÌNH MINH HỌA (mặc định): eyebrow (dòng nhỏ), title, text, button, link,
 *    image (ảnh sản phẩm nền trong suốt, khoảng 900x700), background (tuỳ chọn: ảnh nền 1920x800).
 *
 * link: đường dẫn tương đối, ví dụ 'danh-muc/vong-ngoc', 'san-pham/ma-san-pham', 'san-pham?sap-xep=moi'
 *
 * Thời gian mỗi slide: sửa số DELAY trong assets/js/main.js (4000 = 4 giây).
 */
return [
    [
        'type'         => 'image',
        'title'        => 'Bộ sưu tập ngọc phỉ thúy cao cấp – sản phẩm mới',
        'image'        => 'banners/bo-suu-tap-ngoc-phi-thuy-cao-cap.jpg',
        'image_mobile' => 'banners/bo-suu-tap-ngoc-phi-thuy-cao-cap-dien-thoai.jpg',
        'focus'        => '50% 50%',
        'fill'         => '',
        'link'         => 'san-pham?sap-xep=moi',
    ],
    [
        'type'         => 'image',
        'title'        => 'Bộ sưu tập ngọc phỉ thúy xa hoa – mặt dây chuyền ngọc bội',
        'image'        => 'banners/bo-suu-tap-ngoc-phi-thuy-xa-hoa.jpg',
        'image_mobile' => 'banners/bo-suu-tap-ngoc-phi-thuy-xa-hoa-dien-thoai.jpg',
        'focus'        => '50% 50%',
        'fill'         => '',
        'link'         => 'danh-muc/mat-day-chuyen',
    ],

    // ---- Banner kiểu chữ + hình minh họa ----
    [
        'eyebrow'  => 'Bộ sưu tập vòng ngọc',
        'title'    => 'Vòng ngọc phỉ thúy nguyên khối',
        'text'     => 'Mài từ một khối ngọc tự nhiên loại A, mỗi chiếc vòng là một độc bản – trao gửi bình an và may mắn.',
        'button'   => 'Khám phá vòng ngọc',
        'link'     => 'danh-muc/vong-ngoc',
        'image'    => 'banners/hero-vong.webp',
        'background' => '',
    ],
    [
        'eyebrow'  => 'Chạm khắc thủ công',
        'title'    => 'Rồng, phượng & bình an khấu',
        'text'     => 'Từng đường nét được nghệ nhân chạm tay tỉ mỉ trên ngọc phỉ thúy tự nhiên – mỗi mặt ngọc là một độc bản.',
        'button'   => 'Xem mặt dây chuyền',
        'link'     => 'danh-muc/mat-day-chuyen',
        'image'    => 'banners/hero-cham-khac.webp',
        'background' => '',
    ],
    [
        'eyebrow'  => 'Ngọc hợp mệnh',
        'title'    => 'Chọn sắc ngọc hợp bản mệnh',
        'text'     => 'Tra cứu mệnh theo năm sinh và gợi ý màu ngọc phù hợp theo ngũ hành Kim – Mộc – Thủy – Hỏa – Thổ.',
        'button'   => 'Tra cứu ngay',
        'link'     => '#tra-cuu-menh',
        'image'    => 'banners/hero-ngu-hanh.webp',
        'background' => '',
    ],
];
