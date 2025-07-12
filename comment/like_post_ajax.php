<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "로그인 필요"]);
  exit;
}
include '../db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$post_id = $data['post_id'];
$user_id = $_SESSION['user_id'];

$sql = "SELECT COUNT(*) AS CNT FROM POST_LIKE WHERE POST_ID = :post_id AND USER_ID = :user_id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":post_id", $post_id);
oci_bind_by_name($stid, ":user_id", $user_id);
oci_execute($stid);
$row = oci_fetch_assoc($stid);
if ($row['CNT'] > 0) {
  echo json_encode(["success" => false, "message" => "이미 추천함"]);
  exit;
}

$sql = "INSERT INTO POST_LIKE (POST_ID, USER_ID) VALUES (:post_id, :user_id)";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":post_id", $post_id);
oci_bind_by_name($stid, ":user_id", $user_id);
oci_execute($stid);
oci_commit($conn);

$sql = "SELECT COUNT(*) AS CNT FROM POST_LIKE WHERE POST_ID = :post_id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":post_id", $post_id);
oci_execute($stid);
$row = oci_fetch_assoc($stid);

echo json_encode(["success" => true, "count" => $row['CNT']]);
?>
