<?php
session_start();
require_once '../db_connect.php';

// 시너지 목록 (중복 제거)
$sqlClasses = "
  SELECT DISTINCT TRIM(REGEXP_SUBSTR(champ_class, '[^,]+', 1, LEVEL)) AS cls
    FROM champion
CONNECT BY REGEXP_SUBSTR(champ_class, '[^,]+', 1, LEVEL) IS NOT NULL
";
$stidCls = oci_parse($conn, $sqlClasses);
oci_execute($stidCls);
$synergies = [];
while ($r = oci_fetch_assoc($stidCls)) {
  $synergies[] = htmlspecialchars($r['CLS'], ENT_QUOTES);
}
oci_free_statement($stidCls);

// 아이템 목록
$sqlItems = "SELECT ITEM_ID, ITEM_NAME FROM ITEM ORDER BY ITEM_ID";
$stidItems = oci_parse($conn, $sqlItems);
oci_execute($stidItems);
$items = [];
while ($row = oci_fetch_assoc($stidItems)) {
  $items[] = $row;
}
oci_free_statement($stidItems);

// 챔피언 목록
$sqlChamps = "SELECT CHAMP_ID, CHAMP_NAME FROM CHAMPION ORDER BY CHAMP_NAME";
$stidChamps = oci_parse($conn, $sqlChamps);
oci_execute($stidChamps);
$champions = [];
while ($row = oci_fetch_assoc($stidChamps)) {
  $champions[] = $row;
}
oci_free_statement($stidChamps);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <a href="dashboard.php" style="color: white;">← 관리자 대시보드로</a>
  <title>챔피언 등록 및 삭제</title>
  <style>
    body { background: #111; color: white; font-family: sans-serif; }
    .form-block { max-width: 600px; margin: 40px auto; border: 1px solid #555; padding: 20px; border-radius: 8px; background: #1e1e1e; }
    label, select, input, textarea, button { display: block; width: 100%; margin-top: 10px; }
    .checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
    .checkbox-group label { width: auto; }
  </style>
</head>
<body>
  <h2 style="text-align:center">챔피언 등록</h2>
  <div class="form-block">
    <form method="post" action="champion_insert.php" enctype="multipart/form-data">
      <label>챔피언 이름</label>
      <input type="text" name="champ_name" required>

      <label>챔피언 이미지</label>
      <input type="file" name="champ_src" accept="image/*" required>

      <label>챔피언 스킬 설명</label>
      <textarea name="champ_skill" required></textarea>

      <label>챔피언 시너지 (여러 개 선택 가능)</label>
      <div class="checkbox-group">
        <?php foreach ($synergies as $s): ?>
          <label><input type="checkbox" name="champ_class[]" value="<?= $s ?>"> <?= $s ?></label>
        <?php endforeach; ?>
      </div>

      <label>챔피언 코스트</label>
      <select name="champ_cost" required>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <option value="<?= $i ?>"><?= $i ?>코스트</option>
        <?php endfor; ?>
      </select>

      <label>추천 아이템 선택 (최대 3개)</label>
      <div class="checkbox-group">
        <?php foreach ($items as $item): ?>
          <label><input type="checkbox" name="item_ids[]" value="<?= $item['ITEM_ID'] ?>"> <?= $item['ITEM_NAME'] ?></label>
        <?php endforeach; ?>
      </div>

      <button type="submit">챔피언 등록</button>
    </form>
  </div>

  <h2 style="text-align:center">챔피언 삭제</h2>
  <div class="form-block">
    <form method="post" action="champion_delete.php">
      <label>삭제할 챔피언 선택</label>
      <select name="champ_id" required>
        <?php foreach ($champions as $champ): ?>
          <option value="<?= $champ['CHAMP_ID'] ?>"><?= $champ['CHAMP_NAME'] ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" onclick="return confirm('정말 삭제하시겠습니까?')">챔피언 삭제</button>
    </form>
  </div>
</body>
</html>