<?php
// 에러가 JSON에 섞이지 않도록 끕니다.
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../db_connect.php';

// JSON 디코드
$body = file_get_contents('php://input');
$data = json_decode($body, true);

// 이름 유효성 검사
$title = isset($data['meta_name']) ? trim($data['meta_name']) : '';
if ($title === '') {
    echo json_encode(['success'=>false,'error'=>'메타 이름을 입력해주세요.']);
    exit;
}

// INSERT: title, version, detail_url 을 모두 명시
$sql = "
  INSERT INTO meta_item (title, version, detail_url)
  VALUES (:title, 1, ' ')
";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':title', $title);

$res = oci_execute($stid, OCI_COMMIT_ON_SUCCESS);
if (!$res) {
    $e = oci_error($stid);
    echo json_encode(['success'=>false,'error'=>$e['message']]);
    exit;
}

oci_free_statement($stid);
echo json_encode(['success'=>true]);
oci_close($conn);
exit;
