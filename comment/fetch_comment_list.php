<?php
session_start();
include __DIR__ . '/../db_connect.php';

$post_id = $_GET['post_id'] ?? '';
$sort = $_GET['sort'] ?? 'latest';

if (!isset($_GET['post_id']) || trim($_GET['post_id']) === '') {
  echo "<!-- post_id 없음 -->";
  exit;
}

if ($sort === 'popular') {
$sql = "SELECT C.*, U.USER_NAME, COUNT(CL.USER_ID) AS LIKE_COUNT
        FROM COMMENTS C
        JOIN COMMENT_LIKE CL ON C.COMMENTS_ID = CL.COMMENTS_ID
        JOIN USERS U ON C.USER_ID = U.USER_ID
        WHERE C.POST_ID = :post_id
        GROUP BY C.COMMENTS_ID, C.POST_ID, C.USER_ID, C.COMMENTS_CONTENT, C.COMMENTS_CREATED_AT, U.USER_NAME
        HAVING COUNT(CL.USER_ID) >= 1
        ORDER BY LIKE_COUNT DESC, C.COMMENTS_CREATED_AT DESC";
} else {
$sql = "SELECT C.*, U.USER_NAME, 
              (SELECT COUNT(*) FROM COMMENT_LIKE CL WHERE CL.COMMENTS_ID = C.COMMENTS_ID) AS LIKE_COUNT
        FROM COMMENTS C
        JOIN USERS U ON C.USER_ID = U.USER_ID
        WHERE C.POST_ID = :post_id
        ORDER BY C.COMMENTS_CREATED_AT DESC";
}

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":post_id", $post_id);
oci_execute($stid);

while ($row = oci_fetch_assoc($stid)) {
  $content = htmlspecialchars($row['COMMENTS_CONTENT']);
  $user = htmlspecialchars($row['USER_NAME']);
  $cid = $row['COMMENTS_ID'];
  $like_count = $row['LIKE_COUNT'];
  echo "<p><strong>{$user}:</strong> {$content} ";
  echo "<button onclick='likeComment(\"{$cid}\", this)'>❤️</button> <span class='clike'>{$like_count}명 추천함</span>";
  if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] === $row['USER_ID'] || $_SESSION['user_role'] === 'admin')) {
    echo " <button onclick='deleteComment(\"{$cid}\", this)'>🗑</button>";
    echo " <button onclick='editComment(\"{$cid}\")'>✏️</button>";
  }
  echo "</p>";
}
?>
