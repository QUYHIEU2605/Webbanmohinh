<?php

include '../../db_connect.php';
include '../../admin/menu/menu.php';

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
// Lấy id mã giảm giá
$magiamgia_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($magiamgia_id <= 0) {
    echo "Không có ID mã giảm giá.";
    exit();
}
// Xử lý thêm người dùng vào mã giảm giá
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_users_to_discount'])) {
     if(isset($_POST['manguoidung']) && is_array($_POST['manguoidung'])){
           foreach($_POST['manguoidung'] as $manguoidung_id){
                // Kiểm tra xem người dùng đã được cấp mã này chưa
                $stmtCheck = $conn->prepare("SELECT * FROM magiamgianguoidung WHERE magiamgia_id = ? AND manguoidung_id = ?");
                $stmtCheck->bind_param("ii", $magiamgia_id, $manguoidung_id);
                $stmtCheck->execute();
                $resultCheck = $stmtCheck->get_result();
                   if ($resultCheck->num_rows == 0) {
                          $stmt = $conn->prepare("INSERT INTO magiamgianguoidung (magiamgia_id, manguoidung_id) VALUES (?, ?)");
                           $stmt->bind_param("ii", $magiamgia_id, $manguoidung_id);
                             if (!$stmt->execute()) {
                                  echo "Lỗi khi thêm người dùng: " . $stmt->error;
                                }
                             $stmt->close();
                        }
                  $stmtCheck->close();
             }
          header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $magiamgia_id);
        exit;
     }
}

// Xử lý xóa người dùng khỏi mã giảm giá
if (isset($_GET['action']) && $_GET['action'] == 'delete_user' && isset($_GET['user_id'])) {
    $manguoidung_id = intval($_GET['user_id']);

    $stmt = $conn->prepare("DELETE FROM magiamgianguoidung WHERE magiamgia_id = ? AND manguoidung_id = ?");
      $stmt->bind_param("ii", $magiamgia_id, $manguoidung_id);
       if ($stmt->execute()) {
             header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $magiamgia_id);
             exit;
        } else {
             echo "Lỗi xóa người dùng khỏi mã giảm giá: " . $stmt->error;
        }
    $stmt->close();
}
// Truy vấn người dùng
$sqlUsers = "SELECT * FROM nguoidung";
$resultUsers = $conn->query($sqlUsers);
$users = [];
if ($resultUsers->num_rows > 0) {
    while ($row = $resultUsers->fetch_assoc()) {
        $users[] = $row;
    }
}
// Truy vấn người dùng đã có mã giảm giá
$sqlDiscountUsers = "SELECT nd.manguoidung, nd.tennguoidung, nd.hoten, mggnd.magiamgianguoidung_id
                     FROM nguoidung nd
                     INNER JOIN magiamgianguoidung mggnd ON nd.manguoidung = mggnd.manguoidung_id
                     WHERE mggnd.magiamgia_id = $magiamgia_id";
$resultDiscountUsers = $conn->query($sqlDiscountUsers);
$discountUsers = [];
if ($resultDiscountUsers->num_rows > 0) {
    while ($row = $resultDiscountUsers->fetch_assoc()) {
        $discountUsers[] = $row;
    }
}

// Tạo một mảng chứa các ID người dùng đã được cấp mã
$discountUserIds = array_column($discountUsers, 'manguoidung');
// Lọc bỏ người dùng đã được cấp mã giảm giá
$users = array_filter($users, function($user) use ($discountUserIds) {
    return !in_array($user['manguoidung'], $discountUserIds);
});

 $conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Người Dùng Của Mã Giảm Giá</title>
    <link rel="stylesheet" href="admin_magiamgiauser.css?v=<?php echo time(); ?>">

</head>

<body>
    <h1>Quản lý người dùng của mã giảm giá</h1>
    <div class="user-lists-container">
        <div class="user-list">
            <h2>Danh sách người dùng</h2>
            <form method="POST" action="" id='addUserForm'>
                <div class="user-item">
                    <input type="checkbox" id="select-all-users">
                    <label for="select-all-users">Chọn tất cả</label>
                </div>
                <?php foreach ($users as $user): ?>
                <div class="user-item">
                    <input type="checkbox" name="manguoidung[]" class='user-checkbox'
                        id="user_<?= htmlspecialchars($user['manguoidung']) ?>"
                        value="<?= htmlspecialchars($user['manguoidung']) ?>">
                    <label
                        for="user_<?= htmlspecialchars($user['manguoidung']) ?>"><?= htmlspecialchars($user['tennguoidung']) ?>
                        - <?= htmlspecialchars($user['hoten']) ?></label>
                </div>
                <?php endforeach; ?>
                <br><br>
                <button type='submit' name='add_users_to_discount'>Thêm người dùng</button>
            </form>
        </div>
        <div class="user-list">
            <h2>Danh sách người dùng đã được cấp mã giảm giá</h2>
            <?php if (empty($discountUsers)): ?>
            <p>Không có người dùng nào được cấp mã giảm giá này.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên đăng nhập</th>
                        <th>Họ và tên</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discountUsers as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['manguoidung']) ?></td>
                        <td><?= htmlspecialchars($user['tennguoidung']) ?></td>
                        <td><?= htmlspecialchars($user['hoten']) ?></td>
                        <td>
                            <a href="?action=delete_user&id=<?= $magiamgia_id ?>&user_id=<?= htmlspecialchars($user['manguoidung']) ?>"
                                onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này khỏi mã giảm giá?')"
                                class="btn-delete">Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('select-all-users');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        const addUserForm = document.getElementById('addUserForm');

        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
        addUserForm.addEventListener('submit', function() {
            if (!confirm('Bạn có chắc chắn muốn thêm những người dùng đã chọn?')) {
                event.preventDefault();
            }
        });
    });
    </script>
</body>

</html>