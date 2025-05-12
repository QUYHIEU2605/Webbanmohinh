<?php
include '../../db_connect.php';
include '../../admin/menu/menu.php';

// Kiểm tra xem người dùng đã đăng nhập và có vai trò admin chưa
if (!isset($_SESSION['manguoidung']) || $_SESSION['vaitro'] !== 'Admin') {
    header("Location:  ../../admin/index.php");
    exit();
}

// Xử lý thêm người dùng
if (isset($_POST['add_user'])) {
    $tennguoidung = $_POST['tennguoidung'];
    $hoten = $_POST['hoten'];
    $vaitro = $_POST['vaitro'];
    $email = $_POST['email'];
    $diachi = $_POST['diachi'];
    $sdt = $_POST['sdt'];
    $matkhau = $_POST['matkhau'];

    $sql = "INSERT INTO nguoidung (tennguoidung, hoten, vaitro, email, diachi, sdt, matkhau)
            VALUES ('$tennguoidung', '$hoten', '$vaitro', '$email', '$diachi', '$sdt', '$matkhau')";

    $conn->query($sql);
    header("Location: users.php");
    exit();
}

// Xử lý xóa người dùng
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM nguoidung WHERE manguoidung='$id'");
    header("Location: users.php");
    exit();
}

// Lấy danh sách người dùng
$result = $conn->query("SELECT * FROM nguoidung");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng</title>
    <link rel="stylesheet" href="users.css?=<?php echo time(); ?>">
    
</head>
<body>

<h1>Quản lý người dùng</h1>

<!-- Nút mở popup -->
<button onclick="openPopup()">Thêm người dùng</button>

<!-- Popup Form -->
<div class="popup" id="popupForm">
    <div class="popup-content">
        <span class="close-popup" onclick="closePopup()">×</span>
        <h2>Thêm người dùng</h2>
        <form method="POST">
            <input type="text" name="tennguoidung" placeholder="Tên đăng nhập" required>
            <input type="text" name="hoten" placeholder="Họ tên" required>
            <select name="vaitro" required>
                <option value="">-- Chọn vai trò --</option>
                <option value="admin">Admin</option>
                <option value="khách hàng">Khách hàng</option>
            </select>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="diachi" placeholder="Địa chỉ">
            <input type="text" name="sdt" placeholder="Số điện thoại">
            <input type="password" name="matkhau" placeholder="Mật khẩu" required>
            <button type="submit" name="add_user">Thêm người dùng</button>
        </form>
    </div>
</div>

<!-- Bảng danh sách người dùng -->
<table>
    <thead>
        <tr>
            <th>Mã</th>
            <th>Tên đăng nhập</th>
            <th>Họ tên</th>
            <th>Vai trò</th>
            <th>Email</th>
            <th>Địa chỉ</th>
            <th>SĐT</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['manguoidung']; ?></td>
                <td><?php echo $row['tennguoidung']; ?></td>
                <td><?php echo $row['hoten']; ?></td>
                <td><?php echo $row['vaitro']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['diachi']; ?></td>
                <td><?php echo $row['sdt']; ?></td>
                <td>
                    <a href="?delete=<?php echo $row['manguoidung']; ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script>
// Mở popup
function openPopup() {
    document.getElementById('popupForm').style.display = 'block';
}
// Đóng popup
function closePopup() {
    document.getElementById('popupForm').style.display = 'none';
}
</script>

</body>
</html>