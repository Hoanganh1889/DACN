<?php
// API rất đơn giản: list + create cho phòng chat chung (receiver_id = NULL)
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

if (!isset($_SESSION['user'])) { http_response_code(401); echo json_encode(['error'=>'Unauth']); exit; }
$userId = (int)$_SESSION['user']['id'];

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $json = json_decode(file_get_contents('php://input'), true);
  $msg = trim($json['message'] ?? '');
  if ($msg === '') { echo json_encode(['ok'=>false]); exit; }
  $stmt=$conn->prepare("INSERT INTO messages(sender_id, receiver_id, message) VALUES (?, NULL, ?)");
  $stmt->bind_param('is', $userId, $msg); $stmt->execute(); echo json_encode(['ok'=>true]); exit;
}

if ($action === 'list') {
  $sql = "SELECT m.id, u.username, m.message, m.created_at
          FROM messages m JOIN users u ON m.sender_id=u.id
          WHERE m.receiver_id IS NULL ORDER BY m.id DESC LIMIT 50";
  $res = $conn->query($sql);
  $rows = [];
  while($r=$res->fetch_assoc()) $rows[] = $r;
  echo json_encode(array_reverse($rows)); exit;
}

echo json_encode(['error'=>'invalid']);
