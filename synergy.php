<?php
session_start();
require_once 'db_connect.php';

// 1. 시너지 전체 조회
$sql = "SELECT * FROM SYNERGY";
$stid = oci_parse($conn, $sql);
oci_execute($stid);

$synergies = [];
while ($row = oci_fetch_assoc($stid)) {
    $synergies[] = $row;
}

// 2. 시너지별 챔피언 이미지 목록 조회 (JOIN)
$championImagesBySynergy = [];

$sql = "
  SELECT sc.synergy_id, c.champ_src
  FROM synergy_champion sc
  JOIN champion c ON sc.champ_id = c.champ_id
";
$stid = oci_parse($conn, $sql);
oci_execute($stid);

while ($row = oci_fetch_assoc($stid)) {
    $sid = $row['SYNERGY_ID'];
    $champ_src = $row['CHAMP_SRC'];
    if (!isset($championImagesBySynergy[$sid])) {
        $championImagesBySynergy[$sid] = [];
    }
    $championImagesBySynergy[$sid][] = $champ_src;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>시너지 목록</title>
  <link rel="stylesheet" href="main.css">
  <style>
    .synergy-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.5rem;
    }
    .synergy-card {
      background: #1e1e1e;
      padding: 1rem;
      border-radius: 7px;
      box-shadow: 0 0 10px rgba(0,0,0,0.5);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 620px;
      overflow: hidden;
    }
    .synergy-title {
      font-size: 1.1rem;
      font-weight: bold;
      color: #ffffff;
      margin-bottom: 0.5rem;
      flex-shrink: 0;
    }
.synergy-desc {
  font-size: 0.9rem;
  color: #cccccc;
  margin-bottom: 0.5rem;
  /* white-space 제거 or normal 처리 */
  white-space: normal;
  max-height: 300px;
  overflow-wrap: break-word;
}

    .synergy-tiers {
      font-size: 0.8rem;
      color: #80ff80;
      white-space: normal;
      margin-bottom: 0.8rem;
      flex-shrink: 0;
    }
    .champion-icons {
      display: flex;
      flex-wrap: wrap;
      gap: 7.5px;
      margin-top: auto;
    }
    .champion-icons img {
      width: 60px;
      height: 60px;
      border-radius: 7px;
      border: 1px solid #444;
    }
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
  <h2 style="color: #87CEFA; margin-bottom: 1.5rem;">시너지 목록</h2>

  <div class="synergy-grid">
    <?php foreach ($synergies as $synergy): ?>
      <div class="synergy-card">
        <div class="synergy-title"><?= htmlspecialchars($synergy['NAME']) ?></div>
        <div class="synergy-desc"><?= nl2br(htmlspecialchars($synergy['DESCRIPTION'])) ?></div>
        <div class="synergy-tiers"><?= nl2br(htmlspecialchars($synergy['TIER'] ?? '')) ?></div>
<div class="champion-icons">
  <?php
    $sid = $synergy['ID'];
    if (isset($championImagesBySynergy[$sid])) {
      foreach ($championImagesBySynergy[$sid] as $imgName):
        $imgPath = '/images/champs/' . $imgName; // 경로 앞에 붙이기
  ?>
        <img src="<?= htmlspecialchars($imgPath) ?>" alt="champion">
  <?php endforeach; } ?>
</div>

      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>
