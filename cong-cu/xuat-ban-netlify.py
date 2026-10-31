#!/usr/bin/env python3
"""
XUẤT BẢN CHẠY THỬ CHO NETLIFY
--------------------------------
Biến website PHP thành các trang HTML tĩnh để đưa lên Netlify (Netlify không chạy PHP).
Các tính năng cần máy chủ được thay bằng assets/js/demo-static.js (chạy trên trình duyệt).

Cách dùng (trong Terminal, tại thư mục dự án):
    python3 cong-cu/xuat-ban-netlify.py [địa-chỉ-netlify] [thư-mục-xuất]
Ví dụ:
    python3 cong-cu/xuat-ban-netlify.py https://vien-ngoc-phuong-demo.netlify.app

Kết quả: thư mục + file .zip, kéo-thả lên https://app.netlify.com/drop
Yêu cầu: PHP 8 (lệnh `php`) và Python 3 trên máy.
"""
import os, re, shutil, subprocess, sys, time, urllib.request, urllib.error, zipfile
from urllib.parse import urlparse

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PUBLIC_URL = (sys.argv[1] if len(sys.argv) > 1 else 'https://vien-ngoc-phuong-demo.netlify.app').rstrip('/')
OUT = os.path.abspath(sys.argv[2]) if len(sys.argv) > 2 else os.path.join(os.path.dirname(ROOT), 'ban-chay-thu-netlify', 'site')
PORT = 8096
LOCAL = f'http://127.0.0.1:{PORT}'


def get(path):
    with urllib.request.urlopen(LOCAL + path, timeout=30) as r:
        return r.status, r.read()


def target_file(path):
    """/ -> index.html ; /a/b -> a/b/index.html"""
    p = path.split('?')[0].strip('/')
    return os.path.join(OUT, p, 'index.html') if p else os.path.join(OUT, 'index.html')


def save(rel, data):
    full = rel if os.path.isabs(rel) else os.path.join(OUT, rel)
    os.makedirs(os.path.dirname(full), exist_ok=True)
    with open(full, 'wb') as f:
        f.write(data)


def main():
    if os.path.exists(OUT):
        shutil.rmtree(OUT)
    os.makedirs(OUT)
    env = dict(os.environ, VNP_STATIC='1', VNP_STATIC_URL=PUBLIC_URL)
    server = subprocess.Popen(['php', '-S', f'127.0.0.1:{PORT}', '-t', ROOT, os.path.join(ROOT, 'cong-cu', 'router.php')],
                              env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        for _ in range(50):
            try:
                get('/robots.txt'); break
            except Exception:
                time.sleep(0.1)

        # Danh sách trang: lấy từ sitemap (sản phẩm, danh mục, bộ sưu tập, bài viết, trang thông tin)
        _, xml = get('/sitemap.php')
        pages = [urlparse(u).path or '/' for u in re.findall(r'<loc>([^<]+)</loc>', xml.decode())]
        pages += ['/gio-hang', '/yeu-thich']
        pages = list(dict.fromkeys(pages))

        problems = []
        for path in pages:
            status, html = get(path)
            if status != 200 or re.search(rb'(Fatal error|Warning|Deprecated|Notice):', html):
                problems.append(path)
            save(target_file(path), html)

        # Trang khung kết quả đặt hàng / giữ hàng, trang 404
        save('dat-hang/index.html', get('/dat-hang.php?demo=1')[1])
        save('giu-hang/index.html', get('/giu-hang.php?demo=1')[1])
        save('phieu/index.html', get('/phieu.php?demo=1')[1])
        try:
            get('/404.php')
        except urllib.error.HTTPError as e:
            save('404.html', e.read())

        # Dữ liệu cho trình duyệt + mảnh HTML cho xem nhanh, yêu thích, gợi ý quà
        import json
        _, raw = get('/demo-data.php')
        save('demo-data.json', raw)
        slugs = list(json.loads(raw)['products'].keys())
        save('the-san-pham.html', get('/the-san-pham.php?ds=' + ','.join(slugs))[1])
        for s in slugs:
            save(f'xem-nhanh/{s}.html', get(f'/xem-nhanh.php?sp={s}')[1])

        # Tài nguyên tĩnh
        shutil.copytree(os.path.join(ROOT, 'assets'), os.path.join(OUT, 'assets'), ignore=shutil.ignore_patterns('.DS_Store'))
        shutil.copy(os.path.join(ROOT, 'favicon.ico'), OUT)

        # Không cho Google lập chỉ mục bản chạy thử
        save('robots.txt', b'User-agent: *\nDisallow: /\n')
        save('_headers', b'/*\n  X-Robots-Tag: noindex, nofollow\n')

        # Kiểm tra liên kết nội bộ trỏ tới file .php (không chạy được trên Netlify)
        php_links = set()
        for dirpath, _, files in os.walk(OUT):
            for fn in files:
                if fn.endswith('.html'):
                    txt = open(os.path.join(dirpath, fn), encoding='utf-8', errors='ignore').read()
                    php_links.update(re.findall(r'href="(/[^"]*\.php[^"]*)"', txt))

        zpath = os.path.join(os.path.dirname(OUT), 'ban-chay-thu-netlify.zip')
        with zipfile.ZipFile(zpath, 'w', zipfile.ZIP_DEFLATED) as z:
            for dirpath, _, files in os.walk(OUT):
                for fn in files:
                    full = os.path.join(dirpath, fn)
                    z.write(full, os.path.relpath(full, OUT))

        count = sum(len(f) for _, _, f in os.walk(OUT))
        print(f'Đã xuất {len(pages)} trang, tổng {count} file -> {OUT}')
        print(f'File zip: {zpath} ({os.path.getsize(zpath) // 1024} KB)')
        print('Trang có lỗi PHP:', problems or 'không có')
        print('Liên kết .php còn sót:', sorted(php_links) or 'không có')
    finally:
        server.terminate()


if __name__ == '__main__':
    main()
