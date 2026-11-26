<?php
// app/models/Cart.php
// Gio hang luu trong session. CHI luu product_id va quantity - KHONG BAO GIO luu gia,
// ten hay anh san pham (nhung thu nay luon duoc doc LAI tu CSDL moi lan hien thi,
// xem app/services/CartService.php).
class Cart
{
    private const SESSION_KEY = 'cart';
    private const COUPON_KEY = 'cart_coupon';
    private const SHIPPING_KEY = 'cart_shipping';
    private static function ensure(): void
    {
        if (
            !isset($_SESSION[self::SESSION_KEY]) ||
            !is_array($_SESSION[self::SESSION_KEY])
        ) {
            $_SESSION[self::SESSION_KEY] = [];
        }
    }
    /** Toan bo gio hang hien co: [product_id => quantity] */
    public static function items(): array
    {
        self::ensure();
        return $_SESSION[self::SESSION_KEY];
    }

    /** Them san pham vao gio; neu da co san trong gio thi CONG DON so
luong */
    public static function add(int $productId, int $qty): void
    {
        self::ensure();
        $qty = max(1, $qty);
        $current = $_SESSION[self::SESSION_KEY][$productId] ?? 0;
        $_SESSION[self::SESSION_KEY][$productId] = $current + $qty;
    }
    /** Dat so luong CHINH XAC cho 1 san pham (dung khi sua so luong tai
trang gio hang) */
    public static function setQuantity(int $productId, int $qty): void
    {
        self::ensure();
        if ($qty <= 0) {
            unset($_SESSION[self::SESSION_KEY][$productId]);
            return;
        }
        $_SESSION[self::SESSION_KEY][$productId] = $qty;
    }
    public static function remove(int $productId): void
    {
        self::ensure();
        unset($_SESSION[self::SESSION_KEY][$productId]);
    }
    public static function clear(): void
    {
        $_SESSION[self::SESSION_KEY] = [];
        unset($_SESSION[self::COUPON_KEY]);
    }
    /** Tong so luong san pham trong gio - dung cho bieu tuong gio hang
tren menu */
    public static function totalQuantity(): int
    {
        self::ensure();
        return (int) array_sum($_SESSION[self::SESSION_KEY]);
    }

    /** Ma giam gia dang ap dung (chi luu MA, so tien giam luon tinh lai trong CartService) */
    public static function coupon(): ?string
    {
        $code = $_SESSION[self::COUPON_KEY] ?? null;
        return is_string($code) && $code !== '' ? $code : null;
    }

    public static function setCoupon(string $code): void
    {
        $_SESSION[self::COUPON_KEY] = $code;
    }

    public static function clearCoupon(): void
    {
        unset($_SESSION[self::COUPON_KEY]);
    }

    /** KEY phuong thuc van chuyen da chon (vi du 'standard'), null neu chua chon */
    public static function shipping(): ?string
    {
        $method = $_SESSION[self::SHIPPING_KEY] ?? null;
        return is_string($method) ? $method : null;
    }

    public static function setShipping(string $method): void
    {
        $_SESSION[self::SHIPPING_KEY] = $method;
    }
}
