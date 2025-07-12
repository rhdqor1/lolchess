<?php
require_once '../db_connect.php'; // 이 안에서 이미 연결됨

$champ_id = $_POST['champ_id'] ?? null;
if (!$champ_id) die('챔피언 ID 누락');

$deleteSqls = [
  "DELETE FROM CHAMPION_ITEM_MAP WHERE CHAMP_SRC = (SELECT CHAMP_SRC FROM CHAMPION WHERE CHAMP_ID = :cid)",
  "DELETE FROM SYNERGY_CHAMPION WHERE CHAMP_ID = :cid",
  "DELETE FROM CHAMPION WHERE CHAMP_ID = :cid"
];

foreach ($deleteSqls as $sql) {
  $stid = oci_parse($conn, $sql);
  oci_bind_by_name($stid, ":cid", $champ_id);
  if (!oci_execute($stid, OCI_NO_AUTO_COMMIT)) {
    $err = oci_error($stid);
    oci_rollback($conn);
    die("삭제 실패: " . $err['message']);
  }
}

oci_commit($conn);
oci_close($conn);

header("Location: champion_admin.php");
exit;
