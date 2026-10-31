<?php
/**
 * GIẤY KIỂM ĐỊNH — dùng cho trang "Tra cứu giấy kiểm định"
 *  ► Đã sửa mục này trong TRANG QUẢN TRỊ (/quan-tri/) thì website dùng dữ liệu trong data/noi-dung/ – file này chỉ còn là bản mẫu.
 * - Khóa (bên trái =>) là MÃ in trên giấy kiểm định, khách sẽ nhập mã này để tra cứu.
 * - product: mã 'slug' của sản phẩm tương ứng (trong data/products.php). Có thể để '' nếu sản phẩm đã bán.
 * - image:   ảnh chụp/scan giấy kiểm định, tính từ assets/img/ (nên đặt trong assets/img/certificates/).
 * - details: các dòng thông tin bạn muốn hiển thị (ghi đúng như trên giấy).
 * - mau:     true = dữ liệu mẫu minh họa (trang tra cứu sẽ ghi rõ "dữ liệu mẫu").
 *
 * ⚠️ Các mã dưới đây là DỮ LIỆU MẪU. Hãy xóa và thay bằng giấy kiểm định thật trước khi chạy chính thức.
 */
return [
    'VNP-2610-0001' => [
        'product' => 'vong-ngoc-phi-thuy-xanh-luc-de-vuong',
        'lab'     => 'Tên phòng kiểm định (điền đúng như trên giấy)',
        'date'    => '2026-10-01',
        'result'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên – loại A',
        'details' => [
            'Loại sản phẩm' => 'Vòng tay nguyên khối',
            'Màu sắc'       => 'Xanh lục',
            'Kích thước'    => 'Ni 56 mm',
            'Khối lượng'    => '(điền theo giấy)',
        ],
        'image'   => 'certificates/giay-kiem-dinh-mau.svg',
        'mau'     => true,
    ],
    'VNP-2610-0002' => [
        'product' => 'vong-ngoc-phi-thuy-trang-bang',
        'lab'     => 'Tên phòng kiểm định (điền đúng như trên giấy)',
        'date'    => '2026-10-01',
        'result'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên – loại A',
        'details' => [
            'Loại sản phẩm' => 'Vòng tay nguyên khối',
            'Màu sắc'       => 'Trắng băng',
            'Kích thước'    => 'Ni 55 mm',
            'Khối lượng'    => '(điền theo giấy)',
        ],
        'image'   => 'certificates/giay-kiem-dinh-mau.svg',
        'mau'     => true,
    ],
    'VNP-2610-0003' => [
        'product' => 'vong-ngoc-phi-thuy-tim-lavender',
        'lab'     => 'Tên phòng kiểm định (điền đúng như trên giấy)',
        'date'    => '2026-10-02',
        'result'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên – loại A',
        'details' => [
            'Loại sản phẩm' => 'Vòng tay nguyên khối',
            'Màu sắc'       => 'Tím lavender',
            'Kích thước'    => 'Ni 57 mm',
            'Khối lượng'    => '(điền theo giấy)',
        ],
        'image'   => 'certificates/giay-kiem-dinh-mau.svg',
        'mau'     => true,
    ],
    'VNP-2610-0004' => [
        'product' => 'mat-binh-an-khau-xanh-tao',
        'lab'     => 'Tên phòng kiểm định (điền đúng như trên giấy)',
        'date'    => '2026-10-03',
        'result'  => 'Ngọc phỉ thúy (Jadeite) tự nhiên – loại A',
        'details' => [
            'Loại sản phẩm' => 'Mặt dây chuyền bình an khấu',
            'Màu sắc'       => 'Xanh táo',
            'Kích thước'    => 'Đường kính khoảng 20 mm',
            'Khối lượng'    => '(điền theo giấy)',
        ],
        'image'   => 'certificates/giay-kiem-dinh-mau.svg',
        'mau'     => true,
    ],
];
