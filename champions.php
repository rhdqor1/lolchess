<?php
session_start();
require_once 'db_connect.php';
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
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>티어별 챔피언 & 추천 아이템</title>
  <link rel="stylesheet" href="main.css">
  <style>
    /* champions.php 전용 레이아웃 */
   /* 챔피언 & 아이템 레이아웃 */
.champion-row {
  display: flex;
  gap: 1rem;
}
.champ-item {
  background: #1f1f1f;
  padding: .75rem;
  border-radius: 8px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .5rem;
}
.champ-icon {
  width: 64px;
  height: 64px;
  border-radius: 6px;
  border: 2px solid #444;
}
.champ-name {
  font-size: 0.9rem;
  color: #fff;
}
.item-icons {
  display: flex;
  gap: .25rem;
  flex-wrap: wrap;
  justify-content: center;
}
.item-icon {
  width: 32px;
  height: 32px;
  border-radius: 4px;
  border: 1px solid #666;
}

  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <div class="container">
    <h2>티어별 챔피언 & 추천 아이템</h2>
    <div class="filter-synergies" style="margin:1rem 0;">
  <button data-synergy="all" class="active">All</button>
  <?php foreach ($synergies as $syn): ?>
    <button data-synergy="<?= $syn ?>"><?= $syn ?></button>
  <?php endforeach; ?>
</div>

    
    <!-- 여기서 .tiers 시작 -->
     <div class="tiers">
    <?php
    for ($tier = 1; $tier <= 5; $tier++):
     $sql = "
  SELECT
    champ_src,
    champ_name,
    champ_cost,      -- ★ cost
    champ_skill,     -- ★ skill
    champ_class      -- ★ class
  FROM champion
 WHERE champ_cost = :tier
 ORDER BY champ_name
";
  $stid = oci_parse($conn, $sql);
  oci_bind_by_name($stid, ':tier', $tier);
  oci_execute($stid);
?>
      <!-- champions.php 의 반복 HTML 부분 -->
<section class="champion-tier">
  <h3>티어 <?= $tier ?></h3>
  <div class="champion-row">
    <?php while ($champ = oci_fetch_assoc($stid)): 
  $src   = htmlspecialchars($champ['CHAMP_SRC'],  ENT_QUOTES);
  $name  = htmlspecialchars($champ['CHAMP_NAME'], ENT_QUOTES);
  $cost  = htmlspecialchars($champ['CHAMP_COST'],  ENT_QUOTES);   // ★
  $skill = htmlspecialchars($champ['CHAMP_SKILL'], ENT_QUOTES);   // ★
  $cls   = htmlspecialchars($champ['CHAMP_CLASS'], ENT_QUOTES);   // ★
?>
    <div class="champ-item" data-synergies="<?= $cls ?>">
      <!-- position:relative 래퍼 -->
      <div class="champ-wrapper" style="position:relative;">
        <!-- 1) 챔피언 아이콘 -->
        <img src="/images/champs/<?= $src ?>"
             alt="<?= $name ?>"
             class="champ-icon">

        <!-- 툴팁 -->
        <div class="tooltip-items" style="
             display:none;
             position:absolute;
             top:70px; left:50%;
             transform:translateX(-50%);
             background:rgba(0,0,0,0.85);
             padding:.5rem;
             border-radius:6px;
             min-width:180px;
             z-index:10;
             flex-direction:column;
        ">
          <div style="font-weight:bold;color:#fff;margin-bottom:.3rem;">
            <?= $name ?> <span style="color:#ddd;font-size:.9rem;">💰<?= $cost ?></span>
          </div>
          <div style="color:#ccc;font-size:.9rem;">🪄 <?= $skill ?></div>
          <div style="color:#ccc;font-size:.9rem;">🏷️ <?= $cls ?></div>
          <div style="display:flex;gap:.3rem;flex-wrap:wrap;margin-top:.4rem;">
            <?php
            $sqlItems = "
              SELECT i.ITEM_ID, i.ITEM_IMAGE_PATH
                FROM champion_item_map cim
                JOIN item i ON i.ITEM_ID = cim.ITEM_ID
               WHERE cim.champ_src = :champ_src
               ORDER BY i.ITEM_ID
            ";
            $stI = oci_parse($conn, $sqlItems);
            oci_bind_by_name($stI, ':champ_src', $src);
            oci_execute($stI);
            while ($it = oci_fetch_assoc($stI)):
              $path = str_replace('{item_id}', $it['ITEM_ID'], $it['ITEM_IMAGE_PATH']);
            ?>
              <img src="<?= ltrim(htmlspecialchars($path, ENT_QUOTES), '/') ?>"
                   style="width:32px;height:32px;border:1px solid #666;border-radius:4px;">
            <?php endwhile; oci_free_statement($stI); ?>
          </div>
        </div><!-- /.tooltip-items -->
      </div><!-- /.champ-wrapper -->

      <!-- 2) 챔피언 이름 -->
      <div class="champ-name"><?= $name ?></div>
      <!-- 3) 추천 아이템 목록 -->
      <div class="item-icons">
        <?php
        $sqlItems = "
          SELECT i.ITEM_ID, i.ITEM_IMAGE_PATH
            FROM champion_item_map cim
            JOIN item i ON i.ITEM_ID = cim.ITEM_ID
           WHERE cim.champ_src = :champ_src
           ORDER BY i.ITEM_ID
        ";
        $iSt = oci_parse($conn, $sqlItems);
        oci_bind_by_name($iSt, ':champ_src', $src);
        oci_execute($iSt);
        while ($it = oci_fetch_assoc($iSt)):
          $path = str_replace('{item_id}', $it['ITEM_ID'], $it['ITEM_IMAGE_PATH']);
        ?>
          <img
            src="<?= ltrim(htmlspecialchars($path, ENT_QUOTES), '/') ?>"
            class="item-icon"
            alt=""
          >
        <?php endwhile; oci_free_statement($iSt); ?>
      </div>
    </div><!-- /.champ-item -->
    <?php endwhile; ?>
  </div><!-- /.champion-row -->
</section>
    <?php
      oci_free_statement($stid);
    endfor;
    ?>
    </div><!-- /.tiers -->

  </div><!-- /.container -->

  <?php oci_close($conn); ?>
  <script>
// hover 시 툴팁 show/hide
document.querySelectorAll('.champ-wrapper').forEach(el => {
  el.addEventListener('mouseenter', () => {
    el.querySelector('.tooltip-items').style.display = 'flex';
  });
  el.addEventListener('mouseleave', () => {
    el.querySelector('.tooltip-items').style.display = 'none';
  });
});
const buttons = document.querySelectorAll('.filter-synergies button');
  const items   = document.querySelectorAll('.champ-item');

  buttons.forEach(btn => {
    btn.addEventListener('click', () => {
      buttons.forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      const sel = btn.dataset.synergy;
      items.forEach(item => {
        const arr = item.dataset.synergies.split(',').map(s=>s.trim());
        item.style.display = (sel==='all'||arr.includes(sel)) ? '' : 'none';
      });
    });
  });

</script>
</body>
</html>