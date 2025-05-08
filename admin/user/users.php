<?php
include '../../db_connect.php';
include '../../admin/menu/menu.php';

// Kiểm tra xem người dùng đã đăng nhập và có vai trò admin chưa
if (!isset($_SESSION['manguoidung']) || $_SESSION['vaitro'] !== 'Admin') {
    header("Location:  ../../admin/index.php");
    exit();
}

$error = '';
$success = '';
$editUser = null;

// Xử lý xóa người dùng
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $sql = "DELETE FROM nguoidung WHERE manguoidung = $delete_id";
    if ($conn->query($sql) === TRUE) {
        $success = "Xóa người dùng thành công.";
    } else {
        $error = "Lỗi xóa người dùng: " . $conn->error;
    }
}

// Xử lý thêm/sửa người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manguoidung = isset($_POST['manguoidung']) ? (int)$_POST['manguoidung'] : 0;
    $tennguoidung = $_POST['tennguoidung'];
    $hoten = $_POST['hoten'];
    $vaitro = $_POST['vaitro'];
    $email = $_POST['email'];
    $matkhau = $_POST['matkhau'];
    $diachi = $_POST['diachi'];
    $sdt = $_POST['sdt'];

    if($manguoidung == 0){
        $sql = "INSERT INTO nguoidung (tennguoidung, hoten, vaitro, email, matkhau, diachi, sdt)
            VALUES ('$tennguoidung', '$hoten', '$vaitro', '$email', '$matkhau','$diachi','$sdt')";
        if ($conn->query($sql) === TRUE) {
            $success = "Thêm người dùng thành công.";
        } else {
            $error = "Lỗi thêm người dùng: " . $conn->error;
        }
    }else{
        $sql = "UPDATE nguoidung SET tennguoidung = '$tennguoidung', hoten = '$hoten', vaitro = '$vaitro', email = '$email', matkhau = '$matkhau', diachi='$diachi', sdt='$sdt'  WHERE manguoidung = $manguoidung";
        if ($conn->query($sql) === TRUE) {
            $success = "Cập nhật người dùng thành công.";
        } else {
            $error = "Lỗi cập nhật người dùng: " . $conn->error;
        }
         $editUser = null;
    }
}

// Lấy danh sách người dùng
$sql = "SELECT * FROM nguoidung";
$result = $conn->query($sql);
$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}


if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $sql = "SELECT * FROM nguoidung WHERE manguoidung = $edit_id";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
        $editUser = $result->fetch_assoc();
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="users.css?v=<?php echo time(); ?>">
    <title>Quản Lý Người Dùng</title>
</head>

<body>
    <h1>Quản Lý Người Dùng</h1>
    <?php if ($error) : ?>
    <p style="color: red;"><?= $error; ?></p>
    <?php endif; ?>
    <?php if ($success) : ?>
    <p style="color: green;"><?= $success; ?></p>
    <?php endif; ?>

    <div class="user-action">
        <button onclick="showUserForm(0)">Thêm người dùng</button>
    </div>

    <div id="user-form-popup" class="user-form-popup" style="<?php echo $editUser ? 'display:block;' : '';?>">
        <div class="user-form-popup-content">
            <span class="close-button" onclick="hideUserForm()">×</span>
            <h2><?php echo $editUser ? "Sửa người dùng" : "Thêm người dùng" ?></h2>
            <form method="post">
                <?php if($editUser): ?>
                <input type='hidden' name='manguoidung' value="<?= htmlspecialchars($editUser['manguoidung']) ?>" />
                <?php endif; ?>
                <div>
                    <label for="tennguoidung">Tên đăng nhập:</label>
                    <input type="text" id="tennguoidung" name="tennguoidung" required
                        value="<?= htmlspecialchars($editUser['tennguoidung'] ?? '') ?>">
                </div>
                <div>
                    <label for="hoten">Họ tên:</label>
                    <input type="text" id="hoten" name="hoten"
                        value="<?= htmlspecialchars($editUser['hoten'] ?? '') ?>">
                </div>
                <div>
                    <label for="vaitro">Vai trò:</label>
                    <select name="vaitro" id="vaitro" required>
                        <option value='Khách hàng'
                            <?php if(isset($editUser['vaitro']) && $editUser['vaitro'] == 'Khách hàng') echo 'selected' ?>>
                            Khách hàng</option>
                        <option value='Admin'
                            <?php if(isset($editUser['vaitro']) && $editUser['vaitro'] == 'Admin') echo 'selected' ?>>
                            Admin</option>
                    </select>
                </div>
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required
                        value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
                </div>
                <div>
                    <label for="matkhau">Mật khẩu:</label>
                    <input type="password" id="matkhau" name="matkhau" required
                        value="<?= htmlspecialchars($editUser['matkhau'] ?? '') ?>">
                </div>
                <div>
                    <label for="diachi">Địa chỉ:</label>
                    <textarea id="diachi" name="diachi"><?= htmlspecialchars($editUser['diachi'] ?? '') ?></textarea>
                </div>
                <div>
                    <label for="sdt">Số điện thoại:</label>
                    <input type="text" id="sdt" name="sdt" value="<?= htmlspecialchars($editUser['sdt'] ?? '') ?>">
                </div>
                <button type="submit"><?php echo $editUser ? "Cập nhật" : "Thêm" ?> người dùng</button>
                <?php if($editUser): ?>
                <button type="button" onclick="hideUserForm()">Hủy</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="user-list">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên đăng nhập</th>
                    <th>Họ tên</th>
                    <th>Vai trò</th>
                    <th>Email</th>
                    <th>Địa chỉ</th>
                    <th>Số điện thoại</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                <tr>
                    <td><?= htmlspecialchars($user['manguoidung']); ?></td>
                    <td><?= htmlspecialchars($user['tennguoidung']); ?></td>
                    <td><?= htmlspecialchars($user['hoten']); ?></td>
                    <td><?= htmlspecialchars($user['vaitro']); ?></td>
                    <td><?= htmlspecialchars($user['email']); ?></td>
                    <td><?= htmlspecialchars($user['diachi']); ?></td>
                    <td><?= htmlspecialchars($user['sdt']); ?></td>
                    <td>
                        <button onclick="showUserForm(<?= htmlspecialchars($user['manguoidung']); ?>)">Sửa</button>
                        <a href="users.php?delete_id=<?= htmlspecialchars($user['manguoidung']); ?>"
                            onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này?')">Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
    function showUserForm(editId) {
        const popup = document.getElementById('user-form-popup');
        popup.style.display = 'block';
        if (editId) {
            window.location.href = 'users.php?edit_id=' + editId;
        }
    }

    function hideUserForm() {
        const popup = document.getElementById('user-form-popup');
        popup.style.display = 'none';
        window.location.href = 'users.php';
    }
    </script>
</body>

</html>