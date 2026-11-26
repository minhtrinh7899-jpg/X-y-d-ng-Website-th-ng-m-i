<?php
// config/config.php
// Cau hinh ket noi CSDL va cac hang so dung chung toan he thong

define('DB_HOST', 'localhost');
define('DB_NAME', 'shop_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Doi lai cho dung may cua ban, vi du: http://localhost/shop/public/
define('BASE_URL', 'http://localhost/shop/public/');

// Cau hinh cho phan quan ly anh san pham
define('UPLOAD_ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2 MB
define('UPLOAD_MIN_SIDE', 300);   // px
define('UPLOAD_MAX_SIDE', 5000);  // px
define('UPLOAD_MAX_IMAGES', 8);   // anh / san pham
define('IMAGE_MAIN_WIDTH', 1200); // px - anh chinh
define('IMAGE_THUMB_WIDTH', 400); // px - anh thu nho

// Gio hang (Buoi 6): ma giam gia va phuong thuc van chuyen.
// Day la DU LIEU CO DINH trong ma nguon; trinh duyet chi gui len MA / KEY, khong bao gio gui so tien.
// type: 'percent' (giam theo %, co the gioi han bang max_discount) hoac 'fixed' (giam so tien co dinh)
// min_order: tien hang toi thieu de dung ma; expires: ngay het han Y-m-d (bo trong neu khong het han)
define('COUPONS', [
    'GIAM10'  => ['type' => 'percent', 'value' => 10, 'max_discount' => 200000, 'min_order' => 200000],
    'GIAM50K' => ['type' => 'fixed', 'value' => 50000, 'min_order' => 300000],
]);

// fee: phi van chuyen; free_over: mien phi khi tien hang SAU giam gia dat muc nay (null = khong bao gio mien phi)
define('SHIPPING_METHODS', [
    'standard' => ['label' => 'Giao tiêu chuẩn', 'fee' => 30000, 'free_over' => 500000],
    'express'  => ['label' => 'Giao nhanh', 'fee' => 50000, 'free_over' => null],
]);
define('SHIPPING_DEFAULT', 'standard');
