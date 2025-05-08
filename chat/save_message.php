<?php
include("../db_connect.php");

$sender = $_POST['sender_id'] ?? null;
$receiver = $_POST['receiver_id'] ?? null;
$msg = $_POST['message'] ?? null;

header('Content-Type: application/json');

if (!$sender || !$receiver || !$msg) {
    echo json_encode(["status" => "error", "message" => "Thiếu dữ liệu"]);
    exit();
}

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("iis", $sender, $receiver, $msg);
$ok = $stmt->execute();

if ($ok) {
    echo json_encode(["status" => "ok"]);
} else {
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
}
