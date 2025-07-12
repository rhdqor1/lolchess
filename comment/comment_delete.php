<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("❌ 로그인 후 이용 가능합니다.");
}
include '../db_connect.php';
$id = $_GET['id'];
$post_id = $_GET['post_id'];

$sql2 = "DELETE FROM COMMENT_LIKE WHERE COMMENTS_ID = :id";
$stid2 = oci_parse($conn, $sql2);
oci_bind_by_name($stid2, ":id", $id);
oci_execute($stid2);

$sql = "DELETE FROM COMMENTS WHERE COMMENTS_ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $id);
oci_execute($stid);
oci_commit($conn);

header("Location: ../post/post_view.php?id=$post_id");
exit;
?>
