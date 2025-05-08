<?php
include '../../db_connect.php';
include '../../customer/menu/menu_customer.php';

// Lấy id từ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    // Truy vấn thông tin sản phẩm theo id
    $sql = "SELECT * FROM sanpham WHERE masanpham = $id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        echo "Sản phẩm không tồn tại.";
        exit;
    }
} else {
    echo "Không có id sản phẩm.";
    exit();
}

// Tính giá sau giảm giá
$originalPrice = $product['giaban'];
$discount = $product['giamgia'];
$discountedPrice = $originalPrice - $discount;
$stock = $product['soluong'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="chitiet_sanpham.css?v=<?php echo time(); ?>">
    <title>Chi Tiết Sản Phẩm</title>
</head>

<body>
    <h1>Chi Tiết Sản Phẩm</h1>
    <div class="product-detail">
        <div class="product-images">
            <?php 
            $images = explode(',', $product['anh']); // Tách chuỗi ảnh thành mảng
            foreach ($images as $index => $image):
                if (!empty($image)):
            ?>
            <img src="../../uploads/<?= htmlspecialchars($image); ?>" alt="Ảnh Sản Phẩm" data-index="<?= $index ?>"
                class="product-image <?= $index === 0 ? 'active' : ''; ?>">
            <?php
                endif;
            endforeach;
            ?>
            <button class="carousel-button left" onclick="prevImage()">❮</button>
            <button class="carousel-button right" onclick="nextImage()">❯</button>
        </div>
        <div class="product-info">
            <h2><?= htmlspecialchars($product['tensanpham']); ?></h2>
            <p><strong>Hãng:</strong> <?= htmlspecialchars($product['masx']); ?></p>
            <p><strong>Phân Loại:</strong> <?= htmlspecialchars($product['loaisanpham']); ?></p>
            <p><strong>Tình Trạng:</strong> <?= htmlspecialchars($product['tinhtrang']); ?></p>
            <p><strong>Giá:</strong>
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

            <?php if ($stock > 0) :?>
            <p><strong>Số Lượng:</strong> <?= htmlspecialchars($product['soluong']); ?></p>
            <?php else :?>
            <p><strong>Số Lượng:</strong> <span style="color:red;">Hết Hàng</span></p>
            <?php endif ?>
            <p><strong>Mô Tả:</strong><br><?= nl2br(htmlspecialchars($product['mieuta'])); ?></p>
            <div class="action-buttons">
                <?php if ($stock > 0) : ?>
                <button class='btn-add-to-cart' data-id='<?= $id ?>'>Thêm vào giỏ hàng</button>
                <button class='btn-buy-now' data-id='<?= $id ?>'>Mua ngay</button>
                <?php else: ?>
                <button disabled>Hết hàng</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    const images = document.querySelectorAll('.product-images .product-image');
    let currentImageIndex = 0;

    function showImage(index) {
        images.forEach(img => img.classList.remove('active'));
        images[index].classList.add('active');
        currentImageIndex = index;
    }

    function nextImage() {
        currentImageIndex = (currentImageIndex + 1) % images.length;
        showImage(currentImageIndex);
    }

    function prevImage() {
        currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
        showImage(currentImageIndex);
    }
    document.addEventListener('DOMContentLoaded', function() {
        const addToCartButton = document.querySelector('.btn-add-to-cart');
        const buyNowButton = document.querySelector('.btn-buy-now');
        if (!addToCartButton) {
            return;
        }
        addToCartButton.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToCart(productId, false);
        });
        buyNowButton.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToCart(productId, true);
        });

        function addToCart(productId, buyNow) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../../customer/dathang/add_to_cart.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status == 200) {
                    if (buyNow) {
                        window.location.href = "../../customer/dathang/cart.php";
                    } else {
                        alert("Sản phẩm đã được thêm vào giỏ hàng.");
                    }

                } else {
                    alert("Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng.");
                }
            };
            xhr.send("masanpham=" + productId);
        }
    });
    </script>

</body>

</html>
<?php
include '../../footer.php';?>