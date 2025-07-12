<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

ob_start();

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "로그인 필요"]);
  exit;
}
include '../db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$post_id = $data['post_id'] ?? null;
$content = $data['content'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$post_id || !$content) {
  echo json_encode(["success" => false, "message" => "필수 데이터 누락"]);
  exit;
}

// 시퀀스로부터 ID 가져오기
$sql = "SELECT COMMENTS_SEQ.NEXTVAL AS NEW_ID FROM DUAL";
$stid = oci_parse($conn, $sql);
if (!oci_execute($stid)) {
  $e = oci_error($stid);
  echo json_encode(["success" => false, "message" => "시퀀스 오류: " . $e['message']]);
  exit;
}
$row = oci_fetch_assoc($stid);
$new_id = $row['NEW_ID'];

$sql = "INSERT INTO COMMENTS (COMMENTS_ID, POST_ID, USER_ID, COMMENTS_CONTENT, COMMENTS_CREATED_AT)
        VALUES (:id, :post_id, :user_id, :content, SYSDATE)";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $new_id);
oci_bind_by_name($stid, ":post_id", $post_id);
oci_bind_by_name($stid, ":user_id", $user_id);
oci_bind_by_name($stid, ":content", $content);

if (!oci_execute($stid)) {
  $e = oci_error($stid);
  echo json_encode(["success" => false, "message" => "INSERT 실패: " . $e['message']]);
  exit;
}

oci_commit($conn);
ob_end_clean();

echo json_encode([
  "success" => true,
  "user" => $user_id,
  "content" => $content,
  "id" => $new_id
]);
?>
