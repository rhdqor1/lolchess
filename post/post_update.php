<?php
$image_path = null;
session_start();
if (!isset($_SESSION['user_id'])) {
    die("❌ 로그인 후 이용 가능합니다.");
}

require_once '../db_connect.php';

$id = $_POST['id'] ?? '';
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$is_notice = ($_POST['notice'] ?? '') === 'Y' ? 'Y' : 'N';

if (!$id || !$title || !$content) {
    die("❌ 모든 필드를 입력해주세요.");
}

// 기존 이미지 경로 가져오기
$sql_old = "SELECT POST_IMAGE FROM POST WHERE POST_ID = :id";
$stid_old = oci_parse($conn, $sql_old);
oci_bind_by_name($stid_old, ":id", $id);
oci_execute($stid_old);
$row_old = oci_fetch_assoc($stid_old);
$old_image = $row_old['POST_IMAGE'] ?? null;

// 새 이미지 업로드 처리
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
        $image_path = $target_path;

        // 절대 경로 기반 기존 이미지 삭제
        if ($old_image) {
            $base_path = realpath(__DIR__); // 프로젝트 루트
            $full_old_path = $base_path . "/" . $old_image;
            if (file_exists($full_old_path)) {
                unlink($full_old_path);
            }
        }
    }
}

// 업데이트 쿼리 구성
if ($image_path !== null) {
    $sql = "UPDATE POST SET POST_TITLE = :title, POST_CONTENT = :content, IS_NOTICE = :is_notice, POST_IMAGE = :image, POST_UPDATED_AT = SYSDATE WHERE POST_ID = :id";
} else {
    $sql = "UPDATE POST SET POST_TITLE = :title, POST_CONTENT = :content, IS_NOTICE = :is_notice, POST_UPDATED_AT = SYSDATE WHERE POST_ID = :id";
}

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":title", $title);
oci_bind_by_name($stid, ":content", $content);
oci_bind_by_name($stid, ":is_notice", $is_notice);
oci_bind_by_name($stid, ":id", $id);
if ($image_path !== null) {
    oci_bind_by_name($stid, ":image", $image_path);
}

if (!oci_execute($stid)) {
    $e = oci_error($stid);
    die("❌ 글 수정 실패: " . $e['message']);
}

oci_commit($conn);
header("Location: post_view.php?id=" . urlencode($id));
exit;
