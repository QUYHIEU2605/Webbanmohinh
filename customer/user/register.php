<?php
include '../../db_connect.php'; // Kết nối cơ sở dữ liệu
include '../../customer/menu/menu_customer.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tennguoidung = $_POST['tennguoidung'];
    $hoten = $_POST['hoten'];
    $email = $_POST['email'];
    $matkhau = $_POST['matkhau'];
    $sdt = $_POST['sdt']; // Lấy số điện thoại
    $diachi = $_POST['diachi']; // Lấy địa chỉ

    // Kiểm tra xem tên người dùng đã tồn tại trong database chưa
     $sqlCheckTenNguoiDung = "SELECT tennguoidung FROM nguoidung WHERE tennguoidung = '$tennguoidung'";
     $resultCheckTenNguoiDung = $conn->query($sqlCheckTenNguoiDung);
     if ($resultCheckTenNguoiDung->num_rows > 0) {
       $error = "Tên đăng nhập đã được sử dụng";
     }else {
          // Kiểm tra xem email đã tồn tại trong database chưa
        $sqlCheckEmail = "SELECT email FROM nguoidung WHERE email = '$email'";
        $resultCheckEmail = $conn->query($sqlCheckEmail);
        if ($resultCheckEmail->num_rows > 0) {
             $error = "Email đã được sử dụng";
        }else{
            // Thêm người dùng mới vào database
            $sql = "INSERT INTO nguoidung (tennguoidung, hoten, vaitro, email, matkhau, sdt, diachi) VALUES ('$tennguoidung', '$hoten', 'Khách hàng', '$email', '$matkhau', '$sdt', '$diachi')";
            if ($conn->query($sql) === TRUE) {
                  header("Location: login_customer.php"); // Chuyển hướng sang trang đăng nhập
                  exit();
              } else {
                    $error = "Lỗi thêm tài khoản: " . $conn->error;
              }
         }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dangkyvadangnhap.css?v=<?php echo time(); ?>">
    <title>Đăng Ký</title>
</head>

<body>
    <h1>Đăng Ký</h1>
    <?php if ($error) : ?>
    <p style="color: red;"><?= $error; ?></p>
    <?php endif; ?>
    <form name="form_dangnhapvsdangky" method="post">
        <div>
            <label for="tennguoidung">Tên đăng nhập:</label>
            <input type="text" id="tennguoidung" name="tennguoidung" required>
        </div>
        <div>
            <label for="hoten">Họ tên:</label>
            <input type="text" id="hoten" name="hoten" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="matkhau">Mật khẩu:</label>
            <input type="password" id="matkhau" name="matkhau" required>
        </div>
        <div>
            <label for="sdt">Số điện thoại:</label>
            <input type="text" id="sdt" name="sdt">
        </div>
        <div>
            <label for="diachi">Địa chỉ:</label>
            <textarea id="diachi" name="diachi"></textarea>
        </div>
        <button type="submit">Đăng ký</button>
    </form>
</body>

</html>
<?php
include '../../footer.php';?>