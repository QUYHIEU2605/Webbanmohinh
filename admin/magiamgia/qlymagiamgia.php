<?php
include '../../db_connect.php';
include '../../admin/menu/menu.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['manguoidung'])) {
    header("Location: ../../admin/index.php");
    exit();
}

// Kiểm tra vai trò của người dùng
if ($_SESSION['vaitro'] == 'Khách hàng') {
    echo "Bạn không có quyền truy cập trang này.";
     exit();
    header("Location: ../../customer/index.php");
    exit();
}

// Config phân trang
$limit = 10; // Số mã giảm giá trên một trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Trang hiện tại
$start = ($page - 1) * $limit; // Vị trí bắt đầu

// Function to count discount codes
function countDiscountCodes($conn) {
    $sql = "SELECT COUNT(*) as total FROM giamgia";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    return 0;
}

// Function to fetch discount codes
function fetchDiscountCodes($conn, $start, $limit) {
    $sql = "SELECT * FROM giamgia LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "Error preparing statement: " . $conn->error;
        return [];
    }
    $stmt->bind_param("ii", $start, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $discountCodes = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $discountCodes[] = $row;
        }
    }
    $stmt->close();
    return $discountCodes;
}

// Xử lý thêm mới mã giảm giá
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_discount_code'])) {
    $tenma = $_POST['tenma'];
    $giamtheotien = $_POST['giamtheotien'];
    $giamtheophantram = $_POST['giamtheophantram'];
    $loaigiamgia = $_POST['loaigiamgia'];
    $min_price = $_POST['min_price'];
    $mota = isset($_POST['mota']) ? $_POST['mota'] : '';

    $sql = "INSERT INTO giamgia (tenma, giamtheotien, giamtheophantram, loaigiamgia, min_price, mota) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sddsss", $tenma, $giamtheotien, $giamtheophantram, $loaigiamgia, $min_price, $mota);

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']."?page=".$page);
        exit;
    } else {
        echo "Lỗi: " . $stmt->error;
    }
    $stmt->close();
}

// Xử lý chỉnh sửa mã giảm giá
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_discount_code'])) {
    $magiamgia = $_POST['magiamgia'];
    $tenma = $_POST['tenma'];
    $giamtheotien = $_POST['giamtheotien'];
    $giamtheophantram = $_POST['giamtheophantram'];
    $loaigiamgia = $_POST['loaigiamgia'];
    $min_price = $_POST['min_price'];
     $mota = isset($_POST['mota']) ? $_POST['mota'] : '';

    $sql = "UPDATE giamgia SET tenma = ?, giamtheotien = ?, giamtheophantram = ?, loaigiamgia = ?, min_price = ?, mota = ? WHERE magiamgia = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sddsssi", $tenma, $giamtheotien, $giamtheophantram, $loaigiamgia, $min_price, $mota, $magiamgia);

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']."?page=".$page);
        exit;
    } else {
        echo "Lỗi cập nhật mã giảm giá: " . $stmt->error;
    }
    $stmt->close();
}


// Xử lý xóa mã giảm giá
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];

    // Xóa mã giảm giá trong bảng magiamgianguoidung
   $stmt = $conn->prepare("DELETE FROM magiamgianguoidung WHERE magiamgia_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Xóa mã giảm giá
    $stmt = $conn->prepare("DELETE FROM giamgia WHERE magiamgia = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']."?page=".$page);
        exit;
    } else {
        echo "Lỗi xóa mã giảm giá: " . $stmt->error;
    }
    $stmt->close();
}

$totalDiscountCodes = countDiscountCodes($conn);
$totalPages = ceil($totalDiscountCodes / $limit);
$discountCodes = fetchDiscountCodes($conn, $start, $limit);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="stylesheet" href="qlymgg.css?v=<?php echo time(); ?>">
    <title>Quản lý mã giảm giá</title>
</head>
<body>
    <h2>Danh sách mã giảm giá</h2>
    <button onclick="document.getElementById('addDiscountPopup').style.display='block'">Thêm mã giảm giá</button>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                 <th>Tên mã</th>
                <th>Giảm theo tiền</th>
                <th>Giảm theo phần trăm</th>
                 <th>Loại</th>
                 <th>Giá tối thiểu</th>
                <th>Mô tả</th>
                 <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($discountCodes) > 0): ?>
            <?php foreach($discountCodes as $row): ?>
             <tr>
                  <td><?= htmlspecialchars($row['magiamgia']) ?></td>
                  <td><?= htmlspecialchars($row['tenma']) ?></td>
                  <td><?= htmlspecialchars(number_format($row['giamtheotien'], 0, ',', '.')) ?></td>
                   <td><?= htmlspecialchars($row['giamtheophantram']) ?>%</td>
                  <td><?= htmlspecialchars($row['loaigiamgia']) ?></td>
                   <td><?= htmlspecialchars(number_format($row['min_price'], 0, ',', '.')) ?></td>
                    <td class='description-cell' title='<?= htmlspecialchars($row['mota']) ?>'>
                        <?= strlen(htmlspecialchars($row['mota'])) > 50 ? substr(htmlspecialchars($row['mota']), 0, 50) . '...' : htmlspecialchars($row['mota']) ?>
                      </td>
                 <td>
                        <a href='?action=delete&id=<?= $row['magiamgia'] ?>&page=<?= $page ?>' class='btn-delete' onclick='return confirm("Bạn có chắc chắn muốn xóa mã giảm giá này không?")'>Xóa</a>
                       <a href='#' class='btn-edit' onclick='showEditPopup(<?= $row['magiamgia'] ?>, "<?= htmlspecialchars($row['tenma']) ?>", "<?= htmlspecialchars($row['giamtheotien']) ?>", "<?= htmlspecialchars($row['giamtheophantram']) ?>", "<?= htmlspecialchars($row['loaigiamgia']) ?>", "<?= htmlspecialchars(number_format($row['min_price'], 2, '.', '')) ?>", "<?= htmlspecialchars($row['mota']) ?>")'>Sửa</a>
                         <a href='admin_magiamgiauser.php?id=<?= $row['magiamgia'] ?>' class='btn-view' >Xem User</a>
                     </td>
                </tr>
             <?php endforeach; ?>
            <?php else: ?>
                 <tr><td colspan='7'>Không có mã giảm giá nào!</td></tr>
             <?php endif; ?>
        </tbody>
    </table>
       <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= ($page - 1) ?>">❮</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= ($page == $i) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
               <a href="?page=<?= ($page + 1) ?>">❯</a>
            <?php endif; ?>
        </div>
    <div class="popup" id="addDiscountPopup">
         <div class="popup-content">
         <span class="close-popup" onclick="document.getElementById('addDiscountPopup').style.display='none'">×</span>
          <h2>Thêm Mã Giảm Giá Mới</h2>
           <form method="POST" action="">
              <label for="tenma">Tên Mã:</label><br>
              <input type="text" id="tenma" name="tenma" required><br><br>

              <label for="giamtheotien">Giảm Theo Tiền:</label><br>
              <input type="number" step="0.01" id="giamtheotien" name="giamtheotien"><br><br>

              <label for="giamtheophantram">Giảm Theo Phần Trăm:</label><br>
              <input type="number" step="0.01" id="giamtheophantram" name="giamtheophantram"><br><br>
                <label for="min_price">Giá tối thiểu:</label><br>
              <input type="number" step="0.01" id="min_price" name="min_price"><br><br>
                 <label for="mota">Mô tả:</label><br>
                 <textarea id="mota" name="mota" rows="5" cols="40"></textarea><br><br>

             <label for="loaigiamgia">Loại mã giảm giá:</label><br>
                <select id="loaigiamgia" name="loaigiamgia" required>
                  <option value="Công khai">Công khai</option>
                 <option value="Riêng">Riêng</option>
                  </select><br><br>
              <input type="submit" value="Thêm Mã Giảm Giá" name="add_discount_code">
               <button type="button" onclick="document.getElementById('addDiscountPopup').style.display='none'">Hủy</button>
          </form>
        </div>
    </div>
    <div class="popup" id="editDiscountPopup">
         <div class="popup-content">
          <span class="close-popup" onclick="document.getElementById('editDiscountPopup').style.display='none'">×</span>
          <h2>Sửa Mã Giảm Giá</h2>
            <form method="POST" action="">
                <input type="hidden" name="magiamgia" id="edit_magiamgia">
                <label for="tenma">Tên Mã:</label><br>
                <input type="text" id="edit_tenma" name="tenma" required><br><br>

                <label for="giamtheotien">Giảm Theo Tiền:</label><br>
                <input type="number" step="0.01" id="edit_giamtheotien" name="giamtheotien"><br><br>

                <label for="giamtheophantram">Giảm Theo Phần Trăm:</label><br>
                <input type="number" step="0.01" id="edit_giamtheophantram" name="giamtheophantram"><br><br>
                 <label for="min_price">Giá tối thiểu:</label><br>
                 <input type="number" step="0.01" id="edit_min_price" name="min_price"><br><br>

               <label for="mota">Mô tả:</label><br>
                <textarea id="edit_mota" name="mota" rows="5" cols="40"></textarea><br><br>

                 <label for="loaigiamgia">Loại mã giảm giá:</label><br>
                <select id="edit_loaigiamgia" name="loaigiamgia" required>
                  <option value="Công khai">Công khai</option>
                 <option value="Riêng">Riêng</option>
                  </select><br><br>

                <input type="submit" value="Cập nhật Mã Giảm Giá" name="edit_discount_code">
                <button type="button" onclick="document.getElementById('editDiscountPopup').style.display='none'">Hủy</button>
            </form>
         </div>
     </div>
    <script>
        function showEditPopup(magiamgia, tenma, giamtheotien, giamtheophantram, loaigiamgia, min_price, mota) {
             document.getElementById('edit_magiamgia').value = magiamgia;
             document.getElementById('edit_tenma').value = tenma;
             document.getElementById('edit_giamtheotien').value = giamtheotien;
              document.getElementById('edit_giamtheophantram').value = giamtheophantram;
              document.getElementById('edit_loaigiamgia').value = loaigiamgia;
               document.getElementById('edit_min_price').value = min_price;
              document.getElementById('edit_mota').value = mota;
              document.getElementById('editDiscountPopup').style.display = 'block';
        }
           document.addEventListener('DOMContentLoaded', function () {
                    const select = document.getElementById('loaigiamgia');
                     const userSelect = document.getElementById('userSelect');
                    if(select){
                         select.addEventListener('change', function(event) {
                             if (event.target.value === 'Riêng') {
                                 if(userSelect){
                                        userSelect.style.display = 'block';
                                 }
                            } else{
                                 if(userSelect){
                                      userSelect.style.display = 'none';
                                  }
                             }
                     });
                   }
                });
    </script>
</body>
</html>