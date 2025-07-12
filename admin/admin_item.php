<?php
require_once '../db_connect.php';
session_start();

// 전체 아이템 불러오기
$sql = "SELECT * FROM ITEM ORDER BY TO_NUMBER(ITEM_ID)";
$stid = oci_parse($conn, $sql);
oci_execute($stid);
$allItems = [];
while ($row = oci_fetch_assoc($stid)) {
    $allItems[] = $row;
}
oci_free_statement($stid);

// 전체 조합 정보 불러오기
$comboSql = "
    SELECT c.*, i.ITEM_NAME, i.ITEM_EFFECT, i.ITEM_IMAGE_PATH
    FROM COMBINATION c
    JOIN ITEM i ON c.RESULT_ITEM_ID = i.ITEM_ID
";
$comboStid = oci_parse($conn, $comboSql);
oci_execute($comboStid);
$combinations = [];
while ($row = oci_fetch_assoc($comboStid)) {
    $combinations[] = $row;
}
oci_free_statement($comboStid);

// 폼 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_mode'])) {
    $comp1 = $_POST['comp1_id'];
    $comp2 = $_POST['comp2_id'];
    $result_id = $_POST['new_result_id'];
    $item_name = $_POST['item_name'];
    $item_effect = $_POST['item_effect'];

    $uploadDir = 'images/items/';
    $imgFileName = $result_id . '.png';
    $imgFullPath = $uploadDir . $imgFileName;
    $imgPath = null;

    // 이미지 업로드 처리
    if (!empty($_FILES['item_image']['name'])) {
        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $imgFullPath)) {
            $imgPath = '/images/items/' . $imgFileName;
        }
    }

    // 조합 테이블 업데이트
    $updateComboSql = "
        UPDATE COMBINATION
        SET RESULT_ITEM_ID = :result_id
        WHERE COMPONENT1_ID = :c1 AND COMPONENT2_ID = :c2
    ";
    $stid = oci_parse($conn, $updateComboSql);
    oci_bind_by_name($stid, ':result_id', $result_id);
    oci_bind_by_name($stid, ':c1', $comp1);
    oci_bind_by_name($stid, ':c2', $comp2);
    oci_execute($stid);
    oci_free_statement($stid);

    // 아이템 테이블 업데이트
    if ($imgPath !== null) {
        $updateItemSql = "
            UPDATE ITEM
            SET ITEM_NAME = :name, ITEM_EFFECT = :effect, ITEM_IMAGE_PATH = :img
            WHERE ITEM_ID = :id
        ";
    } else {
        $updateItemSql = "
            UPDATE ITEM
            SET ITEM_NAME = :name, ITEM_EFFECT = :effect
            WHERE ITEM_ID = :id
        ";
    }

    $stid2 = oci_parse($conn, $updateItemSql);
    oci_bind_by_name($stid2, ':name', $item_name);
    oci_bind_by_name($stid2, ':effect', $item_effect);
    oci_bind_by_name($stid2, ':id', $result_id);
    if ($imgPath !== null) {
        oci_bind_by_name($stid2, ':img', $imgPath);
    }
    oci_execute($stid2);
    oci_free_statement($stid2);

    oci_close($conn);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
<a href="dashboard.php" style="color: white;">← 관리자 대시보드로</a>
  <title>아이템 조합 수정</title>
  <style>
    body { background: #111; color: white; font-family: sans-serif; }
    .form-block { max-width: 600px; margin: 40px auto; border: 1px solid #555; padding: 20px; border-radius: 8px; background: #1e1e1e; }
    label, select, input, textarea, button { display: block; width: 100%; margin-top: 10px; }
    img { width: 64px; height: 64px; margin-top: 10px; }
  </style>
</head>
<body>
  <h2 style="text-align:center">아이템 조합 수정</h2>
  <div class="form-block">
    <form method="post" enctype="multipart/form-data" id="edit-form">
      <input type="hidden" name="edit_mode" value="1">

      <label>상위 아이템 선택</label>
      <select name="new_result_id" id="result-select">
        <?php foreach ($allItems as $item): ?>
          <option value="<?= $item['ITEM_ID'] ?>"><?= $item['ITEM_NAME'] ?></option>
        <?php endforeach; ?>
      </select>

      <label>아이템 이미지 (선택)</label>
      <input type="file" name="item_image" accept="image/*">

      <label>아이템 이름</label>
      <input type="text" name="item_name" id="item-name">

      <label>아이템 효과</label>
      <textarea name="item_effect" id="item-effect"></textarea>

      <label>하위 아이템 1</label>
      <select name="comp1_id" id="comp1-select">
        <?php foreach ($allItems as $item): ?>
          <option value="<?= $item['ITEM_ID'] ?>"><?= $item['ITEM_NAME'] ?></option>
        <?php endforeach; ?>
      </select>

      <label>하위 아이템 2</label>
      <select name="comp2_id" id="comp2-select">
        <?php foreach ($allItems as $item): ?>
          <option value="<?= $item['ITEM_ID'] ?>"><?= $item['ITEM_NAME'] ?></option>
        <?php endforeach; ?>
      </select>

      <button type="submit">수정</button>
    </form>
  </div>

  <script>
    const comboData = <?= json_encode($combinations) ?>;

    document.getElementById('result-select').addEventListener('change', function () {
      const selected = this.value;
      const combo = comboData.find(c => c.RESULT_ITEM_ID === selected);
      if (!combo) return;

      document.getElementById('item-name').value = combo.ITEM_NAME;
      document.getElementById('item-effect').value = combo.ITEM_EFFECT;
      document.getElementById('comp1-select').value = combo.COMPONENT1_ID;
      document.getElementById('comp2-select').value = combo.COMPONENT2_ID;
    });

    document.getElementById('result-select').dispatchEvent(new Event('change'));
  </script>
</body>
</html>
