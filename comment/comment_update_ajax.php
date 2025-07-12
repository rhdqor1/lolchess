<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "로그인 필요"]);
    exit;
}
include '../db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$comment_id = $data['id'];
$new_content = $data['content'];

$sql = "SELECT USER_ID FROM COMMENTS WHERE COMMENTS_ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $comment_id);
oci_execute($stid);
$row = oci_fetch_assoc($stid);

if (!$row || ($_SESSION['user_id'] !== $row['USER_ID'] && $_SESSION['user_role'] !== 'admin')) {
    echo json_encode(["success" => false, "message" => "권한 없음"]);
    exit;
}

$sql = "UPDATE COMMENTS SET COMMENTS_CONTENT = :content WHERE COMMENTS_ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":content", $new_content);
oci_bind_by_name($stid, ":id", $comment_id);
oci_execute($stid);
oci_commit($conn);

echo json_encode(["success" => true, "content" => $new_content]);
?>
