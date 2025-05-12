<?php
include '../../db_connect.php';
include '../menu/menu_customer.php';

// Lấy từ khóa tìm kiếm từ URL
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Lấy bộ lọc và sắp xếp từ URL
$allBrandsResult = $conn->query("SELECT masx, tenhang FROM nhasanxuat");
$allBrands = [];
while ($row = $allBrandsResult->fetch_assoc()) {
    $allBrands[$row['masx']] = $row['tenhang'];
}

// Lấy loại sản phẩm từ URL (chỉ dùng để hiển thị tiêu đề, không dùng để lọc)
$loai = isset($_GET['loai']) ? $_GET['loai'] : '';


$categories = $conn->query("SELECT DISTINCT loaisanpham FROM sanpham");
$status = $conn->query("SELECT DISTINCT tinhtrang FROM sanpham");

// Xử lý tìm kiếm, bộ lọc và sắp xếp
$whereClauses = [];
$searchClauses = [];

// Ưu tiên bộ lọc từ form, bỏ qua 'loai' nếu có bộ lọc khác
$usingFilters = false; // Biến để kiểm tra xem có đang sử dụng bộ lọc không

if (!empty($_GET['brand'])) {
    $whereClauses[] = "sp.masx = '" . $conn->real_escape_string($_GET['brand']) . "'";
    $usingFilters = true;
}
if (!empty($_GET['category'])) {
    $whereClauses[] = "sp.loaisanpham = '" . $conn->real_escape_string($_GET['category']) . "'";
      $usingFilters = true;
}
if (!empty($_GET['status'])) {
    $whereClauses[] = "sp.tinhtrang = '" . $conn->real_escape_string($_GET['status']) . "'";
      $usingFilters = true;
}
if (!empty($_GET['price_from']) && !empty($_GET['price_to'])) {
    $priceFrom = (int)$_GET['price_from'];
    $priceTo = (int)$_GET['price_to'];
    $whereClauses[] = "sp.giaban BETWEEN $priceFrom AND $priceTo";
      $usingFilters = true;
}

// Nếu không sử dụng bộ lọc, và có 'loai' thì mới lọc theo loại
if (!$usingFilters && !empty($loai)) {
     $whereClauses[] = "sp.loaisanpham = '" . $conn->real_escape_string($loai) . "'";
}


if ($search !== '') {
    $searchParam = "%" . $conn->real_escape_string($search) . "%";
    $searchClauses[] = "sp.tensanpham LIKE '$searchParam'";
    $searchClauses[] = "nsx.tenhang LIKE '$searchParam'";
    $searchClauses[] = "sp.loaisanpham LIKE '$searchParam'";
    $searchClauses[] = "sp.tinhtrang LIKE '$searchParam'";

    if (count($searchClauses) > 0) {
        $whereClauses[] = "(" . implode(" OR ", $searchClauses) . ")";
    }
}

// Xử lý sắp xếp
$orderBy = "";
if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'name_asc':
            $orderBy = "ORDER BY sp.tensanpham ASC";
            break;
        case 'name_desc':
            $orderBy = "ORDER BY sp.tensanpham DESC";
            break;
        case 'price_asc':
            $orderBy = "ORDER BY sp.giaban ASC";
            break;
        case 'price_desc':
            $orderBy = "ORDER BY sp.giaban DESC";
            break;
        case 'date_asc':
            $orderBy = "ORDER BY sp.masanpham ASC";
            break;
        case 'date_desc':
            $orderBy = "ORDER BY sp.masanpham DESC";
            break;
        default:
            $orderBy = "ORDER BY sp.masanpham DESC";
    }
}
// Kết hợp các điều kiện
$whereQuery = '';
if (count($whereClauses) > 0) {
    $whereQuery = "WHERE " . implode(' AND ', $whereClauses);
}
// Truy vấn sản phẩm, join với bảng nhà sản xuất
$sql = "SELECT sp.*, nsx.tenhang 
        FROM sanpham sp
        LEFT JOIN nhasanxuat nsx ON sp.masx = nsx.masx 
        $whereQuery
        $orderBy";
$result = $conn->query($sql);

$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
$itemsPerPage = 9;

// Lấy trang hiện tại từ URL, mặc định là trang 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Đảm bảo trang không nhỏ hơn 1

// Tính toán offset
$offset = ($page - 1) * $itemsPerPage;

// Đếm tổng số sản phẩm (có áp dụng bộ lọc và tìm kiếm)
$totalItemsQuery = "SELECT COUNT(*) AS total 
                    FROM sanpham sp
                    LEFT JOIN nhasanxuat nsx ON sp.masx = nsx.masx 
                    $whereQuery";
$totalItemsResult = $conn->query($totalItemsQuery);
$totalItems = $totalItemsResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Truy vấn sản phẩm, join với bảng nhà sản xuất
$sql = "SELECT sp.*, nsx.tenhang 
        FROM sanpham sp
        LEFT JOIN nhasanxuat nsx ON sp.masx = nsx.masx 
        $whereQuery
        $orderBy
        LIMIT $itemsPerPage OFFSET $offset";
$result = $conn->query($sql);

$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="sanpham.css?v=<?php echo time(); ?>">
    <title>Danh sách sản phẩm</title>
</head>

<body>
    <div class='main-content-container'>
        <div class="sidebar">
            <h3>Tìm kiếm sản phẩm</h3>
            <form method="GET">
                <input type='hidden' name='search' value='<?= htmlspecialchars($search) ?>'>

                <!-- *Bỏ* input hidden 'loai' ở đây -->

                <!-- Hãng -->
                <label for="brand">Hãng</label>
                <select name="brand" id="brand">
                    <option value="">Tất cả</option>
                    <?php
                         foreach ($allBrands as $masx => $tenhang): ?>
                    <option value="<?= htmlspecialchars($masx); ?>"
                        <?= isset($_GET['brand']) && $_GET['brand'] === $masx ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($tenhang); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <!-- Phân loại -->
                <label for="category">Phân loại</label>
                <select name="category" id="category">
                    <option value="">Tất cả</option>
                    <?php
                         $categories = $conn->query("SELECT DISTINCT loaisanpham FROM sanpham");
                         while ($category = $categories->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($category['loaisanpham']); ?>"
                        <?= isset($_GET['category']) && $_GET['category'] === $category['loaisanpham'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($category['loaisanpham']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>

                <!-- Tình trạng-->
                <label for="status">Tình trạng</label>
                <select name="status" id="status">
                    <option value="">Tất cả</option>
                    <?php
                         $status = $conn->query("SELECT DISTINCT tinhtrang FROM sanpham");
                         while ($stat = $status->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($stat['tinhtrang']); ?>"
                        <?= isset($_GET['status']) && $_GET['status'] === $stat['tinhtrang'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($stat['tinhtrang']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>

                <!-- Giá -->
                <h3>Lọc Giá</h3>
                <div class="price-range-container">
                    <label for="price_range">Khoảng giá:</label>
                    <div class="slider-range">
                        <div id="slider-range"></div>
                    </div>
                    <div class="range-values">
                        <input type="number" name="price_from" id="price_from" readonly>
                        <span> - </span>
                        <input type="number" name="price_to" id="price_to" readonly>
                    </div>
                </div>
                <button type="submit">Tìm kiếm</button>
            </form>
            <h3>Sắp Xếp</h3>
            <form method="GET">

                <!-- *Bỏ* input hidden 'loai' ở đây -->
                <input type='hidden' name='search' value='<?= htmlspecialchars($search) ?>'>
                <select name="sort" onchange="this.form.submit()">
                    <option value="" <?= !isset($_GET['sort']) ? 'selected' : '' ?>>Mặc định</option>
                    <option value="name_asc"
                        <?= isset($_GET['sort']) && $_GET['sort'] === 'name_asc' ? 'selected' : '' ?>>Tên (A-Z)</option>
                    <option value="name_desc"
                        <?= isset($_GET['sort']) && $_GET['sort'] === 'name_desc' ? 'selected' : '' ?>>Tên (Z-A)
                    </option>
                    <option value="price_asc"
                        <?= isset($_GET['sort']) && $_GET['sort'] === 'price_asc' ? 'selected' : '' ?>>Giá (Thấp - Cao)
                    </option>
                    <option value="price_desc"
                        <?= isset($_GET['sort']) && $_GET['sort'] === 'price_desc' ? 'selected' : '' ?>>Giá (Cao - Thấp)
                    </option>
                    <option value="date_asc"
                        <?= isset($_GET['sort']) && $_GET['sort'] === 'date_asc' ? 'selected' : '' ?>>Cũ nhất</option>
                    <option value="date_desc"
                        <?= isset($_GET['sort']) && $_GET['sort'] === 'date_desc' ? 'selected' : '' ?>>Mới nhất</option>
                </select>
            </form>
        </div>
        <div class="product-container">

            <?php if (empty($products)): ?>
            <p>Không có sản phẩm nào phù hợp với tìm kiếm.</p>
            <?php else: ?>
            <?php foreach ($products as $row):
                       $originalPrice = $row['giaban'];
                      $discount = $row['giamgia'];
                      $discountedPrice = $originalPrice - $discount;
                    ?>
            <div class="product-card">
                <div class="carousel-container">
                    <div class="carousel-images">
                        <?php
                                    $images = explode(',', $row['anh']);
                                    foreach ($images as $index => $image):
                                        if (!empty($image)):
                                ?>
                        <img src="../../uploads/<?= htmlspecialchars($image); ?>" alt="Ảnh Sản Phẩm"
                            class="<?= $index === 0 ? 'active' : ''; ?>">
                        <?php
                                        endif;
                                    endforeach;
                                ?>
                    </div>

                </div>
                <div class="product-info">
                    <h3><a
                            href="chitiet_sanpham.php?id=<?= $row['masanpham']; ?>"><?= htmlspecialchars($row['tensanpham']); ?></a>
                    </h3>
                    <p>Hãng: <?= htmlspecialchars($row['tenhang']); ?></p>
                    <p>Phân Loại: <?= htmlspecialchars($row['loaisanpham']); ?></p>
                    <p>Tình Trạng: <?= htmlspecialchars($row['tinhtrang']); ?></p>
                    <p class="price">
                        <?php if ($discount > 0): ?>
                        <span
                            style="text-decoration: line-through;"><?= htmlspecialchars(number_format($originalPrice, 0, ',', '.')); ?>
                            VNĐ</span>
                        <span style="color: red;"><?= htmlspecialchars(number_format($discountedPrice, 0, ',', '.')); ?>
                            VNĐ</span>
                        <?php else: ?>
                        <?= htmlspecialchars(number_format($originalPrice, 0, ',', '.')); ?> VNĐ
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="prev-page">❮ Trang
            trước</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
            class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="next-page">Trang sau ❯</a>
        <?php endif; ?>
    </div>
</body>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
$(function() {
    var priceFrom = $("#price_from"),
        priceTo = $("#price_to"),
        sliderRange = $("#slider-range");
    var minDistance = 50000;
    var step = 10000;

    sliderRange.slider({
        range: true,
        min: 0, // Giá trị tối thiểu
        max: 1000000, // Giá trị tối đa
        step: step,
        values: [<?= $_GET['price_from'] ?? 0 ?>, <?= $_GET['price_to'] ?? 1000000 ?>],
        slide: function(event, ui) {
            if (ui.values[0] + minDistance >= ui.values[1]) {
                return false;
            }
            priceFrom.val(ui.values[0]);
            priceTo.val(ui.values[1]);
        },
        change: function(event, ui) {
            // Submit form khi giá trị slider thay đổi
            $(this).closest("form").submit();
        }
    });

    priceFrom.val(sliderRange.slider("values", 0));
    priceTo.val(sliderRange.slider("values", 1));
});
</script>

</html>
<?php
$conn->close();
?>
<?php
include '../../footer.php';?>