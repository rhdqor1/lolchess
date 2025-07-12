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
  <h2>👤 회원 목록</h2>
  <table border="1" cellpadding="8" cellspacing="0" width="100%">
    <tr><th>ID</th><th>이름</th><th>이메일</th><th>권한</th><th>가입일</th><th>삭제</th><th>승급</th><th>강등</th></tr>
    <?php
    $sql = "SELECT user_id, user_name, user_email, user_role, user_join_date FROM users ORDER BY user_id";
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    while ($row = oci_fetch_assoc($stid)) {
        echo "<tr>";
        echo "<td>{$row['USER_ID']}</td>";
        echo "<td>" . htmlspecialchars($row['USER_NAME']) . "</td>";
        echo "<td>{$row['USER_EMAIL']}</td>";
        echo "<td>{$row['USER_ROLE']}</td>";
        echo "<td>{$row['USER_JOIN_DATE']}</td>";
        echo "<td><a href='delete_user.php?id={$row['USER_ID']}' onclick=\"return confirm('정말 삭제하시겠습니까?');\">삭제</a></td>";
        echo "<td><a href='promote_user.php?id={$row['USER_ID']}' onclick=\"return confirm('정말 승급하시겠습니까?');\">승급</a></td>";
        echo "<td><a href='demote_user.php?id={$row['USER_ID']}' onclick=\"return confirm('정말 강등하시겠습니까?');\">강등</a></td>";
        echo "</tr>";
    }
    ?>
  </table>
  <a href="dashboard.php">← 관리자 대시보드로</a>
</div>
