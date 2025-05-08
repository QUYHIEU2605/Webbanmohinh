<?php
include '../../db_connect.php';
include '../../customer/menu/menu_customer.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$manguoidung = $_SESSION['manguoidung']; // Giả sử đã đăng nhập

// Số đơn hàng mỗi trang
$ordersPerPage = 3;

// Xác định trang hiện tại
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $ordersPerPage;

// Đếm tổng số đơn hàng của người dùng
$countQuery = "SELECT COUNT(*) AS total FROM donhang WHERE manguoidung = ?";
$stmt = $conn->prepare($countQuery);
$stmt->bind_param("i", $manguoidung);
$stmt->execute();
$countResult = $stmt->get_result();
$totalOrders = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalOrders / $ordersPerPage);
$stmt->close();

// Lấy danh sách đơn hàng của người dùng với phân trang
$sql = "SELECT d.madon, d.ngaydathang, d.tongtien, d.phuongthucthanhtoan, d.trangthai, 
               sp.tensanpham, sp.anh, sp.giaban, ctdh.soluong
        FROM donhang d
        JOIN chitietdonhang ctdh ON d.madon = ctdh.madon
        JOIN sanpham sp ON ctdh.masanpham = sp.masanpham
        WHERE d.manguoidung = ?
        ORDER BY d.ngaydathang DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $manguoidung, $ordersPerPage, $offset);
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
    
    // Bắt đầu transaction để đảm bảo tính toàn vẹn
    $conn->begin_transaction();

    try {
        // Xóa chi tiết đơn hàng trước
        $sqlDeleteChiTietDonHang = "DELETE FROM chitietdonhang WHERE madon = ?";
        $stmtDeleteChiTiet = $conn->prepare($sqlDeleteChiTietDonHang);
        $stmtDeleteChiTiet->bind_param("i", $madon);
        $stmtDeleteChiTiet->execute();
        $stmtDeleteChiTiet->close();

        // Sau đó xóa đơn hàng
        $sqlDeleteDonHang = "DELETE FROM donhang WHERE madon = ? AND manguoidung = ?";
        $stmtDeleteDonHang = $conn->prepare($sqlDeleteDonHang);
        $stmtDeleteDonHang->bind_param("ii", $madon, $manguoidung);
        $stmtDeleteDonHang->execute();
        $stmtDeleteDonHang->close();

        // Commit nếu mọi thứ thành công
        $conn->commit();
        echo "<script>alert('Đơn hàng đã được hủy thành công!'); window.location.href = 'donhang.php';</script>";
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
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
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f5f7fa;
        margin: 0;
        padding: 0px;
    }

    h2 {
        text-align: center;
        margin-bottom: 30px;
        color: #333;
    }

    .container {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        justify-content: center;
    }

    .order-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        padding: 20px;
        width: 360px;
        transition: transform 0.3s ease;
    }

    .order-card:hover {
        transform: translateY(-5px);
    }

    .order-card h3 {
        color: #007bff;
        margin-bottom: 10px;
        font-size: 20px;
    }

    .order-card p {
        font-size: 14px;
        margin: 4px 0;
        color: #555;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    th,
    td {
        border: 1px solid #e0e0e0;
        padding: 8px;
        text-align: center;
        font-size: 14px;
    }

    th {
        background-color: #f0f4f8;
        color: #333;
    }

    img {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 8px;
    }

    form {
        margin-top: 12px;
        display: flex;
        justify-content: center;
    }

    button[type="submit"] {
        background-color: #dc3545;
        color: white;
        padding: 8px 14px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }

    button[type="submit"]:hover {
        background-color: #c82333;
    }

    .pagination {
        text-align: center;
        margin-top: 30px;
    }

    .pagination a {
        padding: 10px 18px;
        margin: 0 5px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }

    .pagination a:hover {
        background-color: #0056b3;
    }
    </style>

</head>

<body>
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
                    <button type="submit" name="cancel_order"
                        onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">Hủy đơn hàng</button>
                </form>

            </table>
        </div>
        <?php } ?>
    </div>

    <div class="pagination">
        <?php if ($page > 1) { ?>
        <a href="?page=<?php echo $page - 1; ?>">&laquo; Trang trước</a>
        <?php } ?>
        <?php if ($page < $totalPages) { ?>
        <a href="?page=<?php echo $page + 1; ?>">Trang sau &raquo;</a>
        <?php } ?>
    </div>
</body>

</html>