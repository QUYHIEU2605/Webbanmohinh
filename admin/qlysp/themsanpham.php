<?php
// Kết nối cơ sở dữ liệu
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
$sql = "SELECT tenhang FROM nhasanxuat";
$result = $conn->query($sql);

// Truy vấn danh sách loại sản phẩm
$sql_types = "SELECT DISTINCT loaisanpham FROM sanpham";
$result_types = $conn->query($sql_types);

$sql_types1 = "SELECT DISTINCT tinhtrang FROM sanpham";
$result_types1 = $conn->query($sql_types1);

$mieuta = ''; // Initialize mieuta variable with empty string
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $tensanpham = $_POST['tensanpham'];
    $loaisanpham = $_POST['loaisanpham'];
    $tinhtrang = $_POST['tinhtrang'];
    $tenhang = $_POST['tenhang'];
    $soluong = $_POST['soluong'];
    $giaban = $_POST['giaban'];
    $gianhap = $_POST['gianhap'];
    $giamgia = !empty($_POST['giamgia']) ? $_POST['giamgia'] : 0; // Mặc định là 0 nếu không nhập
    $mieuta = isset($_POST['mieuta']) ? $_POST['mieuta'] : ''; // Get mieuta after form submission, and set it to empty if not set
    
     // Validate if loaisanpham is a dropdown item or not
    if(empty($loaisanpham)){
        $loaisanpham = $_POST['new_loaisanpham'];
    }
    if(empty($tinhtrang)){
        $tinhtrang = $_POST['new_tinhtrang'];
    }


    // Tìm mã nhà sản xuất từ tên nhà sản xuất
    $stmt = $conn->prepare("SELECT masx FROM nhasanxuat WHERE tenhang = ?");
    $stmt->bind_param("s", $tenhang);
    $stmt->execute();
    $stmt->bind_result($masx);
    $stmt->fetch();
    $stmt->close();

    if ($masx) {
        $uploaded_files = [];
        $target_dir = "../../uploads/"; // Thư mục lưu ảnh

        // Lặp qua các tệp được upload
        foreach ($_FILES['anh']['name'] as $key => $name) {
            $tmp_name = $_FILES['anh']['tmp_name'][$key];
            $size = $_FILES['anh']['size'][$key];
            $error = $_FILES['anh']['error'][$key];
            $imageFileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($error === 0) {
                // Kiểm tra kích thước tệp (giới hạn 5MB)
                if ($size > 5000000) {
                    echo "Tệp $name quá lớn.<br>";
                    continue;
                }

                // Chỉ cho phép các định dạng ảnh
                if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    echo "Tệp $name không đúng định dạng.<br>";
                    continue;
                }

                $new_file_name = uniqid() . '.' . $imageFileType;
                $target_file = $target_dir . $new_file_name;

                if (move_uploaded_file($tmp_name, $target_file)) {
                    $uploaded_files[] = $new_file_name; // Lưu tên ảnh
                } else {
                    echo "Không thể tải lên tệp $name.<br>";
                }
            } else {
                echo "Lỗi khi tải tệp $name.<br>";
            }
        }
        $anh = implode(',', $uploaded_files);

        // Thêm sản phẩm vào bảng sanpham
        $sql = "INSERT INTO sanpham (tensanpham, anh, mieuta, loaisanpham, tinhtrang, masx, soluong, giaban, gianhap, giamgia) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssidddd", $tensanpham, $anh, $mieuta, $loaisanpham, $tinhtrang, $masx, $soluong, $giaban, $gianhap, $giamgia);

        if ($stmt->execute()) {
            // Chuyển hướng để làm mới form và tránh thêm lại sản phẩm
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Lỗi: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Không tìm thấy mã nhà sản xuất cho nhà sản xuất đã chọn!";
    }
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="themsanpham.css?v=<?php echo time(); ?>">
    <title>Thêm Sản Phẩm</title>
</head>

<body>
    <h2>Thêm sản phẩm mới</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <label for="tensanpham">Tên sản phẩm:</label><br>
        <input type="text" id="tensanpham" name="tensanpham" required><br><br>

        <label for="loaisanpham">Loại sản phẩm:</label><br>
        <select id="loaisanpham" name="loaisanpham">
            <option value="">Chọn loại sản phẩm</option>
            <?php
             if ($result_types->num_rows > 0) {
                    while ($row = $result_types->fetch_assoc()) {
                         echo "<option value='" . htmlspecialchars($row['loaisanpham']) . "'>" . htmlspecialchars($row['loaisanpham']) . "</option>";
                     }
                 }
            ?>
        </select>
        <input type="text" id="new_loaisanpham" name="new_loaisanpham" placeholder="Nhập loại mới nếu không có"><br><br>

        <label for="tinhtrang">Tình trạng sản phẩm:</label><br>
        <select id="tinhtrang" name="tinhtrang">
            <option value="">Chọn tình trạng sản phẩm</option>
            <?php
             if ($result_types1->num_rows > 0) {
                    while ($row = $result_types1->fetch_assoc()) {
                         echo "<option value='" . htmlspecialchars($row['tinhtrang']) . "'>" . htmlspecialchars($row['tinhtrang']) . "</option>";
                     }
                 }
            ?>
        </select>
        <input type="text" id="new_tinhtrang" name="new_tinhtrang"
            placeholder="Nhập tình trạng mới nếu không có"><br><br>

        <label for="tenhang">Nhà sản xuất:</label><br>
        <select id="tenhang" name="tenhang" required>
            <option value="">Chọn nhà sản xuất</option>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($row['tenhang']) . "'>" . htmlspecialchars($row['tenhang']) . "</option>";
                }
            }
            ?>
        </select><br><br>

        <label for="soluong">Số lượng:</label><br>
        <input type="number" id="soluong" name="soluong" required><br><br>

        <label for="giaban">Giá bán:</label><br>
        <input type="number" step="0.01" id="giaban" name="giaban" required><br><br>

        <label for="gianhap">Giá nhập:</label><br>
        <input type="number" step="0.01" id="gianhap" name="gianhap" required><br><br>

        <label for="giamgia">Giảm giá:</label><br>
        <input type="number" step="0.01" id="giamgia" name="giamgia"><br><br>

        <label for="anh">Chọn hoặc Kéo Thả Ảnh (Có thể chọn nhiều ảnh):</label><br>
        <div class="drop-area" id="drop-area">
            Kéo thả ảnh vào đây hoặc nhấn để chọn ảnh
        </div>
        <input type="file" id="anh" name="anh[]" accept="image/*" multiple style="display: none;" required>
        <div class="preview" id="preview"></div><br><br>

        <label for="mieuta">Miêu tả:</label><br>
        <textarea id="mieuta" name="mieuta" rows="5" cols="40"
            required><?= htmlspecialchars($mieuta); ?></textarea><br><br>

        <input type="submit" value="Thêm Sản Phẩm">
        <button onclick="window.location.href='danhsachsp.php'" type="button">Hủy</button>
    </form>

    <script src="themsp.js?v=<?php echo time(); ?>"></script>
</body>

</html>