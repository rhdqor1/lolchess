<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("❌ 로그인 후 이용 가능합니다.");
}
include '../db_connect.php';

$post_id = $_POST['post_id'];
$content = $_POST['content'];
$user_id = $_SESSION['user_id'];

$sql = "INSERT INTO COMMENTS (COMMENTS_ID, COMMENTS_CONTENT, USER_ID, POST_ID, COMMENTS_CREATED_AT)
        VALUES (COMMENTS_SEQ.NEXTVAL, :content, :user_id, :post_id, SYSDATE)";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":content", $content);
oci_bind_by_name($stid, ":user_id", $user_id);
oci_bind_by_name($stid, ":post_id", $post_id);

if (!oci_execute($stid)) {
    $e = oci_error($stid);
    die("❌ 댓글 작성 실패: " . $e['message']);
}

oci_commit($conn);
header("Location: ../post/post_view.php?id=$post_id&from=comment_write");
exit;
?>
