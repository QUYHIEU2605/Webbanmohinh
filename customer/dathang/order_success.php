<?php
// Giả sử session đã được bắt đầu ở đâu đó trước đó hoặc trong menu_customer.php
// session_start(); // Bỏ comment nếu session chưa được start

include '../../customer/menu/menu_customer.php'; // Đảm bảo đường dẫn này đúng
// Bạn có thể muốn thêm logic kiểm tra đăng nhập ở đây nếu cần
// if (!isset($_SESSION['manguoidung'])) {
//     header("Location: ../user/login_customer.php"); // Ví dụ chuyển hướng nếu chưa đăng nhập
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Hàng Thành Công</title>
    <!-- Link tới file CSS -->
    <link rel="stylesheet" href="order_success.css?v=<?php echo time(); ?>">
    <!-- Link tới Font Awesome để sử dụng icon (ví dụ) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i> <!-- Icon dấu tích -->
        </div>
        <h1>Đặt hàng thành công!</h1>
        <p class="thank-you-message">Cảm ơn bạn đã tin tưởng và mua hàng tại cửa hàng của chúng tôi.</p>
        <p>Đơn hàng của bạn đã được ghi nhận và đang chờ xử lý. Chúng tôi sẽ thông báo cho bạn khi đơn hàng được vận
            chuyển.</p>
        <p>Bạn có thể theo dõi trạng thái đơn hàng trong lịch sử mua hàng.</p>

        <div class="cta-buttons">
            <a href="../dathang/donhang.php" class="btn btn-secondary">Xem Lịch Sử Đơn Hàng</a>
            <a href="../../index.php" class="btn btn-primary">Tiếp Tục Mua Sắm</a>
            <!-- Thay đổi các đường dẫn href cho phù hợp với cấu trúc trang web của bạn -->
        </div>
    </div>
</body>

</html>
<?php
include '../../footer.php';?>