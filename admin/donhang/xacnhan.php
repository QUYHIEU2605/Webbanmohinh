<?php
// --- Phần PHP giữ nguyên như bạn đã cung cấp ---
include '../../db_connect.php';
include '../../admin/menu/menu.php'; // Đảm bảo menu được include đúng

// Kiểm tra đăng nhập
if (!isset($_SESSION['manguoidung'])) {
    header("Location: ../../admin/index.php"); // Chuyển về trang đăng nhập admin nếu chưa login
    exit();
}

// Kiểm tra vai trò của người dùng
if ($_SESSION['vaitro'] == 'Khách hàng') {
    // Có thể chuyển hướng hoặc hiển thị thông báo lỗi thân thiện hơn
    // header("Location: ../../access_denied.php");
    echo "Bạn không có quyền truy cập trang này.";
    exit();
}

// Xoá hoặc xác nhận đơn hàng
if (isset($_POST['toggle_order'])) {
    $madon = $_POST['madon'];
    $trangthai = $_POST['trangthai'];
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;

    if ($trangthai === 'Yêu cầu hủy') {
        // Xóa đơn hàng và cập nhật lại số lượng sản phẩm
        $conn->begin_transaction();
        try {
            // Lấy chi tiết sản phẩm trong đơn hàng
            $sql_get_details = "SELECT masanpham, soluong FROM chitietdonhang WHERE madon = ?";
            $stmt_get_details = $conn->prepare($sql_get_details);
            $stmt_get_details->bind_param("i", $madon);
            $stmt_get_details->execute();
            $result_details = $stmt_get_details->get_result();

            // Cập nhật lại số lượng sản phẩm
            while ($row = $result_details->fetch_assoc()) {
                $sql_update_product = "UPDATE sanpham SET soluong = soluong + ? WHERE masanpham = ?";
                $stmt_update_product = $conn->prepare($sql_update_product);
                $stmt_update_product->bind_param("ii", $row['soluong'], $row['masanpham']);
                $stmt_update_product->execute();
                $stmt_update_product->close();
            }
            $stmt_get_details->close();

            // Xóa chi tiết đơn hàng
            $sql_delete_details = "DELETE FROM chitietdonhang WHERE madon = ?";
            $stmt_delete_details = $conn->prepare($sql_delete_details);
            $stmt_delete_details->bind_param("i", $madon);
            $stmt_delete_details->execute();
            $stmt_delete_details->close();

            // Xóa đơn hàng
            $sql_delete_order = "DELETE FROM donhang WHERE madon = ?";
            $stmt_delete_order = $conn->prepare($sql_delete_order);
            $stmt_delete_order->bind_param("i", $madon);
            $stmt_delete_order->execute();
            $stmt_delete_order->close();

            $conn->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $page . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''));
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo "Lỗi khi xử lý yêu cầu hủy: " . $e->getMessage();
        }
    } elseif ($trangthai === 'Chờ xác nhận') {
        // Xác nhận đơn hàng
        $sql_update = "UPDATE donhang SET trangthai = 'Đã xác nhận' WHERE madon = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $madon);
        $stmt_update->execute();
        $stmt_update->close();
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $page . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''));
        exit();
    }
} elseif (isset($_POST['delete_order'])) {
    $madon = $_POST['madon'];
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;

    $conn->begin_transaction();
    try {
        // Xóa chi tiết đơn hàng
        $sql_delete_details = "DELETE FROM chitietdonhang WHERE madon = ?";
        $stmt_delete_details = $conn->prepare($sql_delete_details);
        $stmt_delete_details->bind_param("i", $madon);
        $stmt_delete_details->execute();
        $stmt_delete_details->close();

        // Xóa đơn hàng
        $sql_delete_order = "DELETE FROM donhang WHERE madon = ?";
        $stmt_delete_order = $conn->prepare($sql_delete_order);
        $stmt_delete_order->bind_param("i", $madon);
        $stmt_delete_order->execute();
        $stmt_delete_order->close();

        $conn->commit();
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $page . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''));
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Lỗi khi xóa đơn hàng: " . $e->getMessage();
    }
}

// --- Phần lấy dữ liệu và phân trang giữ nguyên ---
// Cấu hình phân trang
$limit = 3; // Số đơn hàng trên mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // Đảm bảo page không nhỏ hơn 1
$start = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Hàm đếm tổng số đơn hàng (có thể tối ưu hơn khi có tìm kiếm)
function countOrders($conn, $search) {
    $sql = "SELECT COUNT(DISTINCT dh.madon) as total
            FROM donhang dh
            JOIN nguoidung nd ON dh.manguoidung = nd.manguoidung";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " WHERE dh.madon LIKE ? OR nd.tennguoidung LIKE ?";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) { die("Lỗi đếm đơn hàng: " . $conn->error); }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)$row['total'];
}

$totalOrders = countOrders($conn, $search);
$totalPages = ceil($totalOrders / $limit);
if ($page > $totalPages && $totalPages > 0) { // Chuyển về trang cuối nếu page quá lớn
    $page = $totalPages;
    $start = ($page - 1) * $limit;
}


// Hàm lấy đơn hàng (đã sửa lỗi và tối ưu)
function fetchOrders($conn, $start, $limit, $search) {
    $sql = "SELECT dh.madon, dh.ngaydathang, dh.phuongthucthanhtoan, dh.tongtien, dh.trangthai, dh.lydohuy,
                   nd.tennguoidung, nd.diachi, nd.sdt, dv.tendonvi AS donvivanchuyen
            FROM donhang dh
            JOIN nguoidung nd ON dh.manguoidung = nd.manguoidung
            LEFT JOIN (SELECT madon, MAX(madonvi) as madonvi FROM chitietdonhang GROUP BY madon) ctdh_agg ON dh.madon = ctdh_agg.madon
            LEFT JOIN donvivanchuyen dv ON ctdh_agg.madonvi = dv.madonvi";

    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " WHERE dh.madon LIKE ? OR nd.tennguoidung LIKE ?";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    $sql .= " ORDER BY dh.ngaydathang DESC LIMIT ?, ?";
    $params[] = $start;
    $params[] = $limit;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { die("Lỗi truy vấn đơn hàng: " . $conn->error); }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) { die("Lỗi thực thi truy vấn đơn hàng: " . $stmt->error); }

    $result = $stmt->get_result();
    $orders = [];
    if ($result->num_rows > 0) {
        $orders = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();

    if (!empty($orders)) {
        // Lấy chi tiết sản phẩm cho các đơn hàng đã lấy được
        $order_ids = array_column($orders, 'madon');
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        $types_detail = str_repeat('i', count($order_ids));

        $sql_details = "SELECT ctdh.madon, sp.tensanpham, sp.anh, sp.giaban, ctdh.soluong
                        FROM chitietdonhang ctdh
                        JOIN sanpham sp ON ctdh.masanpham = sp.masanpham
                        WHERE ctdh.madon IN ($placeholders)";

        $stmt_details = $conn->prepare($sql_details);
        if(!$stmt_details) { die("Lỗi truy vấn chi tiết sản phẩm: " . $conn->error); }
        $stmt_details->bind_param($types_detail, ...$order_ids);
        $stmt_details->execute();
        $result_details = $stmt_details->get_result();
        $products_by_order = [];
        while ($row = $result_details->fetch_assoc()) {
            $products_by_order[$row['madon']][] = $row;
        }
        $stmt_details->close();

        // Gán sản phẩm vào đúng đơn hàng
        foreach ($orders as &$order) {
            $order['sanpham'] = isset($products_by_order[$order['madon']]) ? $products_by_order[$order['madon']] : [];
        }
    }

    return $orders;
}

$orders = fetchOrders($conn, $start, $limit, $search);
$conn->close(); // Đóng kết nối sau khi lấy hết dữ liệu
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng</title>
    <!-- Link tới file CSS -->
    <link rel="stylesheet" href="xacnhan.css?v=<?php echo time(); ?>">
    <!-- Link tới Font Awesome nếu menu hoặc bạn muốn dùng icon -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->
</head>

<body>
    <div class="main-content">
        <!-- Bao bọc nội dung chính -->
        <h1>Quản Lý Đơn Hàng</h1>

        <div class="search-container">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Nhập mã đơn hoặc tên người đặt..."
                    value="<?= htmlspecialchars($search) ?>" class="search-input">
                <button type="submit" class="search-button">Tìm kiếm</button>
                <!-- Nút để xem tất cả -->
                <?php if (!empty($search)): ?>
                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="clear-search-button">Xem tất cả</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <!-- Thêm div để xử lý cuộn ngang -->
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Sản Phẩm</th>
                        <th>Ngày Đặt</th>
                        <th>Đơn Vị Vận Chuyển</th>
                        <th>PT Thanh Toán</th>
                        <th>Người Đặt</th>
                        <th>Địa Chỉ</th>
                        <th>SĐT</th>
                        <th>Tổng Tiền</th>
                        <th>Lý do hủy đơn</th>
                        <th>Trạng Thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="12" class="no-orders">Không tìm thấy đơn hàng nào.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td data-label="Mã Đơn"><?= htmlspecialchars($order['madon']) ?></td>
                        <td data-label="Sản Phẩm" class="product-details-cell">
    <?php if (empty($order['sanpham'])): ?>
        <p class="no-product">Không có sản phẩm</p>
    <?php else: ?>
        <?php foreach ($order['sanpham'] as $product): ?>
            <div class="product-item">
                <?php
                $images = explode(',', $product['anh'] ?? 'default.png');
                $firstImage = trim($images[0]);
                ?>
                <img src="../../uploads/<?= htmlspecialchars($firstImage) ?>"
                     alt="<?= htmlspecialchars($product['tensanpham']) ?>"
                     class="product-img" style="margin-right: 5px; width: 50px; height: 50px;">
                <p class="product-name"><?= htmlspecialchars($product['tensanpham']) ?> (SL:
                    <?= htmlspecialchars($product['soluong']) ?>)</p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</td>

                        <td data-label="Ngày Đặt"><?= date("d/m/Y H:i", strtotime($order['ngaydathang'])) ?></td>
                        <td data-label="ĐV Vận Chuyển"><?= htmlspecialchars($order['donvivanchuyen'] ?? 'N/A') ?></td>
                        <td data-label="PT Thanh Toán"><?= htmlspecialchars($order['phuongthucthanhtoan']) ?></td>
                        <td data-label="Người Đặt"><?= htmlspecialchars($order['tennguoidung']) ?></td>
                        <td data-label="Địa Chỉ"><?= htmlspecialchars($order['diachi']) ?></td>
                        <td data-label="SĐT"><?= htmlspecialchars($order['sdt']) ?></td>
                        <td data-label="Tổng Tiền" class="price"><?= number_format($order['tongtien'], 0, ',', '.') ?> VNĐ</td>
                        <td data-label="Lý do hủy đơn"><?= htmlspecialchars($order['lydohuy'] ?? 'Không có') ?></td>
                        <td data-label="Trạng Thái">
                            <span class="status status-<?= strtolower(str_replace(' ', '-', $order['trangthai'])) ?>">
                                <?= htmlspecialchars($order['trangthai']) ?>
                            </span>
                        </td>
                        <td data-label="Hành Động" class="action-cell">
                            <form method="post" class="action-form"
                                onsubmit="return confirm('<?= $order['trangthai'] == 'Chờ xác nhận' ? 'Bạn có chắc muốn XÁC NHẬN đơn hàng này không?' : ($order['trangthai'] == 'Yêu cầu hủy' ? 'Bạn có chắc muốn XÁC NHẬN HỦY đơn hàng này không?' : 'Bạn có chắc muốn XÓA đơn hàng này không?') ?>');">
                                <input type="hidden" name="madon" value="<?= $order['madon'] ?>">
                                <input type="hidden" name="trangthai" value="<?= $order['trangthai'] ?>">
                                <input type="hidden" name="page" value="<?= $page ?>">
                                <?php if ($order['trangthai'] == 'Chờ xác nhận') { ?>
                                    <button type="submit" name="toggle_order" class="action-button btn-confirm">Xác Nhận</button>
                                <?php } elseif ($order['trangthai'] == 'Yêu cầu hủy') { ?>
                                    <button type="submit" name="toggle_order" class="action-button btn-cancel">Xác Nhận Hủy</button>
                                <?php } ?>
                                <button type="submit" name="delete_order" class="action-button btn-delete">Xóa Đơn</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div> <!-- End .table-responsive -->

        <?php if ($totalPages > 1): ?>
        <div class="pagination-container">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
                class="page-link prev">« Trước</a>
            <?php endif; ?>

            <?php
                // Logic hiển thị số trang rút gọn (ví dụ: 1 ... 4 5 6 ... 10)
                $start_page = max(1, $page - 2);
                $end_page = min($totalPages, $page + 2);

                if ($start_page > 1) {
                    echo '<a href="?page=1'.(!empty($search) ? '&search='.urlencode($search) : '').'" class="page-link">1</a>';
                    if ($start_page > 2) {
                        echo '<span class="page-link ellipsis">...</span>';
                    }
                }

                for ($i = $start_page; $i <= $end_page; $i++): ?>
            <a href="?page=<?= $i ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
                class="page-link <?= $i == $page ? 'current-page' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor;

                if ($end_page < $totalPages) {
                     if ($end_page < $totalPages - 1) {
                        echo '<span class="page-link ellipsis">...</span>';
                    }
                    echo '<a href="?page='.$totalPages.(!empty($search) ? '&search='.urlencode($search) : '').'" class="page-link">'.$totalPages.'</a>';
                }
                ?>

            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
                class="page-link next">Sau »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div> <!-- End .main-content -->
</body>

</html>