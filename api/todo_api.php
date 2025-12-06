<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user'])) { http_response_code(401); echo json_encode(['error'=>'Unauth']); exit; }
$userId = (int)$_SESSION['user']['id'];

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD']==='POST' && $action==='') {
  // create
  $title = trim($_POST['title'] ?? '');
  $deadline = $_POST['deadline'] ?? null;
  $status = $_POST['status'] ?? 'Chưa làm';
  if ($title==='') { echo json_encode(['ok'=>false]); exit; }
  $stmt=$conn->prepare("INSERT INTO todos(user_id,title,status,deadline) VALUES (?,?,?,?)");
  $stmt->bind_param('isss', $userId, $title, $status, $deadline);
  $stmt->execute(); echo json_encode(['ok'=>true]); exit;
}

if ($action==='list') {
  $res = $conn->query("SELECT id,title,status,deadline FROM todos WHERE user_id=$userId ORDER BY id DESC");
  $rows=[]; while($r=$res->fetch_assoc()) $rows[]=$r; echo json_encode($rows); exit;
}

if ($action==='update') {
  $id = (int)($_GET['id'] ?? 0);
  $status = $_GET['status'] ?? 'Đang làm';
  $conn->query("UPDATE todos SET status='".$conn->real_escape_string($status)."' WHERE id=$id AND user_id=$userId");
  echo json_encode(['ok'=>true]); exit;
}

if ($action==='delete') {
  $id = (int)($_GET['id'] ?? 0);
  $conn->query("DELETE FROM todos WHERE id=$id AND user_id=$userId");
  echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['error'=>'invalid']);
