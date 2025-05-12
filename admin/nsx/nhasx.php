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
// Truy vấn danh sách nhà sản xuất
$sql = "SELECT * FROM nhasanxuat";
$result = $conn->query($sql);

// Xử lý thêm nhà sản xuất
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $tenhang = $_POST['tenhang'];
    $sdthang = $_POST['sdthang'];
    $diachi = $_POST['diachi'];

    $stmt = $conn->prepare("INSERT INTO nhasanxuat (tenhang, sdthang, diachi) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $tenhang, $sdthang, $diachi);

    if ($stmt->execute()) {
        echo "<script>alert('Thêm nhà sản xuất thành công!'); window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Lỗi khi thêm nhà sản xuất: {$stmt->error}');</script>";
    }

    $stmt->close();
}

// Xử lý cập nhật nhà sản xuất
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $masx = $_POST['masx'];
    $tenhang = $_POST['tenhang'];
    $sdthang = $_POST['sdthang'];
    $diachi = $_POST['diachi'];

    $stmt = $conn->prepare("UPDATE nhasanxuat SET tenhang = ?, sdthang = ?, diachi = ? WHERE masx = ?");
    $stmt->bind_param("sssi", $tenhang, $sdthang, $diachi, $masx);

    if ($stmt->execute()) {
        echo "<script>alert('Cập nhật thành công!'); window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Lỗi khi cập nhật: {$stmt->error}');</script>";
    }

    $stmt->close();
}

// Xử lý xóa nhà sản xuất
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    $stmt = $conn->prepare("DELETE FROM nhasanxuat WHERE masx = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo "<script>alert('Xóa nhà sản xuất thành công!');</script>";
        echo "<script>window.location.href = 'nhasx.php';</script>";
        exit(); // Dừng thực thi script
    } else {
        echo "<script>alert('Lỗi khi xóa nhà sản xuất: {$stmt->error}');</script>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="nhasx.css?v=<?php echo time(); ?>">
    <title>Danh sách nhà sản xuất</title>

</head>

<body>
    <h2>Danh sách nhà sản xuất</h2>
    <button class="btn-add" id="btn-add">Thêm Nhà Sản Xuất</button>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>Mã NSX</th>
                <th>Tên hãng</th>
                <th>Số điện thoại</th>
                <th>Địa chỉ</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['masx']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['tenhang']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['sdthang']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['diachi']) . "</td>";
                    echo "<td>
                        <button class='btn btn-edit' onclick='openEditPopup(" . json_encode($row) . ")'>Sửa</button>
                        <a href='?delete_id=" . htmlspecialchars($row['masx']) . "' class='btn btn-delete' onclick='return confirm(\"Bạn có chắc chắn muốn xóa nhà sản xuất này?\");'>Xóa</a>
                    </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>Không có nhà sản xuất nào!</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Popup thêm nhà sản xuất -->
    <div class="popup-overlay" id="popup-overlay"></div>
    <div class="popup" id="popup-add">
        <h3>Thêm Nhà Sản Xuất</h3>
        <form method="POST" action="">
            <input type="hidden" name="add" value="1">
            <input type="text" name="tenhang" placeholder="Tên hãng" required>
            <input type="text" name="sdthang" placeholder="Số điện thoại" required>
            <input type="text" name="diachi" placeholder="Địa chỉ" required>
            <button type="submit">Lưu</button>
            <button type="button" class="btn-close" id="btn-close-add">Đóng</button>
        </form>
    </div>

    <!-- Popup sửa nhà sản xuất -->
    <div class="popup" id="popup-edit">
        <h3>Sửa Nhà Sản Xuất</h3>
        <form method="POST" action="">
            <input type="hidden" name="update" value="1">
            <input type="hidden" id="edit-masx" name="masx">
            <input type="text" id="edit-tenhang" name="tenhang" placeholder="Tên hãng" required>
            <input type="text" id="edit-sdthang" name="sdthang" placeholder="Số điện thoại" required>
            <input type="text" id="edit-diachi" name="diachi" placeholder="Địa chỉ" required>
            <button type="submit">Cập Nhật</button>
            <button type="button" class="btn-close" id="btn-close-edit">Đóng</button>
        </form>
    </div>

    <script>
    const btnAdd = document.getElementById('btn-add');
    const popupAdd = document.getElementById('popup-add');
    const popupOverlay = document.getElementById('popup-overlay');
    const btnCloseAdd = document.getElementById('btn-close-add');
    const popupEdit = document.getElementById('popup-edit');
    const btnCloseEdit = document.getElementById('btn-close-edit');

    btnAdd.addEventListener('click', () => {
        popupAdd.style.display = 'block';
        popupOverlay.style.display = 'block';
    });

    btnCloseAdd.addEventListener('click', closePopup);
    btnCloseEdit.addEventListener('click', closePopup);
    popupOverlay.addEventListener('click', closePopup);

    function closePopup() {
        popupAdd.style.display = 'none';
        popupEdit.style.display = 'none';
        popupOverlay.style.display = 'none';
    }

    function openEditPopup(data) {
        document.getElementById('edit-masx').value = data.masx;
        document.getElementById('edit-tenhang').value = data.tenhang;
        document.getElementById('edit-sdthang').value = data.sdthang;
        document.getElementById('edit-diachi').value = data.diachi;

        popupEdit.style.display = 'block';
        popupOverlay.style.display = 'block';
    }
    </script>
</body>

</html>