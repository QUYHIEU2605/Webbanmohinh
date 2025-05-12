<?php
include '../../db_connect.php';
include '../../customer/menu/menu_customer.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');
// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['manguoidung'])) {
    header("Location: ../../customer/user/login_customer.php");
    exit();
}

$manguoidung = $_SESSION['manguoidung'];

// Lấy dữ liệu sản phẩm từ query string
$order_details = isset($_GET['order_details']) ? json_decode(urldecode($_GET['order_details']), true) : [];

if (empty($order_details)) {
    echo "Không có sản phẩm nào trong đơn hàng.";
    exit();
}

$maGiamGiaError = '';
$maGiamGiaThanhCong = '';
$giamGiaValue = 0;
$tenmagiamgia = '';
$magiamgia = null;
$totalPrice = 0;
$phiVanChuyen = 0;
$madonvi = isset($_POST['madonvi']) ? intval($_POST['madonvi']) : 0;
$outOfStockItems = [];
$availableItems = [];

// Kiểm tra số lượng tồn kho trước khi hiển thị trang xác nhận
foreach ($order_details as $item) {
    $masanpham = $item['id'];
    $soluongmua = $item['quantity'];

    // Truy vấn để lấy số lượng tồn kho thực tế của sản phẩm
    $sqlCheckStock = "SELECT soluong FROM sanpham WHERE masanpham = ?";
    $stmtCheckStock = $conn->prepare($sqlCheckStock);
    $stmtCheckStock->bind_param("i", $masanpham);
    $stmtCheckStock->execute();
    $resultCheckStock = $stmtCheckStock->get_result();

    if ($resultCheckStock->num_rows > 0) {
        $row = $resultCheckStock->fetch_assoc();
        $soluongtonkho = intval($row['soluong']);

        if ($soluongmua > $soluongtonkho) {
            // Sản phẩm này hết hàng hoặc không đủ số lượng
            $outOfStockItems[] = [
                'id' => $masanpham,
                'name' => $item['name'],
                'available' => $soluongtonkho
            ];
        } else {
            // Sản phẩm này còn hàng
            $availableItems[] = $item;
        }
    } else {
        // Sản phẩm không tồn tại trong database (lỗi)
        $outOfStockItems[] = [
            'id' => $masanpham,
            'name' => $item['name'],
            'available' => 0 // Hoặc một giá trị phù hợp
        ];
    }
}

// Nếu có sản phẩm hết hàng, thông báo cho người dùng và không cho phép xác nhận đơn hàng
if (!empty($outOfStockItems)) {
    echo "<div style='color: red;'>";
    echo "Có một số sản phẩm trong giỏ hàng của bạn đã hết hàng hoặc không đủ số lượng:<br><br>";
    echo "<ul>";
    foreach ($outOfStockItems as $item) {
        echo "<li>Sản phẩm: " . htmlspecialchars($item['name']) . " - Chỉ còn " . htmlspecialchars($item['available']) . " sản phẩm.</li>";
    }
    echo "</ul>";
    echo "<br>Vui lòng điều chỉnh giỏ hàng của bạn trước khi xác nhận đơn hàng.";
    echo "</div>";
    exit(); // Dừng việc hiển thị trang xác nhận đơn hàng
}

// Nếu tất cả sản phẩm đều còn hàng, tiếp tục xử lý đơn hàng
foreach ($availableItems as $item) {
    $originalPrice = floatval($item['price']);
    $discountedPrice = $originalPrice;
    if (isset($item['discount']) && floatval($item['discount']) > 0) {
        $discountedPrice = $originalPrice - floatval($item['discount']);
    }
    $totalPrice += $discountedPrice * $item['quantity'];
}

if (isset($_POST['apply_discount'])) {
    $tenmagiamgia = isset($_POST['magiamgia']) ? $_POST['magiamgia'] : '';
    if ($tenmagiamgia !== '') {
        $sqlCheckMaGiamGia = "SELECT * FROM giamgia WHERE tenma = ?";
        $stmt = $conn->prepare($sqlCheckMaGiamGia);
        $stmt->bind_param("s", $tenmagiamgia);
        $stmt->execute();
        $resultCheckMaGiamGia = $stmt->get_result();

        if ($resultCheckMaGiamGia->num_rows > 0) {
            $rowGiamGia = $resultCheckMaGiamGia->fetch_assoc();
            $magiamgia = $rowGiamGia['magiamgia'];
            if ($totalPrice < floatval($rowGiamGia['min_price'])) {
                $maGiamGiaError = "Mã giảm giá chỉ áp dụng cho đơn hàng có giá trị trên " . number_format($rowGiamGia['min_price'], 0, ',', '.') . " VNĐ";
            } else {
                if ($rowGiamGia['loaigiamgia'] == 'Công khai') {
                    if ($rowGiamGia['giamtheotien'] !== null && floatval($rowGiamGia['giamtheotien']) > 0) {
                        $giamGiaValue = floatval($rowGiamGia['giamtheotien']);
                    } else if ($rowGiamGia['giamtheophantram'] !== null && floatval($rowGiamGia['giamtheophantram']) > 0) {
                        $giamGiaValue = ($totalPrice * floatval($rowGiamGia['giamtheophantram'])) / 100;
                    }
                    $maGiamGiaThanhCong = "Mã giảm giá đã được áp dụng";
                } else if ($rowGiamGia['loaigiamgia'] == 'Riêng') {
                    $sqlCheckMaGiamGiaNguoiDung = "SELECT * FROM magiamgianguoidung WHERE magiamgia_id = ? AND manguoidung_id = ?";
                    $stmt = $conn->prepare($sqlCheckMaGiamGiaNguoiDung);
                    $stmt->bind_param("ii", $magiamgia, $_SESSION['manguoidung']);
                    $stmt->execute();
                    $resultCheckMaGiamGiaNguoiDung = $stmt->get_result();

                    if ($resultCheckMaGiamGiaNguoiDung->num_rows > 0) {
                        $rowGiamGiaNguoiDung = $resultCheckMaGiamGiaNguoiDung->fetch_assoc();
                        if ($rowGiamGia['giamtheotien'] !== null && floatval($rowGiamGia['giamtheotien']) > 0) {
                            $giamGiaValue = floatval($rowGiamGia['giamtheotien']);
                        } else if ($rowGiamGia['giamtheophantram'] !== null && floatval($rowGiamGia['giamtheophantram']) > 0) {
                            $giamGiaValue = ($totalPrice * floatval($rowGiamGia['giamtheophantram'])) / 100;
                        }
                        $maGiamGiaThanhCong = "Mã giảm giá đã được áp dụng";
                    } else {
                        $maGiamGiaError = "Bạn không có mã giảm giá này.";
                    }
                }
            }
        } else {
            $maGiamGiaError = "Mã giảm giá không hợp lệ.";
        }
    }
}

if ($madonvi > 0 && (isset($_POST['madonvi']) || isset($_POST['confirm_order']))) {
    $sqlCheckDonVi = "SELECT giavanchuyen FROM donvivanchuyen WHERE madonvi = ?";
    $stmt = $conn->prepare($sqlCheckDonVi);
    $stmt->bind_param("i", $madonvi);
    $stmt->execute();
    $resultCheckDonVi = $stmt->get_result();

    if ($resultCheckDonVi->num_rows > 0) {
        $rowDonVi = $resultCheckDonVi->fetch_assoc();
        $phiVanChuyen = floatval($rowDonVi['giavanchuyen']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order']) && empty($maGiamGiaError)) {
    $phuongthucthanhtoan = $_POST['phuongthucthanhtoan'];
    $tenmagiamgia = isset($_POST['magiamgia']) ? $_POST['magiamgia'] : '';
    $ngaydathang = date('Y-m-d H:i:s');
    $trangthai = "Chờ xác nhận";
    $magiamgia = null;

    if ($tenmagiamgia !== '') {
        $sqlCheckMaGiamGia = "SELECT magiamgia FROM giamgia WHERE tenma = ?";
        $stmt = $conn->prepare($sqlCheckMaGiamGia);
        $stmt->bind_param("s", $tenmagiamgia);
        $stmt->execute();
        $resultCheckMaGiamGia = $stmt->get_result();

        if ($resultCheckMaGiamGia->num_rows > 0) {
            $rowGiamGia = $resultCheckMaGiamGia->fetch_assoc();
            $magiamgia = $rowGiamGia['magiamgia'];
        }
    }

// Lấy tổng tiền từ form
// Lấy tổng tiền từ form
$tongtien = isset($_POST['tongtien']) ? floatval($_POST['tongtien']) : 0;

if ($tongtien <= 0) {
    die("Lỗi: Tổng tiền không hợp lệ.");
}


try {
    $conn->begin_transaction();
    
    // Thêm đơn hàng vào bảng donhang với tổng tiền cuối cùng
    $sqlDonHang = "INSERT INTO donhang (manguoidung, ngaydathang, tongtien, phuongthucthanhtoan, trangthai) 
                   VALUES (?, ?, ?, ?, ?)";
    $stmtDonHang = $conn->prepare($sqlDonHang);
    $stmtDonHang->bind_param("isdss", $_SESSION['manguoidung'], $ngaydathang, $tongtien, $phuongthucthanhtoan, $trangthai);
    
    if (!$stmtDonHang->execute()) {
        throw new Exception("Lỗi khi tạo đơn hàng: " . $stmtDonHang->error);
    }

    $madon = $conn->insert_id; // Lấy mã đơn hàng vừa tạo

    if (!$madon) {
        throw new Exception("Lỗi: Không thể lấy mã đơn hàng.");
    }

    // Thêm chi tiết đơn hàng vào bảng chitietdonhang
    $sqlChiTiet = "INSERT INTO chitietdonhang (madon, masanpham, soluong, manguoidung, magiamgia, madonvi) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmtChiTiet = $conn->prepare($sqlChiTiet);

    foreach ($availableItems as $item) {
        $masanpham = $item['id'];
        $soluong = $item['quantity'];

        if ($soluong <= 0) {
            throw new Exception("Lỗi: Số lượng sản phẩm không hợp lệ.");
        }

        $stmtChiTiet->bind_param("iiisii", $madon, $masanpham, $soluong, $_SESSION['manguoidung'], $magiamgia, $madonvi);
        if (!$stmtChiTiet->execute()) {
            throw new Exception("Lỗi khi chèn vào chitietdonhang: " . $stmtChiTiet->error);
        }

        // Cập nhật số lượng sản phẩm trong bảng sanpham
        $update_sql = "UPDATE sanpham SET soluong = soluong - ? WHERE masanpham = ?";
        $stmtUpdate = $conn->prepare($update_sql);
        $stmtUpdate->bind_param("ii", $soluong, $masanpham);
        if (!$stmtUpdate->execute()) {
            throw new Exception("Lỗi khi cập nhật số lượng sản phẩm: " . $stmtUpdate->error);
        }
    }

    // Xóa mã giảm giá nếu có
    if ($magiamgia !== null) {
        $sqlDeleteMaGiamGiaNguoiDung = "DELETE FROM magiamgianguoidung WHERE magiamgia_id = ? AND manguoidung_id = ?";
        $stmtDeleteMaGiamGiaNguoiDung = $conn->prepare($sqlDeleteMaGiamGiaNguoiDung);
        $stmtDeleteMaGiamGiaNguoiDung->bind_param("ii", $magiamgia, $_SESSION['manguoidung']);
        if (!$stmtDeleteMaGiamGiaNguoiDung->execute()) {
            throw new Exception("Lỗi khi xóa mã giảm giá người dùng: " . $stmtDeleteMaGiamGiaNguoiDung->error);
        }
    }

    // Xóa giỏ hàng sau khi đặt hàng
    $sqlClearCart = "DELETE FROM giohang WHERE manguoidung = ?";
    $stmtClearCart = $conn->prepare($sqlClearCart);
    $stmtClearCart->bind_param("i", $_SESSION['manguoidung']);
    if (!$stmtClearCart->execute()) {
        throw new Exception("Lỗi khi xóa giỏ hàng: " . $stmtClearCart->error);
    }

    // Commit transaction nếu mọi thứ thành công
    $conn->commit();
    echo "<script>window.location.href = 'order_success.php';</script>";
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Đã xảy ra lỗi: " . $e->getMessage();
}


}


// Truy vấn các đơn vị vận chuyển
$sqlDonViVanChuyen = "SELECT * FROM donvivanchuyen";
$resultDonViVanChuyen = $conn->query($sqlDonViVanChuyen);
$donViVanChuyen = [];
if ($resultDonViVanChuyen->num_rows > 0) {
    while ($row = $resultDonViVanChuyen->fetch_assoc()) {
        $donViVanChuyen[] = $row;
    }
}

$tenNganHang = "MB Bank";
$soTaiKhoan = "0867162706";
$tenNguoiNhan = "Nghiêm Chính Quang";
$maQR = "../../uploads/QR.jpg";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="order_confirm.css?v=<?php echo time(); ?>">
    <title>Xác Nhận Đơn Hàng</title>
</head>
<body>
<h1>Xác Nhận Đơn Hàng</h1>
<div class="order-confirm-container">
    <div class="order-detail">
        <h2>Thông Tin Đơn Hàng</h2>
        <div class="order-items">
            <?php
            foreach ($availableItems as $item):
                $originalPrice = floatval($item['price']);
                $discountedPrice = $originalPrice;
                if (isset($item['discount']) && floatval($item['discount']) > 0):
                    $discountedPrice = $originalPrice - floatval($item['discount']);
                endif;
                ?>
                <div class="order-item">
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="order-item-image"/>
                    <div class="order-item-info">
                        <p>Tên sản phẩm: <?= htmlspecialchars($item['name']); ?></p>
                        <p>
                            Giá:
                            <?php if (isset($item['discount']) && floatval($item['discount']) > 0): ?>
                                <span style="text-decoration: line-through;"><?= htmlspecialchars(number_format($originalPrice, 0, ',', '.')); ?> VNĐ</span>
                                <span style="color: red;"><?= htmlspecialchars(number_format($discountedPrice, 0, ',', '.')); ?> VNĐ</span>
                            <?php else: ?>
                                <?= htmlspecialchars(number_format($originalPrice, 0, ',', '.')); ?> VNĐ
                            <?php endif; ?>
                        </p>
                        <p>Số lượng: <?= htmlspecialchars($item['quantity']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="total-price">Tổng tiền:
    <span id="base-total-price"><?= number_format($totalPrice, 0, ',', '.') ?></span> VNĐ
</p>

<?php if ($giamGiaValue > 0): ?>
    <p class="total-price" style='color:red'>Giảm giá: 
        <?= htmlspecialchars(number_format($giamGiaValue, 0, ',', '.')); ?> VNĐ
    </p>
<?php endif; ?>

<p class="total-price" style='color:blue'>Phí vận chuyển: 
    <span id="shipping-fee"><?= htmlspecialchars(number_format($phiVanChuyen, 0, ',', '.')); ?></span> VNĐ
</p>

<p class="total-price" style='color:red; font-weight: bold;'>Tổng tiền cuối cùng: 
    <span id="final-total-price">
        <?= number_format($totalPrice - $giamGiaValue + $phiVanChuyen, 0, ',', '.') ?>
    </span> VNĐ
</p>
    </div>


<!-- JavaScript -->


        <div class="order-form">
    <h2>Thông Tin Thanh Toán</h2>
    <form method="post" id="order-form">
        <div>
            <label for='phuongthucthanhtoan'>Phương thức thanh toán:</label>
            <select name='phuongthucthanhtoan' id='phuongthucthanhtoan'>
                <option value='Thanh toán khi nhận hàng' 
                    <?= isset($_POST['phuongthucthanhtoan']) && $_POST['phuongthucthanhtoan'] == 'Thanh toán khi nhận hàng' ? 'selected' : '' ?>>
                    Thanh toán khi nhận hàng
                </option>
                <option value='Chuyển khoản ngân hàng' 
                    <?= isset($_POST['phuongthucthanhtoan']) && $_POST['phuongthucthanhtoan'] == 'Chuyển khoản ngân hàng' ? 'selected' : '' ?>>
                    Chuyển khoản ngân hàng
                </option>
            </select>

            <!-- Input ẩn để lưu phương thức thanh toán -->
            <input type="hidden" name="phuongthucthanhtoan_hidden" id="phuongthucthanhtoan_hidden" 
                value="<?= isset($_POST['phuongthucthanhtoan']) ? htmlspecialchars($_POST['phuongthucthanhtoan']) : 'Thanh toán khi nhận hàng' ?>">

            <div id='bank-info' style='display:none;'>
                <p>Tên ngân hàng: <?= htmlspecialchars($tenNganHang) ?></p>
                <p>Số tài khoản: <?= htmlspecialchars($soTaiKhoan) ?></p>
                <p>Tên người nhận: <?= htmlspecialchars($tenNguoiNhan) ?></p>
                <img src="<?= htmlspecialchars($maQR) ?>" alt="Mã QR" width='100px'>
            </div>
        </div>

        <div>
            <label for='madonvi'>Đơn vị vận chuyển:</label>
            <select name='madonvi' id='madonvi' required>
                <option value='0'>Chọn đơn vị vận chuyển</option>
                <?php foreach ($donViVanChuyen as $dv): ?>
                    <option value="<?= htmlspecialchars($dv['madonvi']) ?>" data-fee="<?= htmlspecialchars($dv['giavanchuyen']) ?>"
                        <?= $madonvi == $dv['madonvi'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dv['tendonvi']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <div class="coupon">
            <label for='magiamgia'>Mã giảm giá:</label>
            <input type='text' name='magiamgia' id='magiamgia' placeholder="Nhập mã giảm giá" value='<?= htmlspecialchars($tenmagiamgia) ?>'>
            <button type='submit' name='apply_discount'>Áp dụng</button>
            <?php if ($maGiamGiaError): ?>
                <p style="color:red"><?= $maGiamGiaError ?></p>
            <?php endif; ?>
            <?php if ($maGiamGiaThanhCong): ?>
                <p style="color:green"><?= $maGiamGiaThanhCong ?></p>
            <?php endif; ?>
            </div>
        </div>


        <button type="submit" name='confirm_order' id='confirm-order-button'>Xác nhận đặt hàng</button>
        <!-- Input ẩn để lưu tổng tiền vào form -->
<input type="hidden" id="tongtien" name="tongtien" value="<?= ($totalPrice - $giamGiaValue + $phiVanChuyen) ?>">

    </form>
</div>
    </div>
  </div>  
</body>
</html>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const madonviSelect = document.getElementById('madonvi');
        const confirmOrderButton = document.getElementById('confirm-order-button');
        const phuongthucthanhtoanSelect = document.getElementById('phuongthucthanhtoan');
        const bankInfoDiv = document.getElementById('bank-info');
        const totalPriceInput = document.querySelector('input[name="tongtien"]');
        const shippingFeeElement = document.getElementById("shipping-fee");
        const finalTotalElement = document.getElementById("final-total-price");

        // Tổng tiền gốc sau khi trừ giảm giá (từ PHP)
        let baseTotal = parseFloat(<?= max(0, $totalPrice - $giamGiaValue) ?>) || 0;

        function updateTotalPrice() {
            let selectedOption = madonviSelect.options[madonviSelect.selectedIndex];
            let shippingFee = parseFloat(selectedOption.getAttribute("data-fee")) || 0;
            let finalPrice = baseTotal + shippingFee;

            // Cập nhật phí vận chuyển hiển thị
            shippingFeeElement.innerText = shippingFee.toLocaleString("vi-VN");

            // Chỉ cập nhật tổng tiền cuối cùng, không thay đổi tổng tiền gốc
            finalTotalElement.innerText = finalPrice.toLocaleString("vi-VN") + " VNĐ";

            // Cập nhật giá trị vào input ẩn để gửi dữ liệu
            totalPriceInput.value = finalPrice;
        }

        // Khi thay đổi đơn vị vận chuyển, chỉ cập nhật tổng tiền cuối cùng
        madonviSelect.addEventListener("change", updateTotalPrice);

        // Kiểm tra trước khi submit
        confirmOrderButton.addEventListener('click', function (event) {
            if (madonviSelect.value == 0) {
                alert("Vui lòng chọn đơn vị vận chuyển");
                event.preventDefault();
                return;
            }

            const finalTotal = parseFloat(totalPriceInput.value) || 0;
            if (finalTotal <= 0) {
                alert("Lỗi: Tổng tiền không hợp lệ.");
                event.preventDefault();
                return;
            }
        });

        // Hiển thị thông tin ngân hàng nếu chọn "Chuyển khoản ngân hàng"
        phuongthucthanhtoanSelect.addEventListener('change', function () {
            bankInfoDiv.style.display = this.value === 'Chuyển khoản ngân hàng' ? 'block' : 'none';
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        function updateTotalPrice() {
            let totalPriceElement = document.getElementById("tongtien"); // Đúng ID trong HTML
            if (totalPriceElement) { 
                let baseTotal = parseInt(document.getElementById("base-total-price").innerText.replace(/\./g, ""));
                let discount = document.getElementById("magiamgia").value ? parseInt(document.getElementById("magiamgia").value) : 0;
                let shippingFee = parseInt(document.getElementById("shipping-fee").innerText.replace(/\./g, ""));

                let finalTotal = baseTotal - discount + shippingFee;
                totalPriceElement.value = finalTotal.toLocaleString("vi-VN") + " VNĐ";
            }
        }

        // Lắng nghe sự kiện khi chọn phương thức thanh toán hoặc mã giảm giá
        document.getElementById("phuongthucthanhtoan").addEventListener("change", updateTotalPrice);
        document.getElementById("magiamgia").addEventListener("input", updateTotalPrice);
        document.getElementById("madonvi").addEventListener("change", updateTotalPrice);

        // Chạy ngay khi trang load
        updateTotalPrice();
    });

    // Thêm đoạn cập nhật input ẩn từ tổng tiền hiển thị
    document.addEventListener("DOMContentLoaded", function () {
        // Lấy phần tử tổng tiền hiển thị và input ẩn
        let finalTotalPrice = document.getElementById("final-total-price");
        let hiddenInput = document.getElementById("tongtien");

        function syncHiddenTotal() {
            if (finalTotalPrice && hiddenInput) {
                // Chuyển đổi giá trị từ chuỗi số có dấu chấm sang số nguyên
                let totalValue = parseInt(finalTotalPrice.innerText.replace(/\./g, ""), 10);

                // Cập nhật giá trị vào input ẩn
                hiddenInput.value = totalValue;
            }
        }

        // Gọi hàm ngay khi trang load
        syncHiddenTotal();

        // Đảm bảo cập nhật mỗi khi tổng tiền hiển thị thay đổi
        const observer = new MutationObserver(syncHiddenTotal);
        observer.observe(finalTotalPrice, { childList: true, characterData: true, subtree: true });
    });
</script>


