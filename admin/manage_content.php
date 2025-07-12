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

<link rel="stylesheet" href="../style.css">
<div class="container">
  <h2>📄 게시글 목록</h2>
  <table border="1" cellpadding="8" cellspacing="0" width="100%">
    <tr><th>ID</th><th>제목</th><th>작성자</th><th>작성일</th><th>이동</th><th>삭제</th></tr>
    <?php
    $sql = "SELECT POST_ID, POST_TITLE, POST_USER_ID, POST_CREATED_AT FROM POST ORDER BY POST_CREATED_AT DESC";
    $stid = oci_parse($conn, $sql);
    if (oci_execute($stid)) {
        while ($row = oci_fetch_assoc($stid)) {
            echo "<tr>";
            echo "<td>{$row['POST_ID']}</td>";
            echo "<td>" . htmlspecialchars($row['POST_TITLE']) . "</td>";
            echo "<td>{$row['POST_USER_ID']}</td>";
            echo "<td>{$row['POST_CREATED_AT']}</td>";
            echo "<td><a href='../post/post_view.php?id={$row['POST_ID']}'>이동</a></td>";
            echo "<td><a href='delete_post.php?id={$row['POST_ID']}' onclick=\"return confirm('정말 삭제하시겠습니까?');\">삭제</a></td>";
            echo "</tr>";
        }
    }
    ?>
  </table>

  <h2 style="margin-top:40px;">💬 댓글 목록</h2>
  <table border="1" cellpadding="8" cellspacing="0" width="100%">
    <tr><th>ID</th><th>내용</th><th>작성자</th><th>글 ID</th><th>작성일</th><th>삭제</th></tr>
    <?php
    $sql = "SELECT COMMENTS_ID, COMMENTS_CONTENT, USER_ID, POST_ID, COMMENTS_CREATED_AT FROM COMMENTS ORDER BY COMMENTS_CREATED_AT DESC";
    $stid = oci_parse($conn, $sql);
    if (oci_execute($stid)) {
        while ($row = oci_fetch_assoc($stid)) {
            echo "<tr>";
            echo "<td>{$row['COMMENTS_ID']}</td>";
            echo "<td>" . htmlspecialchars($row['COMMENTS_CONTENT']) . "</td>";
            echo "<td>{$row['USER_ID']}</td>";
            echo "<td>{$row['POST_ID']}</td>";
            echo "<td>{$row['COMMENTS_CREATED_AT']}</td>";
            echo "<td><a href='delete_comment.php?id={$row['COMMENTS_ID']}' onclick=\"return confirm('정말 삭제하시겠습니까?');\">삭제</a></td>";
            echo "</tr>";
        }
    }
    ?>
  </table>
  <a href="dashboard.php">← 관리자 대시보드로</a>
</div>
