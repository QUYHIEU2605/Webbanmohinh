<?php
include '../db_connect.php';
include '../customer/menu/menu_customer.php';
// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['manguoidung'])) {
    header("Location: ../customer/user/login_customer.php");
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
$sql = "SELECT * FROM trangchu WHERE id = ?";
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

$anh = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Xử lý ảnh
    $targetDir = "../uploads/";
    $anh_arr = explode(",", $product['duongdan']);
    $anhPaths = [];
    
     // Xử lý ảnh đã xóa
      if (isset($_POST['remove_images']) && !empty($_POST['remove_images'])) {
        $removeImages = explode(",", $_POST['remove_images']);
        foreach ($removeImages as $removeImage) {
           $removeImageName = basename($removeImage);
            // Xóa ảnh khỏi hệ thống tệp
            if(file_exists("../uploads/" .$removeImageName)){
                unlink("../uploads/" . $removeImageName);
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
     $sql = "UPDATE trangchu SET duongdan=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $anh, $id);
    if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
            exit();
        } else {
            echo "Lỗi cập nhật sản phẩm: " . $stmt->error;
        }
        $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="capnhat.css?v=<?php echo time(); ?>">
    <title>Chỉnh sửa ảnh</title>

</head>

<body>
    <div class=edit-page>
        <h2>Chỉnh sửa ảnh </h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" method="post"
            enctype="multipart/form-data">

            <div>
                <label>Ảnh:</label>
                <div class="drop-area" id="drop-area">
                    Kéo và thả ảnh vào đây hoặc bấm để chọn
                </div>
                <input type="file" id="duongdan" name="duongdan[]" multiple style="display: none;">
                <input type="hidden" name="remove_images" id="remove_images">
                <input type="file" name="new_images[]" id="new_images" multiple style="display:none">
                <div id="preview">
                    <?php
                   $images = explode(",", $product['duongdan']);
                     foreach ($images as $image) {
                         if (!empty($image)) {
                             echo '<div class="preview-image-container">';
                             echo '<img src="../uploads/' . htmlspecialchars($image) . '">';
                             echo '<span class="remove-image" data-src="../uploads/' . htmlspecialchars($image) . '">×</span>';
                             echo '</div>';
                         }
                      }
                  ?>
                </div>
            </div>

            <button type="submit">Cập Nhật</button>
            <button type="button" onclick="window.location.href='../index.php'">Hủy</button>
        </form>
    </div>
    <script src="chinhsua.js">

    </script>
</body>

</html>
<?php
$conn->close();
?>