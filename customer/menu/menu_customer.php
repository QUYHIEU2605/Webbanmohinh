<?php
session_start();
$tong_soluong = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'soluong')) : 0;
$isLoggedIn = isset($_SESSION['manguoidung']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../customer/menu/menu.css?v=<?php echo time(); ?>">

<nav class="customer-menu">
    <div class="menu-container">

        <!-- Menu trái -->
        <div class="menu-left">
            <a href="../../index.php">Trang chủ</a>
            <a href="../../customer/sanpham/sanphamkhach.php">Sản phẩm</a>
            <?php if ($isLoggedIn): ?>
            <a href="../../customer/dathang/donhang.php">Đơn hàng</a>
            <?php endif; ?>

        </div>

        <!-- Thanh tìm kiếm -->
        <div class="menu-search">
            <form action="../../customer/sanpham/sanphamkhach.php" method="GET">
                <input type="text" name="search" placeholder="Tìm kiếm sản phẩm...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <!-- Icon user + cart -->
        <div class="menu-icons">
            <!-- Hồ sơ -->
            <div class="dropdown">
                <i class="fas fa-user-circle"></i>
                <div class="dropdown-content">
                    <?php if ($isLoggedIn): ?>
                    <a href="../../customer/user/profile.php">Hồ sơ</a>
                    <a href="../../customer/user/logout.php">Đăng xuất</a>
                    <?php else: ?>
                    <a href="../../customer/user/login_customer.php">Đăng nhập</a>
                    <a href="../../customer/user/register.php">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Giỏ hàng -->
            <a href="../../customer/dathang/cart.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($tong_soluong > 0): ?>
                <span class="cart-count"><?php echo $tong_soluong; ?></span>
                <?php endif; ?>
            </a>
        </div>

    </div>
</nav>