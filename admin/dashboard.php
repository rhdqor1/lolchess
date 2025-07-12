<?php
session_start();
include '../db_connect.php';

$sql = "SELECT USER_ROLE FROM USERS WHERE USER_ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $_SESSION['user_id']);
oci_execute($stid);
$row = oci_fetch_assoc($stid);

if (!$row || $row['USER_ROLE'] !== 'admin') {
    session_destroy();
    die("❌ 관리자 권한이 없습니다. 다시 로그인해주세요.");
}
?>
<?php include 'admin_header.php'; ?>

<link rel="stylesheet" href="../style.css">
<div class="container">
  <h2>🔧 관리자 대시보드</h2>
  <ul>
    <li><a href="manage_users.php">회원 관리</a></li>
    <li><a href="manage_content.php">공략/댓글 관리</a></li>
    <li><a href="admin_meta.php">메타 관리</a></li>
    <li><a href="admin_videos.php">Youtube 관리</a></li>
    <li><a href="manage_augment.php">증강체 관리</a></li>
    <li><a href="manage_synergy.php">시너지 관리</a></li>
    <li><a href="admin_item.php">아이템 관리</a></li>
    <li><a href="admin_champ.php">챔피언 관리</a></li>
    <li><a href="../index.php">메인으로</a></li>
  </ul>
</div>
