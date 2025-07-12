<?php
$image_path = null;

if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $tmp_name = $_FILES['post_image']['tmp_name'];
    $original_name = basename($_FILES['post_image']['name']);
    $unique_name = uniqid() . "_" . $original_name;
    $target_path = $upload_dir . $unique_name;

    if (move_uploaded_file($tmp_name, $target_path)) {
        $image_path = $target_path;  // DB에 저장할 경로
    }
}
session_start();
if (!isset($_SESSION['user_id'])) {
    die("❌ 로그인 후 이용 가능합니다.");
}
?>

<?php
include '../db_connect.php';
$title = $_POST['title'];
$content = $_POST['content'];
$author = $_SESSION['user_id'];

$is_notice = ($_POST['notice'] ?? '') === 'Y' ? 'Y' : 'N';

$view_count = 0;

$sql = "INSERT INTO POST (POST_ID, POST_TITLE, POST_CONTENT, POST_USER_ID, POST_CREATED_AT, IS_NOTICE, POST_VIEW_COUNT, POST_IMAGE) 
        VALUES (POST_SEQ.NEXTVAL, :title, :content, :user_id, SYSDATE, :is_notice, :view_count, :image)";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":title", $title);
oci_bind_by_name($stid, ":content", $content);
oci_bind_by_name($stid, ":user_id", $author);
oci_bind_by_name($stid, ":is_notice", $is_notice);
oci_bind_by_name($stid, ":view_count", $view_count);
oci_bind_by_name($stid, ":image", $image_path);

if (!oci_execute($stid)) {
    $e = oci_error($stid);
    die("❌ 글 작성 실패: " . $e['message']);
}

oci_commit($conn);
header("Location: ../index.php");
exit;
?>
