<?php
session_start();
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>추천 메타 전체 보기</title>
  <link rel="stylesheet" href="main.css">
  
</head>
<body>



<?php include 'header.php'; ?>

<div class="container">
  <div class="meta-section">
    <h2>추천 메타 <small style="font-size:0.9rem;color:gray;">(v14.5)</small></h2>
    <div class="meta-list">
      <?php
      $sqlMeta  = "SELECT id AS META_ID, title AS TITLE, version AS VERSION, detail_url AS DETAIL_URL
                     FROM meta_item
                    ORDER BY id DESC";
      $stidMeta = oci_parse($conn, $sqlMeta);
      oci_execute($stidMeta);

      while ($meta = oci_fetch_assoc($stidMeta)) {
          $meta_id   = $meta['META_ID'];
          $title     = htmlspecialchars($meta['TITLE'],     ENT_QUOTES);
          $version   = htmlspecialchars($meta['VERSION'],   ENT_QUOTES);
          $detailUrl = htmlspecialchars($meta['DETAIL_URL'],ENT_QUOTES);
          ?>
          <div class="meta-item">
            <div class="meta-info">
              <div class="meta-title">
                <?= $title ?> <span style="font-size:0.8rem;color:gray;"><?= $version ?></span>
              </div>
               <div class="meta-champs">
      <?php
      $sqlChamps = "
        SELECT
          mc.champ_src,
          c.champ_name,
          c.champ_cost,
          c.champ_skill,
          c.champ_class
        FROM meta_champion mc
        JOIN champion      c
          ON c.champ_src = mc.champ_src
        WHERE mc.meta_id = :meta_id
         ORDER BY c.champ_cost ASC, mc.id ASC
      ";
      $stidChamps = oci_parse($conn, $sqlChamps);
      oci_bind_by_name($stidChamps, ':meta_id', $meta_id);
      oci_execute($stidChamps);
  $allSynergies = [];
  $synergyIcons = [
    '속사포'=>'🔫','요새'=>'🛡️','증.폭.'=>'💥','책략가'=>'🎩','처형자'=>'⚔️',
    '학살자'=>'🔪','거리의 악마'=>'😈','군주'=>'👑','네트워크의 신'=>'🌐',
    '니트로'=>'🚀','동물특공대'=>'🐾','바이러스'=>'🦠','범죄 조직'=>'🕵️‍♂️',
    '사이버보스'=>'🤖','사이퍼'=>'🔒','신성기업'=>'🏛️','엑소테크'=>'🛰️',
    '영혼 살해자'=>'👻','폭발 봇'=>'💣','황금 황소'=>'🐂','기술광'=>'🔧',
    '난동꾼'=>'🥊','다이나모'=>'⚡','사격수'=>'🎯','선봉대'=>'🚩',
  ];

      while ($rowCh = oci_fetch_assoc($stidChamps)):
        
        $rawCls = $rowCh['CHAMP_CLASS'];

    // 2) 시너지 배열로 분리 → trim → 빈 문자열 제거 → 개수 세기
    $synergies    = array_filter(
        array_map('trim', explode(',', $rawCls)),
        fn($s) => $s !== ''
    );

    // ⚡ 전역 카운트 합산
    foreach ($synergies as $syn) {
        $allSynergies[$syn] = ($allSynergies[$syn] ?? 0) + 1;
    }

    // 3) HTML 출력용 이스케이프
    $src        = htmlspecialchars($rowCh['CHAMP_SRC'],   ENT_QUOTES);
    $name       = htmlspecialchars($rowCh['CHAMP_NAME'],  ENT_QUOTES);
    $cost       = (int)$rowCh['CHAMP_COST'];
    $skill      = htmlspecialchars($rowCh['CHAMP_SKILL'], ENT_QUOTES);
    $clsEscaped = htmlspecialchars($rawCls,               ENT_QUOTES);
?>
        <div class="champ-wrapper" style="position:relative;display:inline-block;margin:0 6px;">
          <img src="images/champs/<?= $src ?>" alt="<?= $name ?>"
           class="champ-icon"
    style="width:60px; height:60px; border-radius:4px; border:1px solid #444;"
  >
         <div class="champ-label" style="
       text-align:center;
       margin-top:4px;
       font-size:0.75rem;
       color:#f0f0f0;
       white-space:nowrap;
  ">
    <?= $name ?>
    
  </div>

          <div class="tooltip-items" style="
                display:none;
                position:absolute; top:100%; left:50%;
                transform:translateX(-50%);
                background:rgba(0,0,0,0.85);
                padding:.5rem;
                border-radius:6px;
                color:#f0f0f0;
                white-space:nowrap;
                z-index:10;
          ">
            <div style="font-weight:bold;margin-bottom:.3rem;">
              <?= $name ?> <span style="color:#ccc;font-size:.85rem;">💰<?= $cost ?></span>
            </div>
            <div style="margin-bottom:.3rem;">🪄 <?= $skill ?></div>
            + <div style="margin-bottom:.5rem;">🏷️ <?= $clsEscaped ?></div>
            <div style="display:flex;gap:.3rem;">
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
          </div>
        </div>
      <?php endwhile; oci_free_statement($stidChamps);
      arsort($allSynergies);
      if (!empty($allSynergies)) {
    echo '<div class="meta-synergy-summary" style="margin:10px 0; font-size:0.85rem; color:#ffd700;">';
    foreach ($allSynergies as $synName => $cnt) {
        $icon = $synergyIcons[$synName] ?? '🔷';
        echo "{$icon} {$synName}({$cnt})&nbsp;&nbsp;";
    }
    echo '</div>';
}
      ?>
    </div>
    <!-- 여기서 meta-champs 끝 -->
            </div>
            
          </div>
      <?php
      }
      oci_free_statement($stidMeta);
      ?>
    </div>
  </div>
</div>
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
</script>
</body>
</html>
