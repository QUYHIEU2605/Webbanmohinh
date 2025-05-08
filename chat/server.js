const express = require("express");
const socketio = require("socket.io");
const http = require("http");
const mysql = require("mysql2");
const cors = require("cors");

const app = express();
const server = http.createServer(app);

// Cho phép request từ client (http://localhost)
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Kết nối cơ sở dữ liệu
const db = mysql.createConnection({
  host: "localhost",
  user: "root",
  password: "", // nếu bạn có mật khẩu thì thêm vào đây
  database: "qlybanhang"
});

db.connect((err) => {
  if (err) {
    console.error("❌ Lỗi kết nối MySQL:", err);
  } else {
    console.log("✅ Kết nối MySQL thành công!");
  }
});

// Cấu hình socket.io với CORS
const io = socketio(server, {
  cors: {
    origin: "http://localhost",
    methods: ["GET", "POST"]
  }
});

// Khi client kết nối tới socket
io.on("connection", (socket) => {
  console.log("🔌 Một người dùng đã kết nối");

  // Lắng nghe khi có tin nhắn gửi
  socket.on("send_message", (data) => {
    const { sender_id, receiver_id, message } = data;

    // Lưu tin nhắn vào database
    db.query(
      "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)",
      [sender_id, receiver_id, message],
      (err, result) => {
        if (err) {
          console.error("❌ Lỗi khi lưu tin nhắn:", err);
          return;
        }

        console.log("💬 Tin nhắn đã được lưu vào database");

        // Gửi tin nhắn lại cho tất cả client (hoặc bạn có thể chỉ gửi cho người nhận)
        io.emit("receive_message", data);
      }
    );
  });

  socket.on("disconnect", () => {
    console.log("⚡ Người dùng đã ngắt kết nối");
  });
});

// Khởi chạy server
server.listen(3000, () => {
  console.log("🚀 Server đang chạy tại http://localhost:3000");
});
