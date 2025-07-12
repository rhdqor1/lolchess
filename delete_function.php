<?php
function delete_fuc($testid) {
global $conn;
$sql3 = "DELETE FROM COMMENT_LIKE WHERE COMMENTS_ID IN (SELECT COMMENTS_ID FROM COMMENTS WHERE POST_ID = :id)";
$stid3 = oci_parse($conn, $sql3);
oci_bind_by_name($stid3, ":id", $testid);
oci_execute($stid3);

$sql4 = "DELETE FROM POST_LIKE WHERE POST_ID = :id";
$stid4 = oci_parse($conn, $sql4);
oci_bind_by_name($stid4, ":id", $testid);
oci_execute($stid4);

$sql5 = "SELECT POST_IMAGE FROM POST WHERE POST_ID = :id";
$stid5 = oci_parse($conn, $sql5);
oci_bind_by_name($stid5, ":id", $testid);
oci_execute($stid5);
$post = oci_fetch_assoc($stid5);

// 2. 이미지 삭제
if (!empty($post['POST_IMAGE'])) {
    $base_path = realpath(__DIR__); // 프로젝트 최상위 경로
    $image_path = $base_path . "/post/" . $post['POST_IMAGE'];
    if (file_exists($image_path)) {
        unlink($image_path);  // 파일 삭제
    }
}

$sql1 = "DELETE FROM COMMENTS WHERE POST_ID = :id";
$sql2 = "DELETE FROM POST WHERE POST_ID = :id";

$stid1 = oci_parse($conn, $sql1);
$stid2 = oci_parse($conn, $sql2);
oci_bind_by_name($stid1, ":id", $testid);
oci_bind_by_name($stid2, ":id", $testid);
oci_execute($stid1);
oci_execute($stid2);
oci_commit($conn);
}
?>

<?php
function delete_fuc2($testid2) {
global $conn;
$sql3 = "DELETE FROM COMMENT_LIKE WHERE COMMENTS_ID = :id";
$stid3 = oci_parse($conn, $sql3);
oci_bind_by_name($stid3, ":id", $testid2);
oci_execute($stid3);
$sql1 = "DELETE FROM COMMENTS WHERE COMMENTS_ID = :id";
$stid1 = oci_parse($conn, $sql1);
oci_bind_by_name($stid1, ":id", $testid2);
oci_execute($stid1);
oci_commit($conn);
}
?>