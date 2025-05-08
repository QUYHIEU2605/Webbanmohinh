<?php
include '../../db_connect.php';
include '../../customer/menu/menu_customer.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['manguoidung'])) {
    header("Location: login_customer.php");
    exit();
}

$manguoidung = $_SESSION['manguoidung'];

// Lấy thông tin người dùng
$sql = "SELECT hoten, diachi, sdt, email FROM nguoidung WHERE manguoidung = $manguoidung";
$result = $conn->query($sql);

if ($result->num_rows != 1) {
    echo "Không tìm thấy thông tin người dùng.";
    exit();
}
$user = $result->fetch_assoc();

$thongbao = '';
$error_change_pass = '';

// Xử lý form cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $hoten = $_POST['hoten'];
    $diachi = $_POST['diachi'];
    $sdt = $_POST['sdt'];
    $sqlUpdate = "UPDATE nguoidung SET hoten = '$hoten', diachi = '$diachi', sdt = '$sdt' WHERE manguoidung = $manguoidung";

    if ($conn->query($sqlUpdate) === TRUE) {
      $thongbao = "Thông tin đã được cập nhật thành công.";
      $sql = "SELECT hoten, diachi, sdt, email FROM nguoidung WHERE manguoidung = $manguoidung";
      $result = $conn->query($sql);
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
          }

    } else {
        $thongbao = "Lỗi cập nhật thông tin: " . $conn->error;
    }
}

// Xử lý form đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $matkhaucu = $_POST['matkhaucu'];
    $matkhaumoi = $_POST['matkhaumoi'];
    $xacnhanmatkhau = $_POST['xacnhanmatkhau'];
      // Lấy mật khẩu hiện tại của người dùng từ database
    $sqlCheckPass = "SELECT matkhau FROM nguoidung WHERE manguoidung = $manguoidung";
    $resultCheckPass = $conn->query($sqlCheckPass);
    if ($resultCheckPass->num_rows == 1) {
           $rowCheckPass = $resultCheckPass->fetch_assoc();
            if($rowCheckPass['matkhau'] == $matkhaucu){
                if($matkhaumoi == $xacnhanmatkhau){
                      $sqlUpdatePassword = "UPDATE nguoidung SET matkhau = '$matkhaumoi' WHERE manguoidung = $manguoidung";
                     if ($conn->query($sqlUpdatePassword) === TRUE) {
                         $thongbao = "Mật khẩu đã được thay đổi thành công.";
                    }else {
                         $error_change_pass = "Lỗi cập nhật mật khẩu: " . $conn->error;
                      }
                }else {
                    $error_change_pass ="Mật khẩu mới không khớp";
                }
          }else{
            $error_change_pass = "Mật khẩu cũ không đúng";
          }
     }

}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="user_form.css?v=<?php echo time(); ?>">
    <title>Hồ Sơ Người Dùng</title>
</head>

<body>
    <h1>Hồ Sơ Người Dùng</h1>
    <?php if ($thongbao) : ?>
    <p style="color: green;"><?= $thongbao; ?></p>
    <?php endif; ?>
    <div class="profile-container">
        <div class="profile-info">
            <h2>Thông Tin Cá Nhân</h2>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
            <form method="post">
                <div>
                    <label for="hoten">Họ Tên:</label>
                    <input type="text" name="hoten" id="hoten" value="<?= htmlspecialchars($user['hoten']); ?>"
                        required>
                </div>
                <div>
                    <label for="diachi">Địa Chỉ:</label>
                    <textarea name="diachi" id="diachi"><?= htmlspecialchars($user['diachi']); ?></textarea>
                </div>
                <div>
                    <label for="sdt">Số Điện Thoại:</label>
                    <input type="text" name="sdt" id="sdt" value="<?= htmlspecialchars($user['sdt']); ?>">
                </div>
                <button type="submit" name="update_profile">Cập Nhật Thông Tin</button>
            </form>
        </div>
        <div class="password-change">
            <h2>Đổi Mật Khẩu</h2>
            <?php if ($error_change_pass) : ?>
            <p style="color: red;"><?= $error_change_pass; ?></p>
            <?php endif; ?>
            <form method="post">
                <div>
                    <label for="matkhaucu">Mật Khẩu Hiện Tại:</label>
                    <input type="password" name="matkhaucu" id="matkhaucu" required>
                </div>
                <div>
                    <label for="matkhaumoi">Mật Khẩu Mới:</label>
                    <input type="password" name="matkhaumoi" id="matkhaumoi" required>
                </div>
                <div>
                    <label for="xacnhanmatkhau">Xác Nhận Mật Khẩu Mới:</label>
                    <input type="password" name="xacnhanmatkhau" id="xacnhanmatkhau" required>
                </div>
                <button type="submit" name="change_password">Đổi Mật Khẩu</button>
            </form>
        </div>
    </div>
</body>

</html>
<?php
include '../../footer.php';?>