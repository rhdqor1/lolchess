<?php
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['champ_name'] ?? '';
    $skill = $_POST['champ_skill'] ?? '';
    $cost = $_POST['champ_cost'] ?? '';
    $synergies = $_POST['champ_class'] ?? [];
    $items = $_POST['item_ids'] ?? [];

    // 이미지 업로드 처리
    if (isset($_FILES['champ_src']) && $_FILES['champ_src']['error'] === 0) {
        $imgName = basename($_FILES['champ_src']['name']);
        $uploadDir = __DIR__ . '/images/champs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $imgPath = $uploadDir . $imgName;

        if (!move_uploaded_file($_FILES['champ_src']['tmp_name'], $imgPath)) {
            die("이미지 저장 실패: $imgPath");
        }
    } else {
        die('이미지 업로드 실패');
    }

    // 챔피언 ID 자동 증가 시퀀스
    $getIdSql = "SELECT SEQ_CHAMPION_ID.NEXTVAL AS NEW_ID FROM DUAL";
    $stmtId = oci_parse($conn, $getIdSql);
    oci_execute($stmtId);
    $row = oci_fetch_assoc($stmtId);
    $newChampId = $row['NEW_ID'];

    // 챔피언 삽입
    $classStr = implode(', ', $synergies);
    $insertChamp = "
        INSERT INTO CHAMPION (CHAMP_ID, CHAMP_NAME, CHAMP_COST, CHAMP_SKILL, CHAMP_CLASS, CHAMP_SRC)
        VALUES (:id, :name, :cost, :skill, :class, :src)
    ";
    $stmt = oci_parse($conn, $insertChamp);
    oci_bind_by_name($stmt, ':id', $newChampId);
    oci_bind_by_name($stmt, ':name', $name);
    oci_bind_by_name($stmt, ':cost', $cost);
    oci_bind_by_name($stmt, ':skill', $skill);
    oci_bind_by_name($stmt, ':class', $classStr);
    oci_bind_by_name($stmt, ':src', $imgName);
    
    $r = oci_execute($stmt);
    if (!$r) {
        $e = oci_error($stmt);
        die("챔피언 등록 실패: " . $e['message']);
    }
    oci_free_statement($stmt);

    // 아이템 매핑
    $stmtItem = oci_parse($conn, "
        INSERT INTO CHAMPION_ITEM_MAP (CHAMP_SRC, ITEM_ID)
        VALUES (:src, :item_id)
    ");
    foreach ($items as $itemId) {
        oci_bind_by_name($stmtItem, ':src', $imgName);
        oci_bind_by_name($stmtItem, ':item_id', $itemId);
        oci_execute($stmtItem); // 실패 시 생략 가능
    }
    oci_free_statement($stmtItem);

    oci_close($conn);
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>
