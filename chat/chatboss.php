<?php
session_start();
include('../db_connect.php');

// Đảm bảo là admin
if (!isset($_SESSION['manguoidung']) || $_SESSION['vaitro'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['manguoidung'];

// Lấy danh sách khách hàng
$query = "SELECT manguoidung, hoten FROM nguoidung WHERE vaitro = 'Khách hàng'";
$result = $conn->query($query);
$khachhang = [];
while ($row = $result->fetch_assoc()) {
    $khachhang[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Chat Admin</title>
  <style>
    body { font-family: Arial; display: flex; }
    #user-list { width: 200px; border-right: 1px solid #ccc; padding: 10px; }
    #chat-box { flex: 1; padding: 10px; }
    #messages { list-style: none; padding: 0; }
    #messages li { padding: 5px; }
  </style>
</head>
<body>

<div id="user-list">
  <h4>Khách hàng</h4>
  <?php foreach ($khachhang as $kh): ?>
    <button class="user-btn" data-id="<?= $kh['manguoidung'] ?>">
      <?= htmlspecialchars($kh['hoten']) ?>
    </button><br>
  <?php endforeach; ?>
</div>

<div id="chat-box">
  <h4>Đang trò chuyện với: <span id="current-user">Chưa chọn</span></h4>
  <ul id="messages"></ul>
  <form id="form" style="display:none">
    <input id="msg" autocomplete="off" placeholder="Nhập tin nhắn..."><button>Gửi</button>
  </form>
</div>

<script src="http://localhost:3000/socket.io/socket.io.js"></script>
<script>
const socket = io("http://localhost:3000");
let currentUserId = null;
const adminId = <?= json_encode($admin_id) ?>;

document.querySelectorAll('.user-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    currentUserId = btn.dataset.id;
    document.getElementById('current-user').textContent = btn.textContent;
    document.getElementById('messages').innerHTML = "";
    document.getElementById('form').style.display = "block";

    fetch("get_messages.php?user_id=" + currentUserId)
    .then(res => res.json())
    .then(data => {
      data.forEach(msg => {
        const li = document.createElement("li");
        li.textContent = (msg.sender_id == adminId ? "Bạn: " : "Khách: ") + msg.message;
        messages.appendChild(li);
      });
    });
  });
});

const form = document.getElementById("form");
const input = document.getElementById("msg");
const messages = document.getElementById("messages");

form.addEventListener("submit", function(e) {
  e.preventDefault();
  if (!currentUserId) return;

  const msg = input.value;
  const data = {
    sender_id: adminId,
    receiver_id: currentUserId,
    message: msg
  };

  // Gửi qua socket
  socket.emit("send_message", data);

  // Gửi lưu vào PHP
  fetch("save_message.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(data),
  });

  input.value = "";
});

// Lắng nghe tin nhắn mới
socket.on("send_message", function(data) {
  if (data.sender_id == currentUserId || data.receiver_id == currentUserId) {
    const li = document.createElement("li");
    li.textContent = (data.sender_id == adminId ? "Bạn: " : "Khách: ") + data.message;
    messages.appendChild(li);
  }
});

</script>

</body>
</html>
