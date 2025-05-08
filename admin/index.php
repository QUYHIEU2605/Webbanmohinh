<?php
session_start(); // Bắt đầu session

include '../db_connect.php'; // Kết nối cơ sở dữ liệu

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sử dụng prepared statements để bảo mật hơn
    $tennguoidung = $_POST['tennguoidung'];
    $matkhau = $_POST['matkhau']; // Nên mã hóa mật khẩu trước khi lưu và so sánh

    // Chuẩn bị câu lệnh SQL
    $sql = "SELECT manguoidung, vaitro, matkhau FROM nguoidung WHERE tennguoidung = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $tennguoidung);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // **QUAN TRỌNG**: So sánh mật khẩu đã hash (nếu bạn lưu hash)
            // Ví dụ nếu dùng password_hash và password_verify:
            // if (password_verify($matkhau, $user['matkhau'])) {

            // Hoặc nếu lưu mật khẩu dạng text (KHÔNG KHUYẾN KHÍCH):
            if ($matkhau === $user['matkhau']) { // So sánh mật khẩu text
                if ($user['vaitro'] == 'Khách hàng') {
                    $error = "Khách hàng không thể đăng nhập vào trang này.";
                } else {
                    // Đăng nhập thành công
                    $_SESSION['manguoidung'] = $user['manguoidung'];
                    $_SESSION['vaitro'] = $user['vaitro'];
                    // Chuyển hướng đến trang quản lý phù hợp (ví dụ: dashboard hoặc trang đơn hàng)
                    // Nên có một trang dashboard chung thay vì nhảy thẳng vào xác nhận đơn hàng
                    // header("Location: ../admin/dashboard.php"); // Ví dụ
                    header("Location: donhang/xacnhan.php"); // Giữ nguyên nếu bạn muốn
                    exit();
                }
            } else {
                // Sai mật khẩu
                $error = "Thông tin đăng nhập không đúng.";
            }
        } else {
            // Sai tên người dùng
            $error = "Thông tin đăng nhập không đúng.";
        }
        $stmt->close();
    } else {
        $error = "Lỗi truy vấn cơ sở dữ liệu."; // Lỗi chuẩn bị câu lệnh
    }
}
$conn->close(); // Đóng kết nối sau khi dùng xong
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Quản Trị</title>
    <!-- Link tới file CSS -->
    <link rel="stylesheet" href="index.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="login-container">
        <h1>Đăng Nhập Hệ Thống</h1>

        <?php if ($error) : ?>
        <p class="error-message"><?= htmlspecialchars($error); // Luôn escape output ?></p>
        <?php endif; ?>

        <form method="post" action="index.php">
            <!-- Action nên trỏ về chính nó -->
            <div class="form-group">
                <label for="tennguoidung">Tên Người Dùng:</label>
                <input type="text" id="tennguoidung" name="tennguoidung" required
                    value="<?= isset($_POST['tennguoidung']) ? htmlspecialchars($_POST['tennguoidung']) : ''; // Giữ lại username nếu đăng nhập lỗi ?>">
            </div>
            <div class="form-group">
                <label for="matkhau">Mật Khẩu:</label>
                <input type="password" id="matkhau" name="matkhau" required>
            </div>
            <button type="submit" class="btn-login">Đăng Nhập</button>
        </form>
    </div>
</body>

</html>