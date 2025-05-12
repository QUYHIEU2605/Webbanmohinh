<?php
include 'db_connect.php';
include 'customer/menu/menu_customer.php';


$sql = "SELECT * FROM trangchu WHERE id = 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    $product = null; // Gán giá trị null nếu không có dữ liệu
    echo "Không tìm thấy sản phẩm hoặc có lỗi xảy ra.";
}

// Lấy 4 loại sản phẩm phổ biến nhất
$sqlLoaiSanPham = "SELECT loaisanpham, COUNT(*) AS soluong 
                  FROM sanpham
                  WHERE soluong > 0
                  GROUP BY loaisanpham
                  ORDER BY soluong DESC
                  LIMIT 4";
$resultLoaiSanPham = $conn->query($sqlLoaiSanPham);

$loaiSanPhamPhoBien = [];
if ($resultLoaiSanPham && $resultLoaiSanPham->num_rows > 0) {
    while ($row = $resultLoaiSanPham->fetch_assoc()) {
      // Với mỗi loại, lấy 3 sản phẩm mới nhất
        $loai = $row['loaisanpham'];
        $sqlSanPham = "SELECT masanpham, tensanpham, anh, giaban, giamgia
                       FROM sanpham
                       WHERE loaisanpham = '$loai' AND soluong > 0
                       ORDER BY masanpham DESC
                       LIMIT 3";  // Sắp xếp theo mã sản phẩm (mới nhất)
        $resultSanPham = $conn->query($sqlSanPham);
         $sanPham = [];
         if ($resultSanPham && $resultSanPham->num_rows > 0) {
             while ($sanPhamRow = $resultSanPham->fetch_assoc()) {
                $sanPham[] = $sanPhamRow;
              }
         }

        $loaiSanPhamPhoBien[] = [
            'loaisanpham' => $loai,
             'soluong' => $row['soluong'],
             'sanpham' => $sanPham
        ];
    }
}

// Lấy 12 sản phẩm mới nhất VÀ CÓ SẴN
$sqlSanPhamMoi = "SELECT masanpham, tensanpham, anh, giaban, giamgia
                  FROM sanpham
                  WHERE soluong > 0 AND tinhtrang = 'Có sẵn'
                  ORDER BY masanpham DESC
                  LIMIT 5";
$resultSanPhamMoi = $conn->query($sqlSanPhamMoi);

$sanPhamMoi = [];
if ($resultSanPhamMoi && $resultSanPhamMoi->num_rows > 0) {
    while ($row = $resultSanPhamMoi->fetch_assoc()) {
        $sanPhamMoi[] = $row;
    }
}
//tình trạng order
$sqlSanPhamDat = "SELECT masanpham, tensanpham, anh, giaban, giamgia
                  FROM sanpham
                  WHERE soluong > 0 AND tinhtrang = 'Order'
                  ORDER BY masanpham DESC
                  LIMIT 5";
$resultSanPhamDat = $conn->query($sqlSanPhamDat);

$sanPhamDat = [];
if ($resultSanPhamDat && $resultSanPhamDat->num_rows > 0) {
    while ($row = $resultSanPhamDat->fetch_assoc()) {
        $sanPhamDat[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="index_product.css?v=<?php echo time(); ?>">
    <title>Trang Chủ</title>
</head>

<body>
    <div class="product-detail">
        <center>
            <div class="product-images">
                <?php if ($product) : ?>
                <?php 
            $images = explode(',', $product['duongdan']); // Tách chuỗi ảnh thành mảng
            foreach ($images as $index => $image):
                if (!empty($image)):
            ?>
                <img src="uploads/<?= htmlspecialchars($image); ?>" alt="Ảnh Sản Phẩm"
                    class="product-image <?= $index === 0 ? 'active' : ''; ?>">
                <?php
                endif;
            endforeach;
            ?>
                <?php endif; ?>
            </div>
            <?php if ($product && isset($_SESSION['vaitro']) && $_SESSION['vaitro'] === 'Admin') : ?>
            <a href="chinhsua/chinhsua.php?id=<?= htmlspecialchars($product['id']) ?>">Chỉnh sửa ảnh</a>
            <?php endif; ?>
        </center>
    </div>
    <h2>Các loại sản phẩm phổ biến</h2>
    <div class="loai-san-pham-container">
        <?php if (!empty($loaiSanPhamPhoBien)): ?>
        <?php foreach ($loaiSanPhamPhoBien as $loai): ?>
        <div class="loai-san-pham">
            <h3><a href="customer/sanpham/sanphamkhach.php?loai=<?= urlencode($loai['loaisanpham']); ?>"><?= htmlspecialchars($loai['loaisanpham']) ?>
                    (<?= $loai['soluong'] ?>)</a></h3>
            <div class="sanpham-list">
                <?php foreach ($loai['sanpham'] as $sp) :
                             $originalPrice = $sp['giaban'];
                             $discount = $sp['giamgia'];
                             $discountedPrice = $originalPrice - $discount;
                            ?>
                <div class="sanpham-item">
                    <a href="customer/sanpham/chitiet_sanpham.php?id=<?= $sp['masanpham'] ?>">
                        <img src="uploads/<?= htmlspecialchars(explode(',', $sp['anh'])[0]); ?>"
                            alt="<?= htmlspecialchars($sp['tensanpham']) ?>">
                    </a>
                    <p class='ten-sanpham'><a
                            href="customer/sanpham/chitiet_sanpham.php?id=<?= $sp['masanpham'] ?>"><?= htmlspecialchars($sp['tensanpham']) ?></a>
                    </p>
                    <p>
                        Giá:
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
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p>Không có loại sản phẩm nào.</p>
        <?php endif; ?>
    </div>
    <h2>Sản phẩm có sẵn</h2>
    <div class="sanpham-moi-container">
        <?php if (!empty($sanPhamMoi)): ?>
        <?php foreach ($sanPhamMoi as $sp):
                $originalPrice = $sp['giaban'];
                $discount = $sp['giamgia'];
                 $discountedPrice = $originalPrice - $discount;
                ?>
        <div class="sanpham-moi-item">
            <a href="customer/sanpham/chitiet_sanpham.php?id=<?= $sp['masanpham'] ?>">
                <img src="uploads/<?= htmlspecialchars(explode(',', $sp['anh'])[0]); ?>"
                    alt="<?= htmlspecialchars($sp['tensanpham']) ?>">
            </a>
            <p class='ten-sanpham'><a
                    href="customer/sanpham/chitiet_sanpham.php?id=<?= $sp['masanpham'] ?>"><?= htmlspecialchars($sp['tensanpham']) ?></a>
            </p>
            <p>
                Giá:
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
        <?php endforeach; ?>
        <?php else: ?>
        <p>Không có sản phẩm mới.</p>
        <?php endif; ?>
    </div>
    <h2>Sản phẩm đặt trước</h2>
    <div class="sanpham-moi-container">
        <?php if (!empty($sanPhamDat)): ?>
        <?php foreach ($sanPhamDat as $sp):
                $originalPrice = $sp['giaban'];
                $discount = $sp['giamgia'];
                 $discountedPrice = $originalPrice - $discount;
                ?>
        <div class="sanpham-moi-item">
            <a href="customer/sanpham/chitiet_sanpham.php?id=<?= $sp['masanpham'] ?>">
                <img src="uploads/<?= htmlspecialchars(explode(',', $sp['anh'])[0]); ?>"
                    alt="<?= htmlspecialchars($sp['tensanpham']) ?>">
            </a>
            <p class='ten-sanpham'><a
                    href="customer/sanpham/chitiet_sanpham.php?id=<?= $sp['masanpham'] ?>"><?= htmlspecialchars($sp['tensanpham']) ?></a>
            </p>
            <p>
                Giá:
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
        <?php endforeach; ?>
        <?php else: ?>
        <p>Không có sản phẩm mới.</p>
        <?php endif; ?>
    </div>
    <script>
    const images = document.querySelectorAll('.product-images .product-image');
    let currentImageIndex = 0;

    function showNextImage() {
        // Ẩn ảnh hiện tại
        images[currentImageIndex].classList.remove('active');

        // Tính toán index của ảnh tiếp theo
        currentImageIndex = (currentImageIndex + 1) % images.length;

        // Hiển thị ảnh tiếp theo
        images[currentImageIndex].classList.add('active');
    }

    // Thiết lập interval để tự động chuyển ảnh sau mỗi 3 giây (3000ms)
    setInterval(showNextImage, 3000);
    </script>
</body>

</html>
<?php
include 'footer.php';?>