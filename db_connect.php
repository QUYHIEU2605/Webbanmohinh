<?php
$servername = "localhost"; // Địa chỉ máy chủ
$username = "u335507035_qlybanhang";        // Tên người dùng
$password = "Ditconme276";            // Mật khẩu
$dbname = "u335507035_qlybanhang";       // Tên cơ sở dữ liệu

// Kết nối cơ sở dữ liệu
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>
