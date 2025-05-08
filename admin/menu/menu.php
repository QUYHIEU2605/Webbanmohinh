<?php
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
include '../../db_connect.php';


// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['manguoidung'])) {
    header("Location: ../../admin/index.php"); // Chuyển hướng đến trang đăng nhập nếu chưa đăng nhập
    exit();
}

// Kiểm tra vai trò của người dùng
if ($_SESSION['vaitro'] == 'Khách hàng') {
    echo "Bạn không có quyền truy cập trang này.";
     exit();
    header("Location: ../../customer/index.php"); // Chuyển hướng nếu vai trò là khách hàng
    exit();
}
?>
<link rel="stylesheet" href="/mohinh/admin/menu/menu.css?v=<?php echo time(); ?>">
<nav class="navbar">
    <div class="container">
        <a href="index.php" class="logo">My Website</a>
        <ul class="menu">
            <li <?php if ($current_page == '../../admin/donhang/xacnhan.php') echo 'class="active"'; ?>><a href="../../admin/donhang/xacnhan.php">Đơn đặt hàng</a></li>
            <li <?php if ($current_page == '../../admin/qlysp/danhsachsp.php') echo 'class="active"'; ?>><a href="../../admin/qlysp/danhsachsp.php">Sản phẩm</a></li>
            <li <?php if ($current_page == '../../admin/nsx/nhasx.php') echo 'class="active"'; ?>><a href="../../admin/nsx/nhasx.php">Hãng</a></li>
            <li <?php if ($current_page == '../../admin/vanchuyen/dsvanchuyen.php') echo 'class="active"'; ?>><a href="../../admin/vanchuyen/dsvanchuyen.php">Đơn vị vận chuyển</a></li>
            <li <?php if ($current_page == '../../admin/magiamgia/qlymagiamgia.php') echo 'class="active"'; ?>><a href="../../admin/magiamgia/qlymagiamgia.php">Mã giảm giá</a></li>
            <li <?php if ($current_page == '../../admin/user/users.php') echo 'class="active"'; ?>><a href="../../admin/user/users.php">Quản lý người dùng</a></li>
            <li <?php if ($current_page == '../../chat/chatboss.php') echo 'class="active"'; ?>><a href="../../chat/chatboss.php">Chat</a></li>

            <li <?php if ($current_page == '../../admin/thongke/thongke.php') echo 'class="active"'; ?>><a href="../../admin/thongke/thongke.php">Thống kê</a></li>
            <li <?php if ($current_page == '../../admin/logout.php') echo 'class="active"'; ?>><a href="../../admin/logout.php">Đăng xuất</a></li>
        </ul>
    </div>
</nav>  