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
// Xử lý xóa đơn vị vận chuyển
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $madonvi = $_GET['id'];

    $sql = "DELETE FROM donvivanchuyen WHERE madonvi = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $madonvi);

    if ($stmt->execute()) {
         echo "<script>alert('Xóa thành công');</script>";
        header("Location: " . $_SERVER['PHP_SELF']);
          exit;
    } else {
         echo "<script>alert('Lỗi: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}
// Xử lý thêm đơn vị vận chuyển
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $tendonvi = $_POST['tendonvi'];
    $giavanchuyen = $_POST['giavanchuyen'];

    $sql = "INSERT INTO donvivanchuyen (tendonvi, giavanchuyen) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sd", $tendonvi, $giavanchuyen);

    if ($stmt->execute()) {
       echo "<script>alert('Thêm thành công');</script>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
      echo "<script>alert('Lỗi: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}
// Xử lý cập nhật đơn vị vận chuyển
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
     $madonvi = $_POST['madonvi'];
    $tendonvi = $_POST['tendonvi'];
    $giavanchuyen = $_POST['giavanchuyen'];

    $sql = "UPDATE donvivanchuyen SET tendonvi = ?, giavanchuyen = ? WHERE madonvi = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdi", $tendonvi, $giavanchuyen, $madonvi);

    if ($stmt->execute()) {
        echo "<script>alert('Cập nhật thành công');</script>";
         header("Location: " . $_SERVER['PHP_SELF']);
          exit;
    } else {
       echo "<script>alert('Lỗi: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

// Truy vấn danh sách đơn vị vận chuyển
$sql = "SELECT * FROM donvivanchuyen";
$result = $conn->query($sql);


?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="danhsachvanchuyen.css?v=<?php echo time(); ?>">
    <title>Danh Sách Đơn Vị Vận Chuyển</title>
</head>
<body>
    <h2>Danh Sách Đơn Vị Vận Chuyển</h2>
     <button id="addShippingButton">Thêm Đơn Vị Vận Chuyển</button>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>Mã Đơn Vị</th>
                <th>Tên Đơn Vị</th>
                <th>Giá Vận Chuyển</th>
                <th>Hành Động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['madonvi']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['tendonvi']) . "</td>";
                    echo "<td>" . htmlspecialchars(number_format($row['giavanchuyen'], 0, ',', '.')) . " VNĐ</td>";
                     echo "<td>
                        <button class='btn-update' data-id='" . $row['madonvi'] . "' data-name='" . htmlspecialchars($row['tendonvi']) . "' data-price='" . htmlspecialchars($row['giavanchuyen']) . "'>Sửa</button>
                         <a href='?action=delete&id=" . $row['madonvi'] . "' class='btn-delete' onclick='return confirm(\"Bạn có chắc chắn muốn xóa đơn vị vận chuyển này không?\")'>Xóa</a>
                     </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>Không có đơn vị vận chuyển nào!</td></tr>";
            }
            ?>
        </tbody>
    </table>
      <!-- The Modal for Add -->
     <div id="addShippingModal" class="modal">
         <div class="modal-content">
              <span class="close" id="closeModal">×</span>
             <h2>Thêm Đơn Vị Vận Chuyển</h2>
             <form method="POST" action="">
                <input type="hidden" name="action" value="add">
              <label for="tendonvi">Tên Đơn Vị:</label><br>
              <input type="text" id="tendonvi" name="tendonvi" required><br><br>

              <label for="giavanchuyen">Giá Vận Chuyển:</label><br>
              <input type="number" step="0.01" id="giavanchuyen" name="giavanchuyen" required><br><br>
              <input type="submit" value="Thêm Đơn Vị">
               <button type="button" id="cancelAddButton" >Hủy</button>
              </form>
        </div>
    </div>
    <!-- The Modal for Edit -->
    <div id="editShippingModal" class="modal">
         <div class="modal-content">
              <span class="close" id="closeEditModal">×</span>
             <h2>Sửa Đơn Vị Vận Chuyển</h2>
             <form method="POST" action="">
                  <input type="hidden" name="action" value="edit">
                <input type="hidden" id="editmadonvi" name="madonvi">
              <label for="tendonvi">Tên Đơn Vị:</label><br>
              <input type="text" id="edittendonvi" name="tendonvi" required><br><br>

              <label for="giavanchuyen">Giá Vận Chuyển:</label><br>
              <input type="number" step="0.01" id="editgiavanchuyen" name="giavanchuyen" required><br><br>
              <input type="submit" value="Cập Nhật">
               <button type="button" id="cancelEditButton" >Hủy</button>
              </form>
        </div>
    </div>
      <script src="danhsachvanchuyen.js"></script>
</body>
</html>

<?php
$conn->close();
?>