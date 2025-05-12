<?php
include '../../db_connect.php';
include '../../customer/menu/menu_customer.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$manguoidung = $_SESSION['manguoidung']; // Giả sử đã đăng nhập

// Lấy danh sách tất cả đơn hàng của người dùng
$sql = "SELECT d.madon, d.ngaydathang, d.tongtien, d.phuongthucthanhtoan, d.trangthai, 
               sp.tensanpham, sp.anh, sp.giaban, ctdh.soluong
        FROM donhang d
        JOIN chitietdonhang ctdh ON d.madon = ctdh.madon
        JOIN sanpham sp ON ctdh.masanpham = sp.masanpham
        WHERE d.manguoidung = ?
        ORDER BY d.ngaydathang DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $manguoidung);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[$row['madon']]['ngaydathang'] = $row['ngaydathang'];
        $orders[$row['madon']]['tongtien'] = $row['tongtien'];
        $orders[$row['madon']]['phuongthucthanhtoan'] = $row['phuongthucthanhtoan'];
        $orders[$row['madon']]['trangthai'] = $row['trangthai'];
        
        $orders[$row['madon']]['sanpham'][] = [
            'tensanpham' => $row['tensanpham'],
            'anh' => '../../uploads/' . $row['anh'],
            'giaban' => $row['giaban'],
            'soluong' => $row['soluong'],
        ];
    }
}
if (isset($_POST['cancel_order'])) {
    $madon = $_POST['madon'];
    $lydohuy = $_POST['lydohuy'];

    try {
        // Cập nhật trạng thái đơn hàng thành "Yêu cầu hủy" và lưu lý do hủy
        $sqlUpdateTrangThai = "UPDATE donhang SET trangthai = 'Yêu cầu hủy', lydohuy = ? WHERE madon = ? AND manguoidung = ?";
        $stmtUpdateTrangThai = $conn->prepare($sqlUpdateTrangThai);
        $stmtUpdateTrangThai->bind_param("sii", $lydohuy, $madon, $manguoidung);
        $stmtUpdateTrangThai->execute();
        $stmtUpdateTrangThai->close();

        echo "<script>alert('Yêu cầu hủy đơn hàng đã được gửi!'); window.location.href = 'donhang.php';</script>";
    } catch (Exception $e) {
        echo "Có lỗi xảy ra: " . $e->getMessage();
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách đơn hàng</title>
    <link rel="stylesheet" href="donhang.css?v=<?php echo time(); ?>">
    <script>
        function showCancelPopup(madon) {
            const popup = document.getElementById("cancel-popup");
            const madonInput = document.getElementById("popup-madon");
            madonInput.value = madon;
            popup.style.display = "block";
        }

        function closeCancelPopup() {
            const popup = document.getElementById("cancel-popup");
            popup.style.display = "none";
        }
    </script>
    <style>
        #cancel-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        #cancel-popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .popup-close {
            cursor: pointer;
            color: red;
            float: right;
            font-size: 20px;
        }
    </style>
</head>

<body>
    <div id="cancel-popup-overlay" onclick="closeCancelPopup()"></div>
    <div id="cancel-popup">
        <span class="popup-close" onclick="closeCancelPopup()">×</span>
        <h3>Nhập lý do hủy đơn hàng</h3>
        <form action="" method="POST">
            <input type="hidden" name="madon" id="popup-madon">
            <textarea name="lydohuy" required placeholder="Nhập lý do hủy..." style="width: 100%; height: 100px;"></textarea>
            <br>
            <button type="submit" name="cancel_order">Gửi yêu cầu</button>
        </form>
    </div>

    <h2 style="text-align: center;">Danh sách đơn hàng</h2>
    <div class="container">
        <?php foreach ($orders as $madon => $order) { ?>
        <div class="order-card">
            <h3>Đơn hàng #<?php echo $madon; ?></h3>
            <p><strong>Ngày đặt hàng:</strong> <?php echo $order['ngaydathang']; ?></p>
            <p><strong>Tổng tiền:</strong> <?php echo number_format($order['tongtien'], 0, ',', '.'); ?> đ</p>
            <p><strong>Phương thức thanh toán:</strong> <?php echo $order['phuongthucthanhtoan']; ?></p>
            <p><strong>Trạng thái:</strong> <?php echo $order['trangthai']; ?></p>
            <table>
                <tr>
                    <th>Ảnh</th>
                    <th>Sản phẩm</th>
                    <th>SL</th>
                    <th>Giá</th>
                </tr>
                <?php foreach ($order['sanpham'] as $sanpham) { ?>
                <tr>
                    <td>
                        <?php 
                        $images = explode(',', $sanpham['anh']); // Tách chuỗi ảnh
                        $firstImage = trim($images[0]); // Lấy ảnh đầu tiên và loại bỏ khoảng trắng
                        ?>
                        <img src="<?php echo $firstImage; ?>" alt="Ảnh sản phẩm">
                    </td>
                    <td><?php echo $sanpham['tensanpham']; ?></td>
                    <td><?php echo $sanpham['soluong']; ?></td>
                    <td><?php echo number_format($sanpham['giaban'], 0, ',', '.'); ?> đ</td>
                </tr>
                <?php } ?>
                <form action="" method="POST">
                    <input type="hidden" name="madon" value="<?php echo $madon; ?>">
                    <?php if ($order['trangthai'] === 'Yêu cầu hủy') { ?>
                        <button type="button" disabled>Chờ xác nhận</button>
                    <?php } else { ?>
                        <button type="button" onclick="showCancelPopup(<?php echo $madon; ?>)">Hủy đơn hàng</button>
                    <?php } ?>
                </form>
            </table>
        </div>
        <?php } ?>
    </div>
</body>

</html>