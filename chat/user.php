<?php
include("../db_connect.php");
session_start();

$sender_id = $_SESSION['manguoidung'];
$admin_id = 3; // Giả sử admin có ID là 3

// Lấy lịch sử tin nhắn giữa user và admin
$sql = "SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY timestamp ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $sender_id, $admin_id, $admin_id, $sender_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
?>

<div id="chat-box" style="border:1px solid #ccc; height:300px; overflow:auto; padding: 10px;">
  <?php foreach ($messages as $msg): ?>
    <div>
      <b><?= $msg['sender_id'] == $sender_id ? "Bạn" : "Admin" ?>:</b>
      <?= htmlspecialchars($msg['message']) ?>
    </div>
  <?php endforeach; ?>
</div>

<input type="text" id="msg" placeholder="Nhập tin nhắn...">
<button onclick="send()">Gửi</button>

<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script>
  const socket = io("http://localhost:3000");
  const sender_id = <?= $sender_id ?>;
  const receiver_id = <?= $admin_id ?>;

  socket.emit("user_connected", sender_id);

  socket.on("receive_message", data => {
    if (
      (data.sender_id == sender_id && data.receiver_id == receiver_id) ||
      (data.sender_id == receiver_id && data.receiver_id == sender_id)
    ) {
      const box = document.getElementById("chat-box");
      box.innerHTML += `<div><b>${data.sender_id === sender_id ? "Bạn" : "Admin"}:</b> ${data.message}</div>`;
      box.scrollTop = box.scrollHeight;
    }
  });

  function send() {
    const msgInput = document.getElementById("msg");
    const msg = msgInput.value.trim();
    if (!msg) return;

    socket.emit("send_message", {
      sender_id,
      receiver_id,
      message: msg
    });

    const box = document.getElementById("chat-box");
    box.innerHTML += `<div><b>Bạn:</b> ${msg}</div>`;
    box.scrollTop = box.scrollHeight;
    msgInput.value = "";
  }
</script>
