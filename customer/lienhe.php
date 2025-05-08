<?php
session_start();
include '../db_connect.php';
include '../customer/menu/menu_customer.php';
$loggedIn = isset($_SESSION['manguoidung']);
$user_id = $loggedIn ? $_SESSION['manguoidung'] : null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $message = $_POST['message'];

    if ($loggedIn) {
        $stmt = $conn->prepare("INSERT INTO lienhe (manguoidung, email, loinhan) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $email, $message);
        if ($stmt->execute()) {
            echo "<script>alert('Gửi thành công!');</script>";
        } else {
            echo "<script>alert('Lỗi khi gửi!');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Bạn cần đăng nhập để gửi tin nhắn!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>

<body>


    <div id="contact-page" class="lienhe-container">
        <h2 id="contact-title">Liên hệ</h2>

        <div id="contact-map" class="lienhe-map">
            <div class="map-item">
                <img src="../uploads/Screenshot 2025-03-18 195955.png" alt="Bản đồ vị trí">
                <p class="map-info"><b>Trụ sở chính:</b> Số 6 Ngách 77/70/1 Ngọc Trục, Đại Mỗ, Nam Từ Liêm, Hà Nội</p>
            </div>
            <div class="map-item">
                <img src="../uploads/Screenshot 2025-03-18 202631.png" alt="Bản đồ vị trí">
                <p class="map-info"><b>Trụ sở hai:</b> 7 Ngõ Số 2, Thanh Xuân, Sóc Sơn, Hà Nội, Việt Nam</p>
            </div>
        </div>

        <div id="contact-info" class="lienhe-info">
            <p><b>Email:</b> mfigure.vn@gmail.com</p>
            <p><b>Hotline:</b> 090.345.2816</p>
        </div>

        <h3 id="chat-title">Chat với Admin</h3>

        <div id="chat-container" class="chat-container">
            <div id="chat-box" class="chat-box">
                <!-- Tin nhắn sẽ load ở đây -->
            </div>
            <input type="text" id="chat-message" class="chat-input" placeholder="Nhập tin nhắn...">
            <button id="send-btn" onclick="sendMessage()">Gửi</button>
        </div>
    </div>



    <script>
    function loadMessages() {
        $.get("api/load_chat.php", function(data) {
            $("#chatBox").html(data);
        });
    }

    function sendMessage() {
        let msg = $("#message").val();
        if (msg.trim() === "") return;

        $.post("api/send_chat.php", {
            message: msg
        }, function(response) {
            if (response.includes("success")) {
                $("#message").val("");
                loadMessages();
            } else {
                alert(response);
            }
        });
    }
    </script>
    </div>
</body>

</html>