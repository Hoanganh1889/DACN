<?php
// config/db.php
$host = '127.0.0.1';  // hoặc 'localhost'
$user = 'root';
$pass = '';
$db   = 'ql_chat_todo';

$conn = new mysqli("localhost", "root", "", "ql_chat_todo");

if ($conn->connect_error) {
  die('Kết nối DB thất bại: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
