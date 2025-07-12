<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../db_connect.php';

// 1) 원본 JSON 파싱 및 action 검사
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'error' => '잘못된 요청']);
    exit;
}

$action = $data['action'];

switch ($action) {

  // ──────────────────────────────────────────────────────────
  case 'add':
    // 2) meta_id 꺼내기 & 검증
    $meta_id = intval($data['meta_id'] ?? 0);
    if ($meta_id <= 0) {
      echo json_encode(['success'=>false,'error'=>'잘못된 meta_id']);
      exit;
    }
    $src = trim($data['champ_src'] ?? '');
    if ($src === '') {
      echo json_encode(['success'=>false,'error'=>'잘못된 champ_src']);
      exit;
    }

    // 3) 중복 체크
    $chk = oci_parse($conn, "
      SELECT COUNT(*) AS CNT
        FROM meta_champion
       WHERE meta_id   = :m
         AND champ_src = :s
    ");
    oci_bind_by_name($chk, ':m', $meta_id);
    oci_bind_by_name($chk, ':s', $src);
    oci_execute($chk);
    $row = oci_fetch_assoc($chk);
    oci_free_statement($chk);
    if ($row['CNT'] > 0) {
      echo json_encode(['success'=>false,'error'=>'이미 추가된 챔피언입니다.']);
      exit;
    }

    // 4) INSERT
    $ins = oci_parse($conn, "
      INSERT INTO meta_champion (meta_id, champ_src)
           VALUES (:m, :s)
    ");
    oci_bind_by_name($ins, ':m', $meta_id);
    oci_bind_by_name($ins, ':s', $src);
    $ok = oci_execute($ins, OCI_COMMIT_ON_SUCCESS);
    oci_free_statement($ins);

    echo json_encode(['success' => (bool)$ok]);
    break;


  // ──────────────────────────────────────────────────────────
  case 'remove':
    // meta_id 꺼내기 & 검증
    $meta_id = intval($data['meta_id'] ?? 0);
    if ($meta_id <= 0) {
      echo json_encode(['success'=>false,'error'=>'잘못된 meta_id']);
      exit;
    }
    $src = trim($data['champ_src'] ?? '');
    if ($src === '') {
      echo json_encode(['success'=>false,'error'=>'잘못된 champ_src']);
      exit;
    }

    // DELETE
    $del = oci_parse($conn, "
      DELETE FROM meta_champion
       WHERE meta_id   = :m
         AND champ_src = :s
    ");
    oci_bind_by_name($del, ':m', $meta_id);
    oci_bind_by_name($del, ':s', $src);
    $ok = oci_execute($del, OCI_COMMIT_ON_SUCCESS);
    oci_free_statement($del);

    echo json_encode(['success' => (bool)$ok]);
    break;


  // ──────────────────────────────────────────────────────────
  case 'load':
    // meta_id 꺼내기 & 검증
    $meta_id = intval($data['meta_id'] ?? 0);
    if ($meta_id <= 0) {
      echo json_encode(['success'=>false,'error'=>'잘못된 meta_id']);
      exit;
    }

    // SELECT existing champs
    $qry = oci_parse($conn, "
      SELECT champ_src
        FROM meta_champion
       WHERE meta_id = :m
       ORDER BY id
    ");
    oci_bind_by_name($qry, ':m', $meta_id);
    oci_execute($qry);
    $out = [];
    while ($r = oci_fetch_assoc($qry)) {
      $out[] = $r['CHAMP_SRC'];
    }
    oci_free_statement($qry);

    echo json_encode(['success'=>true,'data'=>$out]);
    break;


  // ──────────────────────────────────────────────────────────
  case 'reorder':
    // order 배열 검증
    $order = $data['order'] ?? null;
    if (!is_array($order)) {
      echo json_encode(['success'=>false,'error'=>'잘못된 order 데이터']);
      exit;
    }

    // UPDATE display_order
    $upd = oci_parse($conn, "
      UPDATE meta_item
         SET display_order = :pos
       WHERE id            = :id
    ");
    foreach ($order as $idx => $mid) {
      $pos = intval($idx);
      $id  = intval($mid);
      oci_bind_by_name($upd, ':pos', $pos);
      oci_bind_by_name($upd, ':id',  $id);
      oci_execute($upd, OCI_COMMIT_ON_SUCCESS);
    }
    oci_free_statement($upd);

    echo json_encode(['success'=>true]);
    break;


  // ──────────────────────────────────────────────────────────
  default:
    echo json_encode(['success'=>false,'error'=>'알 수 없는 액션']);
    break;
}

oci_close($conn);
exit;
