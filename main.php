<?php
session_start();
require_once 'db_connect.php';  // 위에서 만든 파일 포함
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LoLCHESS.GG - 롤토체스 공략</title>
  <link rel="stylesheet" href="main.css">
</head>
<body>
  <?php include 'header.php'; ?>

  <div class="container">
    <!-- 추천 메타 섹션 시작 -->
   <!-- main.php -->
<!-- 추천 메타 섹션 -->
<div class="meta-section">
  <h2>추천 메타 <small style="font-size:0.9rem;color:gray;">(v14.5)</small></h2>
  <div class="meta-list">
    <?php
    // 1) meta_item 테이블에서 메타 항목 불러오기
$sqlMeta = "
  SELECT * FROM (
    SELECT id AS META_ID, title AS TITLE, version AS VERSION, detail_url AS DETAIL_URL
              FROM meta_item
             ORDER BY display_order ASC
  )
  WHERE ROWNUM <= 4
";
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
              // 2) meta_champion 테이블에서 이 meta_id에 속한 champ_src만 불러와서 출력
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
  '속사포'         => '🔫',
  '요새'           => '🛡️',
  '증.폭.'         => '💥',   // 증폭의 느낌
  '책략가'         => '🎩',
  '처형자'         => '⚔️',
  '학살자'         => '🔪',
  '거리의 악마'    => '😈',
  '군주'           => '👑',
  '네트워크의 신'  => '🌐',
  '니트로'         => '🚀',
  '동물특공대'      => '🐾',
  '바이러스'       => '🦠',
  '범죄 조직'      => '🕵️‍♂️',
  '사이버보스'     => '🤖',
  '사이퍼'         => '🔒',
  '신성기업'       => '🏛️',
  '엑소테크'       => '🛰️',
  '영혼 살해자'    => '👻',
  '폭발 봇'        => '💣',
  '황금 황소'      => '🐂',
  '기술광'         => '🔧',
  '난동꾼'         => '🥊',
  '다이나모'       => '⚡',
  '사격수'         => '🎯',
  '선봉대'         => '🚩',
];

while ($rowCh = oci_fetch_assoc($stidChamps)) {
    // 1) DB 에서 가져온 원본 시너지 문자열
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

  <div class="champ-wrapper">
    <!-- 아이콘 -->
    <img src="/images/champs/<?= $src ?>" class="champ-icon">
    <div class="champ-label" style="
        text-align:center;
        margin-top:4px;
        font-size:0.75rem;
        color:#f0f0f0;
        white-space:nowrap;
  ">
    <?= $name ?>
  </div>

    <!-- 툴팁 -->
    <div class="tooltip-items">
      <!-- 1. 이름 + 코스트 -->
      <div class="tooltip-title">
        <strong><?= $name ?></strong> <span class="tooltip-cost">💰<?= $cost ?></span>
      </div>
      <!-- 2. 스킬 -->
      <div class="tooltip-skill">🪄 <?= $skill ?></div>
      <!-- 3. 클래스 -->
      <div class="tooltip-class">🏷️ <?= $clsEscaped ?></div>
      <!-- 4. 추천 아이템 -->
      <div class="tooltip-item-list">
        <?php
          $sqlItems = "
            SELECT i.ITEM_ID, i.ITEM_IMAGE_PATH
              FROM champion_item_map cim
              JOIN item i
                ON i.ITEM_ID = cim.ITEM_ID
             WHERE cim.champ_src = :champ_src
             ORDER BY i.ITEM_ID
          ";
          $stidI = oci_parse($conn, $sqlItems);
          oci_bind_by_name($stidI, ':champ_src', $src);
          oci_execute($stidI);

          while ($it = oci_fetch_assoc($stidI)) {
            $path = str_replace('{item_id}', $it['ITEM_ID'], $it['ITEM_IMAGE_PATH']);
        ?>
          <img src="<?= htmlspecialchars(ltrim($path,'/'), ENT_QUOTES) ?>"
               class="tooltip-item-icon">
        <?php
          }
          oci_free_statement($stidI);
        ?>
      </div>
    </div><!-- /.tooltip-items -->
  </div><!-- /.champ-wrapper -->
<?php
}
oci_free_statement($stidChamps);
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
          </div>
        </div>
    <?php
    }
    oci_free_statement($stidMeta);
    ?>
  </div><!-- /.meta-list -->

  <div style="text-align:center; margin-top:10px;">
    <a href="detail.php"><button class="btn-more-meta">📘 메타 더 보기</button></a>
  </div>
</div><!-- /.meta-section -->

    <!-- ======================================== -->
    <!--    추천 메타 섹션 끝                     -->
    <!-- ======================================== -->

    <!-- ======================================== -->
    <!--    커뮤니티 게시글 섹션                   -->
    <!-- ======================================== -->
     <div class="community-section">
      <h2>커뮤니티 게시글</h2>
      <table class="community-table">
        <thead>
          <tr>
            <th>글 ID</th>
            <th>제목</th>
            <th>작성자</th>
            <th>작성일</th>
             <th>조회수</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // (3) community_posts 테이블에서 최근 게시글 7개 정도 조회 (작성시간 내림차순)
         $sqlPosts = "
            SELECT * FROM (
              SELECT P.POST_ID, P.POST_TITLE, P.POST_USER_ID, U.USER_NAME, 
                     P.POST_CREATED_AT, P.IS_NOTICE, P.POST_VIEW_COUNT
              FROM POST P
              JOIN USERS U ON P.POST_USER_ID = U.USER_ID
              ORDER BY P.IS_NOTICE DESC, P.POST_CREATED_AT DESC
             )
            WHERE ROWNUM <= 7
           ";
          $stidPosts = oci_parse($conn, $sqlPosts);
          oci_execute($stidPosts);

          while ($post = oci_fetch_assoc($stidPosts)) {
              $id = $post['POST_ID'];
              if ($post['IS_NOTICE'] === 'Y') $id = '📌';
              $title    = htmlspecialchars($post['POST_TITLE'], ENT_QUOTES);
              $author   = htmlspecialchars($post['USER_NAME'], ENT_QUOTES);
              // DATE → "HH24:MI" 문자열로 포맷
              $created  = htmlspecialchars($post['POST_CREATED_AT'], ENT_QUOTES);
              $viewcount   = htmlspecialchars($post['POST_VIEW_COUNT'], ENT_QUOTES);
              ?>
              <tr>
                <td><?= $id ?></td>
                <td class="title"><a href='post/post_view.php?id=<?= $id ?>'><?= $title ?></a></td>
                <td><?= $author ?></td>
                <td><?= $created ?></td>
                <td><?= $viewcount ?></td>
              </tr>
          <?php
          }
          oci_free_statement($stidPosts);
          ?>
        </tbody>
      </table>
    </div>
    <!-- ======================================== -->
    <!--    커뮤니티 게시글 섹션 끝                -->
    <!-- ======================================== -->

    <!-- ======================================== -->
    <!--    추천 영상 섹션                         -->
    <!--    (기존 코드와 동일)                      -->
    <!-- ======================================== -->
    <div class="video-section">
      <h2>추천 영상</h2>
      <?php
      // (4) videos 테이블에서 조회 (기존 코드 재사용)
      $sql = "SELECT ID, TITLE, YOUTUBE_URL FROM videos ORDER BY CREATED_AT DESC";
      $stid = oci_parse($conn, $sql);
      oci_execute($stid);
      ?>
      <div class="video-grid">
        <?php while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)): ?>
          <?php
            $id    = $row['ID'];
            $title = htmlspecialchars($row['TITLE'], ENT_QUOTES, "UTF-8");
            $clob  = $row['YOUTUBE_URL'];
            $raw_url = is_object($clob) ? $clob->load() : (string)$clob;
            $raw_url = trim($raw_url);

            // YouTube VIDEO_ID 추출
            $video_id = "";
            if (preg_match('@youtu\.be/([A-Za-z0-9_\-]+)@i', $raw_url, $m)) {
              $video_id = $m[1];
            } elseif (preg_match('/v=([A-Za-z0-9_\-]+)/i', $raw_url, $m)) {
              $video_id = $m[1];
            } elseif (preg_match('@embed/([A-Za-z0-9_\-]+)@i', $raw_url, $m)) {
              $video_id = $m[1];
            }
            if ($video_id !== "") {
              $video_id = preg_replace("/[^A-Za-z0-9_\-]/", "", $video_id);
            }
            $embed_url = ($video_id !== "") ? "https://www.youtube.com/embed/" . $video_id : "";
          ?>

          <div class="video-item">
            <h3><?= $title ?></h3>
            <?php if ($embed_url !== ""): ?>
              <div class="iframe-wrapper">
                <iframe
                  src="<?= htmlspecialchars($embed_url, ENT_QUOTES, "UTF-8") ?>"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                  allowfullscreen
                ></iframe>
              </div>
            <?php else: ?>
              <div class="invalid-url">
                ⚠ 유효하지 않은 YouTube URL 입니다.
              </div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      </div>
      <?php
      if ($conn) {
        oci_free_statement($stid);
        oci_close($conn);
      }
      ?>
    </div>
    <!-- ======================================== -->
    <!--    추천 영상 섹션 끝                     -->
    <!-- ======================================== -->
  </div>
</body>
</html>
