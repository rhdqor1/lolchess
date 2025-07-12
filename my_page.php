<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("❌ 로그인 후 이용해주세요.");
}
include 'db_connect.php';

$user_id = $_SESSION['user_id'];
?>

<link rel="stylesheet" href="style.css">
<div class="container">
 <h2>👤 마이페이지</h2>
<h3>📝 내가 작성한 글</h3>
<table border="1" cellpadding="8" width="100%">
  <tr><th>번호</th><th>제목</th><th>작성일</th></tr>
  <?php
  $sql = "SELECT POST_ID, POST_TITLE, POST_CREATED_AT 
          FROM POST
          WHERE POST_USER_ID = :user_id 
          ORDER BY POST_CREATED_AT DESC";
  $stid = oci_parse($conn, $sql);
  oci_bind_by_name($stid, ":user_id", $user_id);
  oci_execute($stid);
  while ($row = oci_fetch_assoc($stid)) {
      $title = htmlspecialchars($row['POST_TITLE']);
      $date = $row['POST_CREATED_AT'];
      echo "<tr>";
      echo "<td>{$row['POST_ID']}</td>";
      echo "<td><a href='post/post_view.php?id={$row['POST_ID']}'>$title</a></td>";
      echo "<td>$date</td>";
      echo "</tr>";
  }
  ?>
</table>

<h3>💬 내가 작성한 댓글</h3>
<table border="1" cellpadding="8" width="100%">
  <tr><th>번호</th><th>댓글 내용</th><th>작성일</th></tr>
  <?php
  $sql = "SELECT COMMENTS_ID, COMMENTS_CONTENT, COMMENTS_CREATED_AT, POST_ID 
          FROM COMMENTS 
          WHERE USER_ID = :user_id 
          ORDER BY COMMENTS_CREATED_AT DESC";
  $stid = oci_parse($conn, $sql);
  oci_bind_by_name($stid, ":user_id", $user_id);
  oci_execute($stid);
  while ($row = oci_fetch_assoc($stid)) {
      $content = htmlspecialchars($row['COMMENTS_CONTENT']);
      $date = $row['COMMENTS_CREATED_AT'];
      $post_id = $row['POST_ID'];
      $comment_id = $row['COMMENTS_ID'];
      echo "<tr>";
      echo "<td>$comment_id</td>";
      echo "<td><a href='post/post_view.php?id=$post_id#comment-$comment_id'>$content</a></td>";
      echo "<td>$date</td>";
      echo "</tr>";
  }
  ?>
</table>
