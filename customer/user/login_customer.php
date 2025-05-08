<?php   
include '../../db_connect.php'; // Kết nối cơ sở dữ liệu
include '../../customer/menu/menu_customer.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tennguoidung = $_POST['tennguoidung'];
    $matkhau = $_POST['matkhau'];

    // Kiểm tra thông tin đăng nhập
    $sql = "SELECT manguoidung, vaitro FROM nguoidung WHERE tennguoidung = '$tennguoidung' AND matkhau = '$matkhau'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['manguoidung'] = $user['manguoidung'];
        $_SESSION['vaitro'] = $user['vaitro'];
        header("Location: ../../index.php"); // Chuyển hướng đến trang chủ sau khi đăng nhập
        exit();
    } else {
        $error = "Thông tin đăng nhập không đúng.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dangkyvadangnhap.css?v=<?php echo time(); ?>">
    <title>Đăng Nhập</title>
</head>

<body>
    <h1>Đăng Nhập</h1>
    <?php if ($error) : ?>
    <p style="color: red;"><?= $error; ?></p>
    <?php endif; ?>
    <form name="form_dangnhapvsdangky" method="post">
        <div>
            <label for="tennguoidung">Tên đăng nhập:</label>
            <input type="text" id="tennguoidung" name="tennguoidung" required>
        </div>
        <div>
            <label for="matkhau">Mật Khẩu:</label>
            <input type="password" id="matkhau" name="matkhau" required>
        </div>
        <button type="submit">Đăng Nhập</button>
    </form>

</body>

</html>
<?php
include '../../footer.php';?>