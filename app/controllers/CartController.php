<?php
// app/controllers/CartController.php
class CartController extends Controller
{
    public function index(): void
    {
        $cart = CartService::build();
        // flash() dung chung 1 khoa 'error' nen gop moi thong bao thanh MOT lan (layout in thang, cho phep <br>)
        $notices = [];
        if ($cart['adjusted']) {
            $notices[] = 'Một số sản phẩm trong giỏ đã được điều chỉnh số lượng cho đúng tồn kho hiện tại.';
        }
        if (!empty($cart['removed'])) {
            $notices[] = 'Đã bỏ khỏi giỏ hàng vì ngừng kinh doanh hoặc không còn tồn tại: ' . e(implode(', ', $cart['removed']));
        }
        if (!empty($cart['coupon_note'])) {
            $notices[] = e($cart['coupon_note']);
        }
        if ($notices) {
            flash('error', implode('<br>', $notices));
        }
        $this->view('cart/index', [
            'pageTitle' => 'Giỏ hàng',
            'cart' => $cart,
        ]);
    }
    /** Them san pham vao gio. Ho tro ca goi AJAX (tra JSON) lan gui form thuong (redirect). */
    public function add(): void
    {
        verify_csrf($_POST['csrf_token'] ?? '');
        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty = max(1, (int) ($_POST['quantity'] ?? 1));
        $product = Product::find($productId);
        if (!$product || $product['status'] !== 'active') {
            $this->respond(false, 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.');
            return;
        }
        $stock = (int) $product['stock'];
        $currentQty = (int) (Cart::items()[$productId] ?? 0);
        $newQty = $currentQty + $qty;
        if ($stock <= 0) {
            $this->respond(false, 'Sản phẩm đã hết hàng.');
            return;
        }
        if ($newQty > $stock) {
            Cart::setQuantity($productId, $stock);
            $this->respond(true, 'Đã thêm vào giỏ (số lượng được điều chỉnh về tối đa còn lại: ' . $stock . ').');
            return;
        }
        Cart::add($productId, $qty);
        $this->respond(true, 'Đã thêm vào giỏ hàng.');
    }
    public function update(): void
    {
        verify_csrf($_POST['csrf_token'] ?? '');
        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty = (int) ($_POST['quantity'] ?? 0);
        $product = Product::find($productId);
        if ($product && $product['status'] === 'active' && $qty > 0) {
            $qty = min($qty, (int) $product['stock']);
            Cart::setQuantity($productId, $qty);
        } else {
            Cart::remove($productId);
        }
        $this->redirect('cart/index');
    }
    public function remove(): void
    {
        verify_csrf($_POST['csrf_token'] ?? '');
        Cart::remove((int) ($_POST['product_id'] ?? 0));
        $this->redirect('cart/index');
    }
    public function clear(): void
    {
        verify_csrf($_POST['csrf_token'] ?? '');
        Cart::clear();
        $this->redirect('cart/index');
    }
    /** Ap dung ma giam gia: chi luu MA vao session; so tien giam luon duoc CartService tinh lai. */
    public function applyCoupon(): void
    {
        verify_csrf($_POST['csrf_token'] ?? '');
        $code = CartService::normalizeCoupon((string) ($_POST['coupon_code'] ?? ''));
        $result = CartService::evaluateCoupon($code, (float) CartService::build()['subtotal']);
        if ($result['ok']) {
            Cart::setCoupon($code);
            flash('success', e($result['message']));
        } else {
            flash('error', e($result['message']));
        }
        $this->redirect('cart/index');
    }

    public function removeCoupon(): void
    {
        verify_csrf($_POST['csrf_token'] ?? '');
        Cart::clearCoupon();
        $this->redirect('cart/index');
    }

    /** Chon phuong thuc van chuyen: chi chap nhan KEY co trong SHIPPING_METHODS (config). */
    public function shipping(): void
    {
        verify_csrf($_POST['csrf_token'] ?? '');
        $method = (string) ($_POST['shipping_method'] ?? '');
        if (isset(SHIPPING_METHODS[$method])) {
            Cart::setShipping($method);
        } else {
            flash('error', 'Phương thức vận chuyển không hợp lệ.');
        }
        $this->redirect('cart/index');
    }

    /** Goi AJAX (co header X-Requested-With) nhan ve JSON; form thuong thi redirect kem flash. */
    private function respond(bool $success, string $message): void
    {
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') ===
            'XMLHttpRequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $success,
                'message' => $message,
                'cart_count' => Cart::totalQuantity(),
            ]);
            exit;
        }
        flash($success ? 'success' : 'error', $message);
        $this->redirect('product/detail/' . (int) ($_POST['product_id'] ??
            0));
    }
}
