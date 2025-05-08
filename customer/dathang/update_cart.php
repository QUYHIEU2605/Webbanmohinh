<?php
session_start();
include '../../db_connect.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['manguoidung'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập.']);
    exit();
}

$manguoidung = $_SESSION['manguoidung'];
$masanpham = isset($_POST['masanpham']) ? (int)$_POST['masanpham'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($masanpham <= 0 || !in_array($action, ['plus', 'minus', 'remove'])) {
    echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ.']);
    exit();
}
// Truy vấn số lượng sản phẩm có sẵn trong kho
$sql = "SELECT soluong FROM sanpham WHERE masanpham = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $masanpham);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
    exit();
}
$product = $result->fetch_assoc();
$stock = $product['soluong'];
if ($action == 'plus') {
    if ($quantity > $stock) {
         echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ.']);
         exit();
     }
}
if ($action == 'remove') {
     $sql = "DELETE FROM giohang WHERE masanpham = ? AND manguoidung = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $masanpham, $manguoidung);
         if ($stmt->execute()) {
               echo json_encode(['success' => true]);
                exit;
            }
 }
$sql = "UPDATE giohang SET soluong = ? WHERE masanpham = ? AND manguoidung = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $quantity, $masanpham, $manguoidung);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'soluong' => $quantity]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật giỏ hàng: ' . $stmt->error]);
}

$conn->close();
?>