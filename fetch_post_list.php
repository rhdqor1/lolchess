<?php
// 오류 출력
ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/db_connect.php';

$sort = $_GET['sort'] ?? 'latest';

$keyword = $_GET['keyword'] ?? '';
$type = $_GET['type'] ?? 'title';
$where = '';

if ($keyword !== '') {
  $escaped = '%' . strtoupper($keyword) . '%';
  if ($type === 'title') {
    $where = "AND UPPER(POST_TITLE) LIKE :kw";
  } elseif ($type === 'title_content') {
    $where = "AND UPPER(POST_TITLE) LIKE :kw OR UPPER(POST_CONTENT) LIKE :kw";
  } elseif ($type === 'author') {
    $where = "AND UPPER(POST_USER_ID) LIKE :kw";
  }
}

if ($sort === 'popular') {
$sql = "SELECT P.POST_ID, P.POST_TITLE, P.POST_USER_ID, U.USER_NAME, P.POST_CREATED_AT,
        (SELECT COUNT(*) FROM POST_LIKE PL WHERE PL.POST_ID = P.POST_ID) AS LIKE_COUNT,
        P.IS_NOTICE, P.POST_VIEW_COUNT
        FROM POST P
        JOIN USERS U ON P.POST_USER_ID = U.USER_ID
        WHERE P.IS_NOTICE = 'Y' OR 
              (SELECT COUNT(*) FROM POST_LIKE PL WHERE PL.POST_ID = P.POST_ID) >= 3
        $where
        ORDER BY P.IS_NOTICE DESC, P.POST_CREATED_AT DESC";
} else {
$sql = "SELECT P.POST_ID, P.POST_TITLE, P.POST_USER_ID, U.USER_NAME, P.POST_CREATED_AT,
        (SELECT COUNT(*) FROM POST_LIKE PL WHERE PL.POST_ID = P.POST_ID) AS LIKE_COUNT,
        P.IS_NOTICE, P.POST_VIEW_COUNT
        FROM POST P
        JOIN USERS U ON P.POST_USER_ID = U.USER_ID
        WHERE 1=1
        $where
        ORDER BY P.IS_NOTICE DESC, P.POST_CREATED_AT DESC";
}

$stid = oci_parse($conn, $sql);
if ($keyword !== '') {
  oci_bind_by_name($stid, ":kw", $escaped);
}
oci_execute($stid);
?>

<table border="1" cellpadding="8" width="100%">
  <tr><th>번호</th><th>제목</th><th>작성자</th><th>작성일</th><th>추천수</th><th>조회수</th></tr>
  <?php while ($row = oci_fetch_assoc($stid)) {
    echo "<tr>";
    $pi = $row['POST_ID'];
    if ($row['IS_NOTICE'] === 'Y') $pi = '📌';
    echo "<td>$pi</td>";
    $title = htmlspecialchars($row['POST_TITLE']);
    echo "<td><a href='post/post_view.php?id={$row['POST_ID']}'>" . $title . "</a></td>";
    echo "<td>{$row['USER_NAME']}</td>";
    echo "<td>{$row['POST_CREATED_AT']}</td>";
    echo "<td>{$row['LIKE_COUNT']}</td>";
    echo "<td>{$row['POST_VIEW_COUNT']}</td>";
    echo "</tr>";
  } ?>
</table>
