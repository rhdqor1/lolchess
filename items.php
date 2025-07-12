<?php
require_once 'db_connect.php';
session_start();

$basicSql = "
    SELECT * FROM (
        SELECT * FROM DBUSER212358.ITEM 
        WHERE ITEM_ID IN (
            SELECT COMPONENT1_ID FROM DBUSER212358.COMBINATION
            UNION
            SELECT COMPONENT2_ID FROM DBUSER212358.COMBINATION
        )
        AND ITEM_ID NOT IN (
            SELECT RESULT_ITEM_ID FROM DBUSER212358.COMBINATION
        )
        ORDER BY TO_NUMBER(ITEM_ID)
    ) WHERE ROWNUM <= 10
";
$basicStid = oci_parse($conn, $basicSql);
oci_execute($basicStid);
$basicItems = [];
while ($row = oci_fetch_assoc($basicStid)) {
    $basicItems[] = $row;
}

$comboSql = "
    SELECT c.*, i.ITEM_ID, i.ITEM_NAME, i.ITEM_EFFECT, i.ITEM_IMAGE_PATH
    FROM DBUSER212358.COMBINATION c
    JOIN DBUSER212358.ITEM i ON c.RESULT_ITEM_ID = i.ITEM_ID
";

$comboStid = oci_parse($conn, $comboSql);
oci_execute($comboStid);
$comboMap = [];
while ($row = oci_fetch_assoc($comboStid)) {
    $key1 = $row['COMPONENT1_ID'] . '_' . $row['COMPONENT2_ID'];
    $key2 = $row['COMPONENT2_ID'] . '_' . $row['COMPONENT1_ID'];
    $comboMap[$key1] = $comboMap[$key2] = $row;
}

oci_free_statement($basicStid);
oci_free_statement($comboStid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="ko">
  <head>
  <meta charset="UTF-8">
  <title>TFT 아이템 조합표</title>
  <link rel="stylesheet" href="main.css"> <!-- 외부 CSS 우선 적용 -->
</head>
  <style>
    <style>
.item-grid {
  display: table;
  border-collapse: separate;
  border-spacing: 8px;
  margin: 2rem auto;
}
.header-row, .grid-row {
  display: table-row;
}
.corner-cell, .top-item, .left-item, .grid-cell {
  display: table-cell;
  width: 64px;
  height: 64px;
  padding: 2px;
  position: relative;
}
.corner-cell {
  background-color: transparent;
}
.top-item img, .left-item img, .grid-cell img {
  width: 64px;
  height: 64px;
  object-fit: cover;
  border-radius: 10px;
  transition: all 0.2s ease-in-out;
  display: block;
}
.grid-cell.unavailable {
  opacity: 0.3;
}
.highlight img {
  filter: none;
  opacity: 1;
  box-shadow: 0 0 8px #00ffff, 0 0 12px #00ffff;
}
.dim img {
  filter: grayscale(100%);
  opacity: 0.6;
}
.tooltip {
  position: absolute;
  background: rgba(0, 0, 0, 0.95);
  color: #fff;
  padding: 10px;
  border-radius: 8px;
  font-size: 13px;
  max-width: 250px;
  display: none;
  z-index: 999;
  box-shadow: 0 0 8px rgba(255,255,255,0.2);
}
.container {
  padding-top: 30px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}
h2 {
  margin-top: 0.5rem;
  margin-bottom: 0.2rem;
  color: #87CEFA;
}

.item-grid {
  display: table;
  border-collapse: separate;
  border-spacing: 8px;
  margin: 2rem auto;
}

</style>

  </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
  <h2>TFT 아이템 조합표</h2>
  <div class="item-grid">
    <div class="header-row">
      <div class="corner-cell"></div>
      <?php foreach ($basicItems as $colItem): ?>
        <div class="top-item"
             data-id="<?= $colItem['ITEM_ID'] ?>"
             data-effect="<?= htmlspecialchars($colItem['ITEM_EFFECT']) ?>">
          <img src="/images/items/<?= htmlspecialchars($colItem['ITEM_ID']) ?>.png"
               alt="<?= htmlspecialchars($colItem['ITEM_NAME']) ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <?php foreach ($basicItems as $rowItem): ?>
      <div class="grid-row">
        <div class="left-item"
             data-id="<?= $rowItem['ITEM_ID'] ?>"
             data-effect="<?= htmlspecialchars($rowItem['ITEM_EFFECT']) ?>">
          <img src="/images/items/<?= htmlspecialchars($rowItem['ITEM_ID']) ?>.png"
               alt="<?= htmlspecialchars($rowItem['ITEM_NAME']) ?>">
        </div>
        <?php foreach ($basicItems as $colItem): ?>
          <?php
            $key = $rowItem['ITEM_ID'] . '_' . $colItem['ITEM_ID'];
            $combo = $comboMap[$key] ?? null;
          ?>
          <div class="grid-cell <?= $combo ? '' : 'unavailable' ?>"
               data-top-id="<?= $colItem['ITEM_ID'] ?>"
               data-left-id="<?= $rowItem['ITEM_ID'] ?>"
               data-combo='<?= $combo ? json_encode($combo) : '' ?>'>
            <?php if ($combo): ?>
              <img src="/images/items/<?= htmlspecialchars($combo['ITEM_ID']) ?>.png"
                   alt="<?= htmlspecialchars($combo['ITEM_NAME']) ?>">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div id="tooltip" class="tooltip"></div>
</div>

<script>
const tooltip = document.getElementById('tooltip');

function getItemNameById(id) {
  const el = document.querySelector(`.top-item[data-id='${id}'], .left-item[data-id='${id}']`);
  return el ? el.querySelector('img').alt : '아이템';
}

document.querySelectorAll('.grid-cell').forEach(cell => {
  cell.addEventListener('mouseover', (e) => {
    const topId = cell.dataset.topId;
    const leftId = cell.dataset.leftId;
    const comboData = cell.dataset.combo ? JSON.parse(cell.dataset.combo) : null;

    document.querySelectorAll('.highlight, .dim').forEach(el => el.classList.remove('highlight', 'dim'));

    document.querySelector(`.top-item[data-id='${topId}']`)?.classList.add('highlight');
    document.querySelector(`.left-item[data-id='${leftId}']`)?.classList.add('highlight');
    cell.classList.add('highlight');

    document.querySelectorAll('.top-item, .left-item, .grid-cell').forEach(el => {
      if (!el.classList.contains('highlight')) {
        el.classList.add('dim');
      }
    });

    if (comboData) {
      const comboName = comboData.ITEM_NAME;
      const comboEffect = comboData.ITEM_EFFECT;
      const base1 = getItemNameById(comboData.COMPONENT1_ID);
      const base2 = getItemNameById(comboData.COMPONENT2_ID);

      tooltip.innerHTML = `<strong>${comboName}</strong><br>${comboEffect}<hr><small>조합: ${base1} + ${base2}</small>`;
      tooltip.style.display = 'block';
      tooltip.style.left = e.pageX + 10 + 'px';
      tooltip.style.top = e.pageY + 10 + 'px';
    }
  });

  cell.addEventListener('mousemove', (e) => {
    tooltip.style.left = e.pageX + 10 + 'px';
    tooltip.style.top = e.pageY + 10 + 'px';
  });

  cell.addEventListener('mouseout', () => {
    tooltip.style.display = 'none';
    document.querySelectorAll('.highlight, .dim').forEach(el => el.classList.remove('highlight', 'dim'));
  });
});

document.querySelectorAll('.top-item, .left-item').forEach(el => {
  el.addEventListener('mouseover', (e) => {
    document.querySelectorAll('.highlight, .dim').forEach(el => el.classList.remove('highlight', 'dim'));
    el.classList.add('highlight');

    document.querySelectorAll('.top-item, .left-item, .grid-cell').forEach(other => {
      if (!other.classList.contains('highlight')) {
        other.classList.add('dim');
      }
    });

    const img = el.querySelector('img');
    const name = img?.alt || '아이템';
    const effect = el.dataset.effect || '';
    tooltip.innerHTML = `<strong>${name}</strong><br>${effect}`;
    tooltip.style.display = 'block';
    tooltip.style.left = e.pageX + 10 + 'px';
    tooltip.style.top = e.pageY + 10 + 'px';
  });

  el.addEventListener('mousemove', (e) => {
    tooltip.style.left = e.pageX + 10 + 'px';
    tooltip.style.top = e.pageY + 10 + 'px';
  });

  el.addEventListener('mouseout', () => {
    tooltip.style.display = 'none';
    document.querySelectorAll('.highlight, .dim').forEach(el => el.classList.remove('highlight', 'dim'));
  });
});
</script>

</body>
</html>
