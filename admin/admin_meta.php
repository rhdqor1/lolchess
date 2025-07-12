<?php
session_start();

// 관리자 체크 (대소문자 무시)
if (
  !isset($_SESSION['user_id']) ||
  !isset($_SESSION['user_role']) ||
  strcasecmp($_SESSION['user_role'], 'ADMIN') !== 0
) {
  header('Location: ../index.php');
  exit;
}

// DB 연결
require_once __DIR__ . '/../db_connect.php';
?>
<!DOCTYPE html>
<html lang="ko">
<a href="dashboard.php">← 관리자 대시보드로</a>
<head>
   <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>관리자 - 추천 메타 관리</title>
  <style>
    /* 기본 레이아웃 */
/* ── 레이아웃 전체 ── */
body, html {
  margin: 0;
  padding: 0;
  height: 100%;
}
.container {
  display: flex;

  height: 100vh;
  align-items: flex-start;
}

/* ── 좌측 패널 ── */
.left-panel {
  /* 원하는 픽셀 고정 너비 (또는 %), 너무 좁으면 그리드가 깨지니 적당히 잡아주세요 */
  width: 250px;
  background: #f5f5f5;
  border-right: 1px solid #ddd;
  align-self: flex-start;

  /* flex 컬럼으로: 검색창 고정, 그리드가 남은 높이 채움 */
  display: flex;
  flex-direction: column;
  padding: 10px;
}

/* 검색창 */
#champ-search {
  box-sizing: border-box;
  width: 100%;
  padding: 6px 8px;
  margin-bottom: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 14px;
}

/* 챔피언 목록: 그리드로 4열 고정 */
.champ-list {
  flex: 1;              /* 검색창 밑에서 남은 공간을 차지 */
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  /* 세로 간격 6px, 가로 간격 6px */
  gap: 6px 6px;
  overflow-y: auto;
  padding-right: 4px;   /* 스크롤바 여백 */
}

/* 아이콘 */
.champ-wrapper img {
  width: 100%;   /* 그리드 셀 가득 */
  aspect-ratio: 1 / 1;
  object-fit: cover;
  border: 2px solid transparent;
  border-radius: 4px;
  cursor: pointer;
  transition: border-color .2s;
}
.champ-wrapper img:hover {
  border-color: #0288d1;
}

/* ── 우측 패널 (참고) ── */
.right-panel {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}
/* meta-item 우측에 작게 배치된 편집 버튼 */
.meta-item {
  position: relative;
  padding-right: 30px; /* 버튼 공간 확보 */
}
.edit-meta-btn {
  position: absolute;
  right: 6px;
  top: 6px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 14px;
  color: #555;
}
.edit-meta-btn:hover {
  color: #0288d1;
}


/* 버튼 바 중앙 정렬 */
.top-bar {
  display: flex;
  justify-content: center;  /* 수평 중앙 배치 */
  gap: 10px;                /* 버튼 사이 여백 */
  margin-bottom: 15px;
}
.top-bar button {
  background: #0288d1;
  color: #fff;
  border: none;
  padding: 8px 12px;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s;
}
.top-bar button:disabled {
  background: #aaa;
  cursor: not-allowed;
}
.top-bar button:hover:enabled {
  background: #0277bd;
}

/* 메타 리스트 */
.meta-list {
  margin-bottom: 20px;
}
.meta-item {
  padding: 10px;
  border: 1px solid #ccc;
  margin-bottom: 5px;
  background: #fff;
  cursor: pointer;
  transition: background 0.2s;
}
.meta-item:hover {
  background: #f0f0f0;
}
.meta-item.selected {
  background: #e0f7fa;
}

/* 메타 상세(드롭존) */
.meta-detail {
  display: none;
}
.meta-detail.active {
  display: block;
  margin-top: 10px;
}

.drop-zone {
  width: 70px;
  height: 70px;
  border: 2px dashed #aaa;
  display: inline-block;
  margin: 5px;
  vertical-align: top;
  position: relative;
  background: #fafafa;
  transition: background 0.2s;
  cursor: pointer;
}
.drop-zone:hover {
  background: #e0e0e0;
}

/* 슬롯에 들어간 이미지 */
.drop-zone img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  pointer-events: none;
}

/* 삭제 버튼 */
.drop-zone .remove-btn {
  position: absolute;
  top: 0;
  right: 0;
  background: rgba(0,0,0,0.6);
  color: #fff;
  width: 20px;
  height: 20px;
  text-align: center;
  line-height: 20px;
  font-size: 14px;
  display: none;
  cursor: pointer;
  border-radius: 2px;
}
.drop-zone:hover .remove-btn {
  display: block;
}

  </style>
</head>
<body>
  <div class="container">
    <!-- 좌측 챔피언 목록 -->
    <div class="left-panel">
       <input id="champ-search" type="text" placeholder="챔피언 검색" />
                <div class="champ-list">
      <?php
      $sql = "
    SELECT
      champ_id,
      champ_name,
      champ_src
    FROM champion
     ORDER BY champ_cost ASC, champ_name ASC
  ";
  $stid = oci_parse($conn, $sql);
  oci_execute($stid);

  while ($r = oci_fetch_assoc($stid)) {
    $fileName = htmlspecialchars($r['CHAMP_SRC'], ENT_QUOTES);
    $name     = htmlspecialchars($r['CHAMP_NAME'], ENT_QUOTES);
    echo "<div class='champ-wrapper' data-champ-id='{$r['CHAMP_ID']}' data-champ-src='{$fileName}'>";
    echo "  <img src='/images/champs/{$fileName}' title='{$name}' class='champ-icon'/>";
    // 여기 추가: 아이콘 아래에 이름 표시
    echo "  <div class='champ-label'>{$name}</div>";
    echo "</div>";
  } // ← 여기에 닫는 중괄호 있어야 합니다.

  oci_free_statement($stid);
?>
    </div>
</div> <!-- left-panel 닫기 추가 -->
    <!-- 우측 메타 관리 -->
    <div class="right-panel">
      <div class="top-bar">
        <button id="btn-new-meta">새 메타 만들기</button>
        <button id="btn-delete-meta" disabled>선택 메타 삭제</button>
      </div>
      <div class="meta-list" id="meta-list">
        <?php
        $sql  = "SELECT id AS META_ID, title AS META_NAME
           FROM meta_item
          ORDER BY display_order ASC";
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);

        while ($m = oci_fetch_assoc($stid)) {
          // ★ 반드시 $id, $name 변수를 여기서 정의해야 합니다
          $id   = $m['META_ID'];
          $name = htmlspecialchars($m['META_NAME'], ENT_QUOTES);

          echo "<div class='meta-item' data-meta-id='{$id}'>";
          echo "  <span class='meta-name'>{$name}</span>";
          echo "  <button class='edit-meta-btn' title='이름 수정'>✎</button>";
          echo "</div>";
        }

        oci_free_statement($stid);
        // ← 여기까지 대체
        ?>
      </div>
      <?php
      $sql = "SELECT id AS meta_id FROM meta_item ORDER BY id";
      $stid = oci_parse($conn, $sql); oci_execute($stid);
      while ($m = oci_fetch_assoc($stid)) {
        echo "<div class='meta-detail' id='meta-detail-{$m['META_ID']}' data-meta-id='{$m['META_ID']}'>";
        for ($i=1; $i<=10; $i++) {
          echo "<div class='drop-zone' data-slot='$i'><div class='remove-btn'>×</div></div>";
        }
        echo "</div>";
      }
      oci_free_statement($stid);
      ?>
    </div>
  </div>

  <script>
document.addEventListener('DOMContentLoaded', () => {
  let selectedMetaId = null;

  // 1) 메타 아이템 클릭 리스너
  const metaItems   = document.querySelectorAll('.meta-item');
  const metaDetails = document.querySelectorAll('.meta-detail');
  const btnDelete   = document.getElementById('btn-delete-meta');

  metaItems.forEach(item => {
    item.addEventListener('click', () => {
      // 선택 표시 초기화
      metaItems.forEach(x => x.classList.remove('selected'));
      metaDetails.forEach(x => x.classList.remove('active'));

      // 이 아이템 선택
      item.classList.add('selected');
      selectedMetaId = item.dataset.metaId;
      btnDelete.disabled = false;

      // 해당 상세 보여주기
      document.getElementById(`meta-detail-${selectedMetaId}`)
              .classList.add('active');

      loadExistingChampions(selectedMetaId);
    });
  });

  // 메타 이름 수정 버튼
document.querySelectorAll('.edit-meta-btn').forEach(btn => {
  btn.addEventListener('click', ev => {
    ev.stopPropagation(); // 리스트 클릭 이벤트 방지
    const item = btn.closest('.meta-item');
    const metaId = item.dataset.metaId;
    const oldName = item.querySelector('.meta-name').textContent;

    const newName = prompt('새 메타 이름을 입력하세요', oldName);
    if (!newName || newName.trim() === '' || newName === oldName) return;

    fetch('update_meta.php', {
      method: 'POST',
      headers: { 'Content-Type':'application/json' },
      body: JSON.stringify({
        action:  'rename',
        meta_id: metaId,
        new_name: newName.trim()
      })
    })
    .then(r => r.json())
    .then(json => {
      if (!json.success) {
        return alert('이름 수정 실패: ' + (json.error||''));
      }
      // 화면에도 즉시 반영
      item.querySelector('.meta-name').textContent = newName.trim();
    })
    .catch(_ => alert('서버 요청 실패'));
  });
});
  // 2) 챔피언 검색 필터
  document.getElementById('champ-search')
    .addEventListener('input', e => {
      const q = e.target.value.trim().toLowerCase();
      document.querySelectorAll('.champ-wrapper').forEach(w => {
        const name = w.querySelector('img').title.toLowerCase();
        w.style.display = name.includes(q) ? '' : 'none';
      });
    });

const sortable = Sortable.create(document.getElementById('meta-list'), {
  animation: 150,
  onEnd: () => {
    const order = Array.from(document.getElementById('meta-list').children)
      .map(x => x.dataset.metaId);
      
    fetch('update_meta.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    action: 'reorder',
    order: order
  })
})
.then(res => res.json())
.then(json => {
  if (!json.success) {
    alert('순서 저장에 실패했습니다: ' + (json.error || ''));
  }
})
.catch(err => {
  console.error(err);
  alert('서버 요청 중 오류가 발생했습니다.');
  });
    }
  });


  // 3) 새 메타 만들기
  document.getElementById('btn-new-meta')
    .addEventListener('click', () => {
      const name = prompt('새 메타 이름');
      if (!name) return alert('이름을 입력해야 합니다.');
      fetch('create_meta.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ meta_name:name })
      })
      .then(r=>r.json())
      .then(j=> j.success ? location.reload() : alert('생성 실패:'+j.error))
      .catch(_=>alert('서버 요청 실패'));
    });

  // 4) 메타 삭제
  btnDelete.addEventListener('click', () => {
    if (!selectedMetaId || !confirm('정말 삭제?')) return;
    fetch('delete_meta.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({ meta_id:selectedMetaId })
    })
    .then(r=>r.json())
    .then(j=> j.success ? location.reload() : alert('삭제 실패:'+j.error))
    .catch(_=>alert('서버 요청 실패'));
  });

  // 5) 챔피언 클릭 → 빈 슬롯에 추가
  document.querySelectorAll('.champ-wrapper').forEach(cw => {
    cw.addEventListener('click', () => {
      if (!selectedMetaId) return alert('메타 먼저 선택');

      // 데이터 속성엔 전체 경로가 담겨 있으니, 파일명만 분리
      const fullPath = cw.dataset.champSrc;            // "/images/champs/가렌.png"
      const fileName = fullPath.split('/').pop();      // "가렌.png"

      const detail = document.getElementById(`meta-detail-${selectedMetaId}`);
      const empty  = Array.from(detail.querySelectorAll('.drop-zone'))
                           .find(z => !z.querySelector('img'));
      if (!empty) return alert('빈 슬롯이 없습니다.');

      fetch('update_meta.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({
          action:    'add',
          meta_id:   selectedMetaId,
          champ_src: fileName   // 파일명만 서버에 보냄
        })
      })
      .then(r=>r.json())
      .then(j=>{
        if (j.success) {
          renderSlot(empty, fileName);
        } else {
          alert(j.error);
        }
      })
      .catch(_=>alert('서버 요청 실패'));
    });
  });

  // 6) 슬롯 렌더링 + 삭제 바인딩
  function renderSlot(zone, fileName) {
    const imgSrc = `/images/champs/${fileName}`;
    zone.innerHTML = `<img src="${imgSrc}"/><div class="remove-btn">×</div>`;

    // 삭제 버튼
    zone.querySelector('.remove-btn').addEventListener('click', ev => {
      ev.stopPropagation();
      fetch('update_meta.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({
          action:    'remove',
          meta_id:   selectedMetaId,
          champ_src: fileName
        })
      })
      .then(r=>r.json())
      .then(j=>{
        if (j.success) {
          zone.innerHTML = `<div class="remove-btn"></div>`;
        } else {
          alert(j.error);
        }
      })
      .catch(_=>alert('서버 요청 실패'));
    });
  }

  // 7) 기존 챔피언들 불러오기
  function loadExistingChampions(mid) {
        console.log('▶ loadExistingChampions 실행, meta_id =', mid);

  fetch('update_meta.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ action:'load', meta_id: mid })
  })
  .then(r => r.json())
  .then(j => {
    console.log('▶ loadExistingChampions 응답:', j);
    if (!j.success) return console.error('Load 실패:', j.error);

    // 드롭존 초기화
    const detail = document.getElementById(`meta-detail-${mid}`);
    detail.querySelectorAll('.drop-zone').forEach(z => {
      z.innerHTML = `<div class="remove-btn">×</div>`;
    });

    // 이미지 렌더링
    j.data.forEach((fileName, idx) => {
      console.log('  • slot', idx+1, '파일:', fileName);
      const zone = detail.querySelector(`.drop-zone[data-slot="${idx+1}"]`);
      if (zone) renderSlot(zone, fileName);
    });
  })
  .catch(e => console.error('▶ loadExistingChampions 네트워크 오류', e));
}
});
  const firstItem = document.querySelector('.meta-item');
  if (firstItem) {
    firstItem.click();
  }
</script>

</body>
</html>
