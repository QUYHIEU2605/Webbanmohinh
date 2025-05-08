<?php
include '../../db_connect.php';
include '../../admin/menu/menu.php';

// Kiểm tra xem người dùng đã đăng nhập chưa và có quyền admin không
if (!isset($_SESSION['manguoidung']) || $_SESSION['vaitro'] != 'Admin') {
    header("Location: ../../admin/index.php");
    exit();
}

$reportType = isset($_GET['type']) ? $_GET['type'] : 'daily'; // Mặc định báo cáo theo ngày
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Tính tổng vốn
$sqlTotalCost = "SELECT SUM(sp.gianhap * ctdh.soluong) AS tongvon
                 FROM chitietdonhang ctdh
                 INNER JOIN sanpham sp ON ctdh.masanpham = sp.masanpham
                 INNER JOIN donhang dh ON ctdh.madon = dh.madon
                 WHERE DATE(dh.ngaydathang) BETWEEN '$startDate' AND '$endDate'";
$resultTotalCost = $conn->query($sqlTotalCost);
$rowTotalCost = $resultTotalCost->fetch_assoc();
$totalCost = $rowTotalCost['tongvon'] ?? 0; // Nếu không có dữ liệu, mặc định 0

$revenueData = [];
$totalRevenue = 0;

switch ($reportType) {
    case 'daily':
        $sql = "SELECT DATE(dh.ngaydathang) AS ngay, SUM(dh.tongtien) AS doanhthu
                FROM donhang dh
                WHERE DATE(dh.ngaydathang) BETWEEN '$startDate' AND '$endDate'
                GROUP BY ngay
                ORDER BY ngay";
        break;

    case 'monthly':
        $sql = "SELECT DATE_FORMAT(ngaydathang, '%Y-%m') AS thang, SUM(tongtien) AS doanhthu
                FROM donhang
                WHERE ngaydathang BETWEEN '$startDate' AND '$endDate'
                GROUP BY thang
                ORDER BY thang";
        break;

    case 'yearly':
        $sql = "SELECT DATE_FORMAT(ngaydathang, '%Y') AS nam, SUM(tongtien) AS doanhthu
                FROM donhang
                WHERE ngaydathang BETWEEN '$startDate' AND '$endDate'
                GROUP BY nam
                ORDER BY nam";
        break;
}

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $revenueData[] = $row;
        $totalRevenue += $row['doanhthu'];
    }
}

// Lấy danh sách sản phẩm bán chạy nhất
$sqlTopProducts = "SELECT sp.tensanpham, SUM(ctdh.soluong) AS tongsoluong
                   FROM chitietdonhang ctdh
                   INNER JOIN sanpham sp ON ctdh.masanpham = sp.masanpham
                   INNER JOIN donhang dh ON ctdh.madon = dh.madon
                   WHERE DATE(dh.ngaydathang) BETWEEN '$startDate' AND '$endDate'
                   GROUP BY sp.masanpham
                   ORDER BY tongsoluong DESC
                   LIMIT 10";
$resultTopProducts = $conn->query($sqlTopProducts);
$topProducts = [];
if ($resultTopProducts->num_rows > 0) {
    while ($row = $resultTopProducts->fetch_assoc()) {
        $topProducts[] = $row;
    }
}

// Lấy danh sách hãng sản xuất bán chạy nhất
$sqlTopBrands = "SELECT nsx.tenhang, SUM(ctdh.soluong) AS tongsoluong
                 FROM chitietdonhang ctdh
                 INNER JOIN sanpham sp ON ctdh.masanpham = sp.masanpham
                 INNER JOIN nhasanxuat nsx ON sp.masx = nsx.masx
                 INNER JOIN donhang dh ON ctdh.madon = dh.madon
                 WHERE DATE(dh.ngaydathang) BETWEEN '$startDate' AND '$endDate'
                 GROUP BY nsx.masx
                 ORDER BY tongsoluong DESC
                 LIMIT 10";
$resultTopBrands = $conn->query($sqlTopBrands);
$topBrands = [];
if ($resultTopBrands->num_rows > 0) {
    while ($row = $resultTopBrands->fetch_assoc()) {
        $topBrands[] = $row;
    }
}

function formatCurrency($number) {
    return number_format($number ?? 0, 0, ',', '.') . " VNĐ";
}

// Tính lợi nhuận
$profit = $totalRevenue - $totalCost;
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Ensure the path to thongke.css is correct -->
    <link rel="stylesheet" href="thongke.css?v=<?php echo time(); ?>">
    <title>Báo Cáo Doanh Thu</title>
</head>

<body>
    <div class="report-container">
        <h1>Báo Cáo Doanh Thu</h1>

        <div class="report-options">
            <form method="get">
                <label for="report_type">Loại Báo Cáo:</label>
                <select name="type" id="report_type">
                    <option value="daily" <?php if ($reportType == 'daily') echo 'selected'; ?>>Theo Ngày</option>
                    <option value="monthly" <?php if ($reportType == 'monthly') echo 'selected'; ?>>Theo Tháng</option>
                    <option value="yearly" <?php if ($reportType == 'yearly') echo 'selected'; ?>>Theo Năm</option>
                </select>
                <label for="start_date">Ngày bắt đầu:</label>
                <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($startDate) ?>">
                <label for="end_date">Ngày kết thúc:</label>
                <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($endDate) ?>">
                <button type="submit">Xem Báo Cáo</button>
            </form>
        </div>

        <div class="report-container">
            <?php if (!empty($revenueData)) : ?>
            <?php if($reportType === 'daily') : ?>
            <h2>Doanh Thu Theo Ngày (<?= htmlspecialchars($startDate) ?> - <?= htmlspecialchars($endDate) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Doanh Thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        // Reset total revenue for display specific to this table
                        $currentTotalRevenue = 0;
                        foreach ($revenueData as $row) :
                            $currentTotalRevenue += $row['doanhthu'];
                        ?>
                    <tr>
                        <td><?= htmlspecialchars($row['ngay']) ?></td>
                        <td><?= formatCurrency(htmlspecialchars($row['doanhthu'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Tổng doanh thu (kỳ báo cáo):</strong></td>
                        <td><strong><?= formatCurrency($currentTotalRevenue) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            <?php elseif ($reportType === 'monthly'): ?>
            <h2>Doanh Thu Theo Tháng (<?= htmlspecialchars($startDate) ?> - <?= htmlspecialchars($endDate) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tháng</th>
                        <th>Doanh Thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $currentTotalRevenue = 0;
                        foreach ($revenueData as $row) :
                            $currentTotalRevenue += $row['doanhthu'];
                        ?>
                    <tr>
                        <td><?= htmlspecialchars($row['thang']) ?></td>
                        <td><?= formatCurrency(htmlspecialchars($row['doanhthu'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Tổng doanh thu (kỳ báo cáo):</strong></td>
                        <td><strong><?= formatCurrency($currentTotalRevenue) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            <?php elseif($reportType === 'yearly'): ?>
            <h2>Doanh Thu Theo Năm (<?= htmlspecialchars($startDate) ?> - <?= htmlspecialchars($endDate) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Năm</th>
                        <th>Doanh Thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $currentTotalRevenue = 0;
                        foreach ($revenueData as $row) :
                            $currentTotalRevenue += $row['doanhthu'];
                        ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nam']) ?></td>
                        <td><?= formatCurrency(htmlspecialchars($row['doanhthu'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Tổng doanh thu (kỳ báo cáo):</strong></td>
                        <td><strong><?= formatCurrency($currentTotalRevenue) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            <?php endif; ?>
            <?php else : ?>
            <p>Không có dữ liệu doanh thu cho khoảng thời gian đã chọn.</p>
            <?php endif; ?>

            <h2>Top 10 Sản Phẩm Bán Chạy Nhất (<?= htmlspecialchars($startDate) ?> - <?= htmlspecialchars($endDate) ?>)
            </h2>
            <?php if (!empty($topProducts)) : ?>
            <ol class="top-products-list">
                <?php foreach ($topProducts as $product) : ?>
                <li>
                    <?= htmlspecialchars($product['tensanpham']) ?> (Số lượng:
                    <?= htmlspecialchars($product['tongsoluong']) ?>)
                </li>
                <?php endforeach; ?>
            </ol>
            <?php else: ?>
            <p>Không có sản phẩm bán chạy trong khoảng thời gian này.</p>
            <?php endif; ?>

            <h2>Top 10 Hãng Sản Xuất Bán Chạy Nhất (<?= htmlspecialchars($startDate) ?> -
                <?= htmlspecialchars($endDate) ?>)
            </h2>
            <?php if (!empty($topBrands)) : ?>
            <ol class="top-brands-list">
                <?php foreach ($topBrands as $brand) : ?>
                <li>
                    <?= htmlspecialchars($brand['tenhang']) ?> (Số lượng:
                    <?= htmlspecialchars($brand['tongsoluong']) ?>)
                </li>
                <?php endforeach; ?>
            </ol>
            <?php else: ?>
            <p>Không có hãng sản xuất bán chạy trong khoảng thời gian này.</p>
            <?php endif; ?>
        </div>

        <!-- Added ID here -->
        <div id="business-results">
            <h2>Báo Cáo Kết Quả Kinh Doanh (<?= htmlspecialchars($startDate) ?> - <?= htmlspecialchars($endDate) ?>)
            </h2>
            <table>
                <tbody>
                    <!-- Use tbody for consistency -->
                    <tr>
                        <td><strong>Tổng Doanh Thu:</strong></td>
                        <td><strong><?= formatCurrency($totalRevenue) ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Tổng Vốn:</strong></td>
                        <td><strong><?= formatCurrency($totalCost) ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Lợi Nhuận:</strong></td>
                        <td>
                            <!-- Added ID here -->
                            <strong id="profit-value" style="color: <?= ($profit >= 0) ? '#27ae60' : '#e74c3c' ?>;">
                                <?= formatCurrency($profit) ?>
                            </strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
<?php $conn->close(); // Close the connection at the very end ?>