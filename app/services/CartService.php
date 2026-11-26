<?php
// app/services/CartService.php
// Doc lai gio hang tu session, doi chieu CSDL va tra ve du lieu SAN SANG hien thi.
// Day la noi DUY NHAT tinh tien gio hang (tien hang, giam gia, phi van chuyen, tong cong)
// - view KHONG tu tinh toan gi them. Session chi luu product_id, quantity, MA giam gia va
// KEY phuong thuc van chuyen; moi so tien deu duoc tinh lai o day tu CSDL va config.
class CartService
{
    /**
     * @return array{
     *   lines: array<int,array>, subtotal: float, adjusted: bool, removed: string[],
     *   coupon: ?string, coupon_note: ?string, discount: float,
     *   shipping: array{method: string, label: string, fee: float, free: bool},
     *   shipping_options: array<string,array>, total: float
     * }
     */
    public static function build(): array
    {
        $items = Cart::items();
        if (empty($items)) {
            return self::withTotals([], 0.0, false, [], false);
        }

        // MOT truy van duy nhat lay toan bo san pham co trong gio, kem anh dai dien
        $products = Product::findManyWithImage(array_keys($items));

        $lines = [];
        $subtotal = 0.0;
        $adjusted = false;
        $removed = [];

        foreach ($items as $productId => $qty) {
            $productId = (int) $productId;
            $product = $products[$productId] ?? null;

            // San pham khong con ton tai hoac da ngung kinh doanh -> loai khoi gio
            if (!$product || $product['status'] !== 'active') {
                Cart::remove($productId);
                $removed[] = $product['name'] ?? ('#' . $productId);
                continue;
            }

            $qty = (int) $qty;
            $stock = (int) $product['stock'];

            // So luong KHONG duoc vuot ton kho; vuot thi tu dieu chinh ve toi da
            // va cap nhat lai session cho dung voi thuc te, dong thoi ghi nhan de bao cho khach.
            if ($qty > $stock) {
                $adjusted = true;
                if ($stock <= 0) {
                    Cart::remove($productId);
                    $removed[] = $product['name'];
                    continue;
                }
                $qty = $stock;
                Cart::setQuantity($productId, $qty);
            }

            $lineTotal = (float) $product['price'] * $qty;
            $subtotal += $lineTotal;
            $lines[] = [
                'product_id' => $productId,
                'name' => $product['name'],
                'price' => (float) $product['price'],
                'quantity' => $qty,
                'stock' => $stock,
                'line_total' => $lineTotal,
                'image' => $product['image'],
            ];
        }

        return self::withTotals($lines, $subtotal, $adjusted, $removed, true);
    }

    /** Chuan hoa ma giam gia nguoi dung go: bo khoang trang, viet hoa, chi cho phep A-Z 0-9 _ - */
    public static function normalizeCoupon(string $code): string
    {
        $code = strtoupper(trim($code));
        return preg_match('/^[A-Z0-9_-]{1,30}$/', $code) ? $code : '';
    }

    /**
     * Kiem tra mot ma giam gia voi tien hang hien tai. Du lieu ma lay tu config (COUPONS),
     * khong bao gio tu trinh duyet.
     *
     * @return array{ok: bool, discount: float, message: string}
     */
    public static function evaluateCoupon(string $code, float $subtotal): array
    {
        $fail = fn(string $msg): array => ['ok' => false, 'discount' => 0.0, 'message' => $msg];

        if ($code === '') {
            return $fail('Vui lòng nhập mã giảm giá hợp lệ.');
        }
        $coupon = COUPONS[$code] ?? null;
        if ($coupon === null) {
            return $fail('Mã giảm giá "' . $code . '" không tồn tại.');
        }
        if (!empty($coupon['expires']) && date('Y-m-d') > $coupon['expires']) {
            return $fail('Mã giảm giá "' . $code . '" đã hết hạn.');
        }
        $minOrder = (float) ($coupon['min_order'] ?? 0);
        if ($subtotal < $minOrder) {
            return $fail('Mã "' . $code . '" chỉ áp dụng cho đơn hàng từ ' . format_vnd($minOrder) . '.');
        }

        if (($coupon['type'] ?? '') === 'percent') {
            $discount = floor($subtotal * (float) $coupon['value'] / 100);
            if (isset($coupon['max_discount'])) {
                $discount = min($discount, (float) $coupon['max_discount']);
            }
        } else {
            $discount = (float) ($coupon['value'] ?? 0);
        }
        $discount = max(0.0, min($discount, $subtotal));

        return [
            'ok' => true,
            'discount' => $discount,
            'message' => 'Đã áp dụng mã ' . $code . ': giảm ' . format_vnd($discount) . '.',
        ];
    }

    /** Gan them giam gia, phi van chuyen va tong cong vao ket qua. */
    private static function withTotals(array $lines, float $subtotal, bool $adjusted, array $removed, bool $hasItems): array
    {
        $couponCode = Cart::coupon();
        $couponNote = null;
        $discount = 0.0;

        // Ma da luu trong session nhung khong con hop le (het han, tien hang giam xuong
        // duoi muc toi thieu...) thi go bo va bao cho khach.
        if ($couponCode !== null && $hasItems) {
            $result = self::evaluateCoupon($couponCode, $subtotal);
            if ($result['ok']) {
                $discount = $result['discount'];
            } else {
                Cart::clearCoupon();
                $couponNote = $result['message'] . ' Mã đã được gỡ khỏi giỏ hàng.';
                $couponCode = null;
            }
        }

        $afterDiscount = max(0.0, $subtotal - $discount);

        $selected = Cart::shipping();
        if ($selected === null || !isset(SHIPPING_METHODS[$selected])) {
            $selected = SHIPPING_DEFAULT;
        }

        $options = [];
        foreach (SHIPPING_METHODS as $key => $method) {
            $baseFee = (float) $method['fee'];
            $freeOver = $method['free_over'] ?? null;
            $free = $hasItems && $freeOver !== null && $afterDiscount >= (float) $freeOver;
            $options[$key] = [
                'label' => $method['label'],
                'base_fee' => $baseFee,
                'free_over' => $freeOver,
                'free' => $free,
                'fee' => $hasItems ? ($free ? 0.0 : $baseFee) : 0.0,
            ];
        }
        $shipping = [
            'method' => $selected,
            'label' => $options[$selected]['label'],
            'fee' => $options[$selected]['fee'],
            'free' => $options[$selected]['free'],
        ];

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'adjusted' => $adjusted,
            'removed' => $removed,
            'coupon' => $couponCode,
            'coupon_note' => $couponNote,
            'discount' => $discount,
            'shipping' => $shipping,
            'shipping_options' => $options,
            'total' => $afterDiscount + $shipping['fee'],
        ];
    }
}
