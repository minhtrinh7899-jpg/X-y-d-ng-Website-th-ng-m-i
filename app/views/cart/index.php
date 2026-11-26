<h1 class="h4 mb-4">Giỏ hàng của bạn</h1>
<?php if (empty($cart['lines'])): ?>
    <div class="alert alert-light border text-center py-5">
        Giỏ hàng đang trống. <a href="<?= e(base_url('')) ?>">Tiếp tục mua sắm</a>.
    </div>
<?php else: ?>
    <div class="row g-4">
        <!-- Cot trai: bang san pham (theo tai lieu Buoi 6) -->
        <div class="col-lg-8">
            <div class="table-responsive">
                <table class="table align-middle bg-white">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th style="width: 160px;">Số lượng</th>
                            <th>Thành tiền</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart['lines'] as $line): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= e(product_image_url($line['image'], 'thumb')) ?>"
                                            alt="<?= e($line['name']) ?>"
                                            style="width: 64px; height: 64px; object-fit: cover;"
                                            class="rounded border">
                                        <a href="<?= e(base_url('product/detail/' . $line['product_id'])) ?>"
                                            class="text-decoration-none text-dark">
                                            <?= e($line['name']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td><?= format_vnd($line['price']) ?></td>
                                <td>
                                    <form method="POST" action="<?= e(base_url('cart/update')) ?>" class="d-flex gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $line['product_id'] ?>">
                                        <input type="number" name="quantity" value="<?= (int) $line['quantity'] ?>"
                                            min="1" max="<?= (int) $line['stock'] ?>"
                                            class="form-control form-control-sm" style="width: 70px;">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Cập nhật</button>
                                    </form>
                                </td>
                                <td class="text-danger fw-bold"><?= format_vnd($line['line_total']) ?></td>
                                <td>
                                    <form method="POST" action="<?= e(base_url('cart/remove')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $line['product_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <form method="POST" action="<?= e(base_url('cart/clear')) ?>" class="mt-3"
                onsubmit="return confirm('Xóa toàn bộ giỏ hàng?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">Xóa toàn bộ giỏ hàng</button>
            </form>
        </div>

        <!-- Cot phai: ma giam gia, van chuyen, hoa don. Moi so tien da duoc CartService tinh san -->
        <div class="col-lg-4">
            <div class="mb-4">
                <h2 class="h6 mb-2">Mã giảm giá</h2>
                <?php if ($cart['coupon'] !== null): ?>
                    <form method="POST" action="<?= e(base_url('cart/removeCoupon')) ?>"
                        class="d-flex align-items-center justify-content-between gap-2">
                        <?= csrf_field() ?>
                        <span>Đang áp dụng: <strong><?= e($cart['coupon']) ?></strong></span>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Gỡ mã</button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="<?= e(base_url('cart/applyCoupon')) ?>" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <input type="text" name="coupon_code" class="form-control form-control-sm" maxlength="30"
                            placeholder="Nhập mã" autocomplete="off" aria-label="Mã giảm giá">
                        <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">Áp dụng</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <h2 class="h6 mb-2">Phương thức vận chuyển</h2>
                <form method="POST" action="<?= e(base_url('cart/shipping')) ?>">
                    <?= csrf_field() ?>
                    <?php foreach ($cart['shipping_options'] as $key => $opt): ?>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="shipping_method"
                                id="ship_<?= e($key) ?>" value="<?= e($key) ?>"
                                <?= $cart['shipping']['method'] === $key ? 'checked' : '' ?>
                                onchange="this.form.submit()">
                            <label class="form-check-label" for="ship_<?= e($key) ?>">
                                <?= e($opt['label']) ?> (<?= format_vnd($opt['base_fee']) ?>)
                            </label>
                            <?php if ($opt['free_over'] !== null): ?>
                                <div class="small text-muted">Miễn phí cho đơn từ <?= format_vnd($opt['free_over']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <noscript><button type="submit" class="btn btn-sm btn-outline-secondary mt-2">Cập nhật vận chuyển</button></noscript>
                </form>
            </div>

            <hr>
            <dl class="row mb-0">
                <dt class="col-7 fw-normal">Tiền hàng</dt>
                <dd class="col-5 text-end"><?= format_vnd($cart['subtotal']) ?></dd>

                <dt class="col-7 fw-normal">Giảm giá</dt>
                <dd class="col-5 text-end">-<?= format_vnd($cart['discount']) ?></dd>

                <dt class="col-7 fw-normal">Phí vận chuyển</dt>
                <dd class="col-5 text-end">
                    <?= $cart['shipping']['free'] ? 'Miễn phí' : format_vnd($cart['shipping']['fee']) ?>
                </dd>
            </dl>
            <hr>
            <div class="d-flex justify-content-between align-items-baseline mb-3">
                <span class="h5 mb-0">Tổng cộng</span>
                <span class="h4 mb-0 text-danger fw-bold"><?= format_vnd($cart['total']) ?></span>
            </div>
            <div class="d-grid">
                <button class="btn btn-primary" disabled title="Sẽ hoàn thiện ở Buổi 7-8">Tiến hành thanh toán</button>
            </div>
        </div>
    </div>
<?php endif; ?>
