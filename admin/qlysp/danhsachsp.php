<?php
include '../../db_connect.php';
// **QUAN TRỌNG:** Include menu *sau khi* xử lý logic POST/GET (như xóa)
// để session và kiểm tra quyền được thực hiện trước khi menu được hiển thị.
// include '../../admin/menu/menu.php'; // Chuyển xuống dưới sau logic xử lý

// --- Phần xử lý PHP (xóa sản phẩm) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    // Bắt đầu session và kiểm tra quyền ở đây nếu chưa làm trong menu.php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['manguoidung']) || (isset($_SESSION['vaitro']) && $_SESSION['vaitro'] == 'Khách hàng')) {
        // Chuyển hướng hoặc báo lỗi nếu không có quyền
        header("Location: ../../admin/index.php");
        exit();
    }

    $id = $_GET['id'];
    $conn->begin_transaction(); // Bắt đầu transaction để đảm bảo toàn vẹn

    try {
        // Lấy danh sách ảnh để xóa file
        $stmt_select = $conn->prepare("SELECT anh FROM sanpham WHERE masanpham = ?");
        if (!$stmt_select) throw new Exception("Lỗi chuẩn bị lấy ảnh: " . $conn->error);
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $result_select = $stmt_select->get_result();
        $anh_data = $result_select->fetch_assoc();
        $stmt_select->close();

        $anh_to_delete = $anh_data ? $anh_data['anh'] : null;

        // Xóa sản phẩm khỏi CSDL
        $stmt_delete = $conn->prepare("DELETE FROM sanpham WHERE masanpham = ?");
        if (!$stmt_delete) throw new Exception("Lỗi chuẩn bị xóa sản phẩm: " . $conn->error);
        $stmt_delete->bind_param("i", $id);
        if (!$stmt_delete->execute()) {
            throw new Exception("Lỗi thực thi xóa sản phẩm: " . $stmt_delete->error);
        }
        $stmt_delete->close();

        // Nếu xóa CSDL thành công, tiến hành xóa file ảnh
        if ($anh_to_delete) {
            $anh_arr = explode(",", $anh_to_delete);
            foreach ($anh_arr as $anh_path_raw) {
                 $anh_path = trim($anh_path_raw); // Loại bỏ khoảng trắng thừa
                if (empty($anh_path)) continue;

                 // Cố gắng xóa đường dẫn tuyệt đối nếu có
                 if (file_exists($anh_path)) {
                    @unlink($anh_path); // Dùng @ để bỏ qua lỗi nếu không xóa được
                 } else {
                     // Nếu không, thử xóa trong thư mục uploads chuẩn
                    $anh_name = basename($anh_path);
                    $upload_path = "../../uploads/" . $anh_name;
                    if (file_exists($upload_path)) {
                        @unlink($upload_path);
                    }
                 }
            }
        }

        $conn->commit(); // Hoàn tất transaction
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); // Chuyển hướng về trang gốc, loại bỏ query string cũ
        exit;

    } catch (Exception $e) {
        $conn->rollback(); // Hoàn tác nếu có lỗi
        // Hiển thị lỗi hoặc ghi log
        echo "Lỗi xóa sản phẩm: " . $e->getMessage();
        // Có thể thêm một đoạn dừng nhỏ để người dùng đọc lỗi
        // sleep(3);
        // header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        // exit;
    }
}

// --- Include menu SAU KHI xử lý logic GET/POST ---
include '../../admin/menu/menu.php';

// Truy vấn danh sách sản phẩm (sau khi đã xử lý xóa nếu có)
$sql = "SELECT sp.*, nsx.tenhang
        FROM sanpham sp
        LEFT JOIN nhasanxuat nsx ON sp.masx = nsx.masx
        ORDER BY sp.masanpham DESC"; // Sắp xếp theo ID giảm dần chẳng hạn
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Link tới CSS -->
    <link rel="stylesheet" href="dssp.css?v=<?php echo time(); ?>">
    <title>Danh Sách Sản Phẩm</title>
</head>

<body>
    <div class="main-content">
        <!-- Container cho nội dung chính -->
        <h2>Danh Sách Sản Phẩm</h2>

        <div class="action-bar">
            <!-- Container cho nút Thêm -->
            <button class="btn btn-add" onclick="window.location.href='themsanpham.php'">
                + Thêm Sản Phẩm
                <!-- Sử dụng mã HTML cho dấu cộng -->
            </button>
        </div>

        <div class="table-responsive">
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ảnh</th>
                        <th>Tên Sản Phẩm</th>
                        <th>Loại</th>
                        <th>Tình trạng</th>
                        <th>Hãng</th>
                        <th>SL</th>
                        <th>Giá Bán</th>
                        <th>Giá Nhập</th>
                        <th>Giảm Giá</th>
                        <th>Miêu Tả</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $description = htmlspecialchars($row['mieuta'] ?? ''); // Xử lý nếu mieuta là NULL
                            $truncatedDescription = mb_strlen($description) > 100 ? mb_substr($description, 0, 100) . '...' : $description; // Dùng mb_ để xử lý UTF-8

                            echo "<tr>";
                            echo "<td data-label='ID'>" . htmlspecialchars($row['masanpham']) . "</td>";

                            // --- Image Cell ---
                            echo "<td data-label='Ảnh' class='image-cell' data-images='" . htmlspecialchars($row['anh'] ?? '') . "'>";
                            $images = $row['anh'] ? explode(",", $row['anh']) : [];
                            $firstImage = isset($images[0]) ? trim($images[0]) : '';
                            $imagePath = '';
                            if (!empty($firstImage)) {
                                if (file_exists($firstImage)) {
                                    $imagePath = htmlspecialchars($firstImage);
                                } else {
                                    $imgName = basename($firstImage);
                                    if (file_exists("../../uploads/" . $imgName)) {
                                        $imagePath = htmlspecialchars("../../uploads/" . $imgName);
                                    }
                                }
                            }
                             // Ảnh mặc định nếu không có ảnh hoặc không tìm thấy
                            $displayImage = $imagePath ?: '../../uploads/placeholder.png'; // Thay 'placeholder.png' bằng tên ảnh mặc định của bạn

                            echo "<div class='image-container'>";
                            echo "<img src='" . $displayImage . "' alt='Ảnh sản phẩm' data-index='0' class='product-thumbnail'>";
                            if (count($images) > 1) {
                                echo "<div class='image-nav'>";
                                echo "<button type='button' class='prev-image'><</button>"; // Dùng mã HTML
                                echo "<button type='button' class='next-image'>></button>"; // Dùng mã HTML
                                echo "</div>";
                            }
                            echo "</div>";
                            echo "</td>";
                            // --- End Image Cell ---

                            echo "<td data-label='Tên SP'>" . htmlspecialchars($row['tensanpham']) . "</td>";
                            echo "<td data-label='Loại'>" . htmlspecialchars($row['loaisanpham'] ?? 'N/A') . "</td>";
                            echo "<td data-label='Tình trạng'>" . htmlspecialchars($row['tinhtrang'] ?? 'N/A') . "</td>";
                            echo "<td data-label='Hãng'>" . htmlspecialchars($row['tenhang'] ?? 'N/A') . "</td>";
                            echo "<td data-label='SL'>" . htmlspecialchars($row['soluong']) . "</td>";
                            echo "<td data-label='Giá Bán' class='price'>" . number_format($row['giaban'], 0, ',', '.') . "đ</td>";
                            echo "<td data-label='Giá Nhập' class='price'>" . number_format($row['gianhap'], 0, ',', '.') . "đ</td>";
                            echo "<td data-label='Giảm Giá' class='price'>" . number_format($row['giamgia'] ?? 0, 0, ',', '.') . "đ</td>"; // Xử lý nếu giamgia là NULL
                            echo "<td data-label='Miêu Tả' class='description-cell' title='" . $description . "'>" . $truncatedDescription . "</td>";

                            // --- Action Cell ---
                            echo "<td data-label='Hành Động' class='action-cell'>";
                            echo "<a href='capnhat.php?id=" . $row['masanpham'] . "' class='btn btn-update' title='Sửa sản phẩm'>Sửa</a>";
                            echo "<a href='?action=delete&id=" . $row['masanpham'] . "' class='btn btn-delete' title='Xóa sản phẩm' onclick='return confirm(\"Bạn có chắc chắn muốn xóa sản phẩm này không? Việc này không thể hoàn tác.\")'>Xóa</a>";
                            echo "</td>";
                            // --- End Action Cell ---

                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='12' class='no-products'>Không có sản phẩm nào trong danh sách.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div> <!-- End .table-responsive -->
    </div> <!-- End .main-content -->

    <!-- Image Popup -->
    <div class="image-popup" id="imagePopup">
        <div class="image-popup-content">
            <span class="close-popup" id="closePopup">×</span> <!-- Dùng mã HTML cho dấu X -->
            <img src="" alt="Ảnh sản phẩm lớn" id="popupImage">
        </div>
    </div>

    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableResponsive = document.querySelector('.table-responsive'); // Target the container
        const imagePopup = document.getElementById('imagePopup');
        const popupImage = document.getElementById('popupImage');
        const closePopup = document.getElementById('closePopup');

        if (tableResponsive) { // Check if the element exists
            tableResponsive.addEventListener('click', function(event) {
                const target = event.target;
                const imageContainer = target.closest('.image-container');
                const imageCell = target.closest('.image-cell'); // Find the parent cell

                if (!imageCell) return; // Exit if click wasn't in an image cell

                const imagesData = imageCell.dataset.images;
                const images = imagesData ? imagesData.split(',').map(img => img.trim()).filter(img =>
                    img !== '') : []; // Trim and filter empty strings

                if (target.classList.contains('product-thumbnail') && images.length > 0) {
                    // Click on the thumbnail itself to open popup
                    popupImage.src = target.src; // Use the current thumbnail src for popup
                    imagePopup.style.display = 'flex';
                } else if (imageContainer && images.length > 1) {
                    // Handle navigation clicks only if there's a container and more than 1 image
                    const currentImageElement = imageContainer.querySelector('.product-thumbnail');
                    let currentIndex = parseInt(currentImageElement.dataset.index, 10);

                    if (target.classList.contains('prev-image')) {
                        event.stopPropagation(); // Prevent popup from opening when clicking nav
                        currentIndex = (currentIndex - 1 + images.length) % images.length;
                        updateThumbnail(currentImageElement, images, currentIndex);
                    } else if (target.classList.contains('next-image')) {
                        event.stopPropagation(); // Prevent popup from opening when clicking nav
                        currentIndex = (currentIndex + 1) % images.length;
                        updateThumbnail(currentImageElement, images, currentIndex);
                    }
                }
            });
        }

        if (closePopup) {
            closePopup.addEventListener('click', function() {
                imagePopup.style.display = 'none';
                popupImage.src = ''; // Clear src when closing
            });
        }

        // Close popup if clicked outside the image content
        if (imagePopup) {
            imagePopup.addEventListener('click', function(event) {
                if (event.target === imagePopup) { // Check if the click is on the backdrop
                    imagePopup.style.display = 'none';
                    popupImage.src = '';
                }
            });
        }

        function updateThumbnail(imageElement, images, index) {
            const potentialSrc = images[index];
            // Basic check if path likely exists (more robust check needed on server)
            // We can't reliably check file existence purely via JS due to security restrictions
            // Let's try to construct the path relative to uploads
            let displaySrc = '../../uploads/placeholder.png'; // Default
            if (potentialSrc) {
                // Assume it might be a full path or just filename
                if (potentialSrc.includes('/')) { // Likely contains path info
                    // Cannot reliably check absolute/different paths from browser
                    // Assume it might be relative to root or similar - difficult!
                    // BEST APPROACH: Store only filenames in DB, prepend '../../uploads/'
                    const filename = potentialSrc.split('/').pop();
                    displaySrc = '../../uploads/' + filename; // Construct likely path
                } else { // Assume it's just the filename
                    displaySrc = '../../uploads/' + potentialSrc;
                }
                // Note: This JS cannot guarantee the file exists at displaySrc.
                // The HTML rendering part should handle missing files better.
                imageElement.src = displaySrc; // Attempt to load
            } else {
                imageElement.src = '../../uploads/placeholder.png'; // Fallback
            }

            // Fallback image if the attempted load fails
            imageElement.onerror = function() {
                this.src = '../../uploads/placeholder.png';
                this.onerror = null; // Prevent infinite loop if placeholder is missing
            };

            imageElement.dataset.index = index; // Update the index attribute
        }
    });
    </script>
</body>

</html>

<?php
if (isset($conn)) { // Chỉ đóng nếu biến $conn tồn tại
    $conn->close();
}
?>