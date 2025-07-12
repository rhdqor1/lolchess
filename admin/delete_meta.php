<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../db_connect.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw,true);
if (!$data||empty($data['meta_id'])) {
  echo json_encode(['success'=>false,'error'=>'잘못된 요청']);
  exit;
}
$mid = intval($data['meta_id']);
// 챔피언 데이터도 함께 삭제
$sql1 = "DELETE FROM meta_champion WHERE meta_id=:m";
$stid1 = oci_parse($conn,$sql1);
oci_bind_by_name($stid1,':m',$mid);
oci_execute($stid1,OCI_COMMIT_ON_SUCCESS);
oci_free_statement($stid1);

// 메타 아이템 삭제
$sql2 = "DELETE FROM meta_item WHERE id=:m";
$stid2 = oci_parse($conn,$sql2);
oci_bind_by_name($stid2,':m',$mid);
$res = oci_execute($stid2,OCI_COMMIT_ON_SUCCESS);
if (!$res) {
  $e = oci_error($stid2);
  echo json_encode(['success'=>false,'error'=>$e['message']]);
  exit;
}
oci_free_statement($stid2);
echo json_encode(['success'=>true]);
oci_close($conn);
exit;
