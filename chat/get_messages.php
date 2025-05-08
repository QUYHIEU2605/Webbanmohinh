<?php
include('../db_connect.php');
session_start();

$admin_id = $_SESSION['manguoidung'];
$user_id = $_GET['user_id'] ?? 0;

$stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY id ASC
");
$stmt->bind_param("iiii", $admin_id, $user_id, $user_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);
