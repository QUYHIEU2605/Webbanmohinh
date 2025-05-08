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
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo "Không có ID sản phẩm.";
    exit();
}

// Truy vấn thông tin sản phẩm hiện tại
$sql = "SELECT * FROM sanpham WHERE masanpham = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo "Không tìm thấy sản phẩm.";
    exit();
}

$tenError = $loaiError = $tinhtrangError = $giaError =$gianhapError = $soLuongError = "";
$ten = $loai = $tinhtrang = $masx = $gia =$gianhap = $soLuong = $giamGia = $mieuTa = $anh = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ten = trim($_POST["tensanpham"]);
    $loai = trim($_POST["loaisanpham"]);
    $tinhtrang = trim($_POST["tinhtrang"]);
    $masx = intval($_POST["masx"]);
    $gia = floatval($_POST["giaban"]);
    $gianhap = floatval($_POST["gianhap"]);
    $soLuong = intval($_POST["soluong"]);
    $giamGia = floatval($_POST["giamgia"]);
    $mieuTa = trim($_POST["mieuta"]);

    // Validate
    if (empty($ten)) {
        $tenError = "Tên sản phẩm không được để trống.";
    }
    if (empty($loai)) {
        $loaiError = "Loại sản phẩm không được để trống.";
    }
    if (empty($tinhtrang)) {
        $tinhtrangError = "Tình trang sản phẩm không được để trống.";
    }
    if ($gia <= 0) {
        $giaError = "Giá bán phải lớn hơn 0.";
    }
    if($gianhap<=0){
        $giaError = "Giá nhập phải lớn hơn 0.";
    }
    if ($soLuong < 0) {
        $soLuongError = "Số lượng không được âm.";
    }

    if (empty($tenError) && empty($loaiError) && empty($giaError) && empty($gianhapError) && empty($soLuongError)) {
        // Xử lý ảnh
        $targetDir = "../../uploads/";
        $anh_arr = explode(",", $product['anh']);
        $anhPaths = [];
        
         // Xử lý ảnh đã xóa
          if (isset($_POST['remove_images']) && !empty($_POST['remove_images'])) {
            $removeImages = explode(",", $_POST['remove_images']);
            foreach ($removeImages as $removeImage) {
               $removeImageName = basename($removeImage);
                // Xóa ảnh khỏi hệ thống tệp
                if(file_exists("../../uploads/" .$removeImageName)){
                    unlink("../../uploads/" . $removeImageName);
                }
                // Loại bỏ ảnh khỏi danh sách ảnh hiện tại
                $index = array_search($removeImage, $anh_arr);
                if ($index !== false) {
                    unset($anh_arr[$index]);
                }
            }
        }
           // Xử lý ảnh mới tải lên
        if (isset($_FILES["new_images"]) && !empty($_FILES["new_images"]["name"][0])) {
            $uploadOk = 1;
          foreach ($_FILES["new_images"]["name"] as $key => $name) {
                 $targetFile = $targetDir . basename($name);
                 $imageFileType = strtolower(pathinfo($targetFile,PATHINFO_EXTENSION));
                  // Kiểm tra xem có phải là ảnh không
                 $check = getimagesize($_FILES["new_images"]["tmp_name"][$key]);
                 if($check === false) {
                       $uploadOk = 0;
                  }

                   // Kiểm tra kích thước file
                   if ($_FILES["new_images"]["size"][$key] > 500000) {
                       $uploadOk = 0;
                   }
                    // Kiểm tra định dạng file
                    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                    && $imageFileType != "gif" ) {
                      $uploadOk = 0;
                    }
                     if ($uploadOk == 1) {
                        if (move_uploaded_file($_FILES["new_images"]["tmp_name"][$key], $targetFile)) {
                             $anhPaths[] = basename($targetFile);
                         }
                     }
              }
         }
        $anhPaths = array_merge($anhPaths, $anh_arr);
        $anh = implode(",", $anhPaths);
           // Cập nhật thông tin sản phẩm
         $sql = "UPDATE sanpham SET tensanpham=?, loaisanpham=?, tinhtrang=?, masx=?, giaban=?, gianhap=?, soluong=?, giamgia=?, mieuta=?, anh=? WHERE masanpham=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiddiissi", $ten, $loai, $tinhtrang, $masx, $gia, $gianhap, $soLuong, $giamGia, $mieuTa, $anh, $id);

        if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
                exit();
            } else {
                echo "Lỗi cập nhật sản phẩm: " . $stmt->error;
            }

            $stmt->close();
     }
}
// Truy vấn danh sách nhà sản xuất
$sqlNSX = "SELECT masx, tenhang FROM nhasanxuat";
$resultNSX = $conn->query($sqlNSX);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="capnhat.css?v=<?php echo time(); ?>">
    <title>Cập Nhật Sản Phẩm</title>

</head>

<body>
    <h2>Cập Nhật Sản Phẩm</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" method="post"
        enctype="multipart/form-data">
        <div>
            <label for="tensanpham">Tên Sản Phẩm:</label>
            <input type="text" id="tensanpham" name="tensanpham"
                value="<?php echo htmlspecialchars($product['tensanpham']); ?>">
            <span class="error"><?php echo $tenError; ?></span>
        </div>
        <div>
            <label for="loaisanpham">Loại Sản Phẩm:</label>
            <input type="text" id="loaisanpham" name="loaisanpham"
                value="<?php echo htmlspecialchars($product['loaisanpham']); ?>">
            <span class="error"><?php echo $loaiError; ?></span>
        </div>
        <div>
            <label for="tinhtrang">Tình Trạng Sản Phẩm:</label>
            <input type="text" id="tinhtrang" name="tinhtrang"
                value="<?php echo htmlspecialchars($product['tinhtrang']); ?>">
            <span class="error"><?php echo $loaiError; ?></span>
        </div>
        <div>
            <label for="masx">Nhà Sản Xuất:</label>
            <select name="masx" id="masx">
                <?php
                if ($resultNSX->num_rows > 0) {
                    while ($rowNSX = $resultNSX->fetch_assoc()) {
                        $selected = ($product['masx'] == $rowNSX['masx']) ? "selected" : "";
                        echo "<option value='" . $rowNSX['masx'] . "' " . $selected . ">" . htmlspecialchars($rowNSX['tenhang']) . "</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div>
            <label for="giaban">Giá Bán:</label>
            <input type="number" id="giaban" name="giaban" value="<?php echo htmlspecialchars($product['giaban']); ?>">
            <span class="error"><?php echo $giaError; ?></span>
        </div>
        <div>
            <label for="gianhap">Giá Nhập:</label>
            <input type="number" id="gianhap" name="gianhap"
                value="<?php echo htmlspecialchars($product['gianhap']); ?>">
            <span class="error"><?php echo $giaError; ?></span>
        </div>
        <div>
            <label for="soluong">Số Lượng:</label>
            <input type="number" id="soluong" name="soluong"
                value="<?php echo htmlspecialchars($product['soluong']); ?>">
            <span class="error"><?php echo $soLuongError; ?></span>
        </div>
        <div>
            <label for="giamgia">Giảm Giá:</label>
            <input type="number" id="giamgia" name="giamgia"
                value="<?php echo htmlspecialchars($product['giamgia']); ?>">
        </div>
        <div>
            <label for="mieuta">Miêu Tả:</label>
            <textarea id="mieuta" name="mieuta"><?php echo htmlspecialchars($product['mieuta']); ?></textarea>
        </div>
        <div>
            <label>Ảnh:</label>
            <div class="drop-area" id="drop-area">
                Kéo và thả ảnh vào đây hoặc bấm để chọn
            </div>
            <input type="file" id="anh" name="anh[]" multiple style="display: none;">
            <input type="hidden" name="remove_images" id="remove_images">
            <input type="file" name="new_images[]" id="new_images" multiple style="display:none">
            <div id="preview">
                <?php
                   $images = explode(",", $product['anh']);
                     foreach ($images as $image) {
                         if (!empty($image)) {
                             echo '<div class="preview-image-container">';
                             echo '<img src="../../uploads/' . htmlspecialchars($image) . '" width="50" height="50" style="margin-right: 5px;">';
                             echo '<span class="remove-image" data-src="../../uploads/' . htmlspecialchars($image) . '">×</span>';
                             echo '</div>';
                         }
                      }
                  ?>
            </div>
        </div>

        <button type="submit">Cập Nhật</button>
        <button type="button" onclick="window.location.href='danhsachsp.php'">Hủy</button>
    </form>
    <script src="capnhat.js">

    </script>
</body>

</html>
<?php
$conn->close();
?>