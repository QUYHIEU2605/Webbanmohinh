<?php
session_start();
include '../../db_connect.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['manguoidung'])) {
    header("Location: ../../customer/user/login_customer.php");
    exit();
}

// Lấy thông tin sản phẩm từ form
$masanpham = isset($_POST['masanpham']) ? (int)$_POST['masanpham'] : 0;
$manguoidung = $_SESSION['manguoidung'];

if ($masanpham > 0) {
    // Kiểm tra xem sản phẩm đã có trong giỏ hàng của người dùng chưa
    $sql = "SELECT id, soluong FROM giohang WHERE masanpham = ? AND manguoidung = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $masanpham, $manguoidung);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Nếu sản phẩm đã có trong giỏ hàng, tăng số lượng lên 1
        $row = $result->fetch_assoc();
        $giohang_id = $row['id'];
        $soluong = $row['soluong'] + 1;
        
        $sql = "UPDATE giohang SET soluong = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $soluong, $giohang_id);
       
    } else {
        // Nếu sản phẩm chưa có trong giỏ hàng, thêm mới vào giỏ hàng
        $sql = "INSERT INTO giohang (masanpham, soluong, manguoidung) VALUES (?, ?, ?)";
        $soluong = 1;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $masanpham, $soluong, $manguoidung);
    }

    if ($stmt->execute()) {
        header("Location: ../../customer/dathang/cart.php"); // Chuyển hướng về trang giỏ hàng
        exit();
    } else {
        echo "Lỗi thêm/cập nhật giỏ hàng: " . $stmt->error;
    }
} else {
    echo "Không có ID sản phẩm.";
}

$conn->close();
?>