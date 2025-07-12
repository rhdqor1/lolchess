<?php
require_once '../db_connect.php';

// 시너지 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];

    // 이미지 연동 삭제
    $sql = "DELETE FROM SYNERGY_CHAMPION WHERE SYNERGY_ID = :id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $id);
    oci_execute($stid);

    // 시너지 삭제
    $sql = "DELETE FROM SYNERGY WHERE ID = :id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $id);
    oci_execute($stid);

    oci_commit($conn);
    echo "<script>alert('🗑 삭제 완료'); location.href='manage_synergy.php';</script>";
    exit;
}

// 시너지 추가 또는 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['description'])) {
    $name = trim($_POST['name']);
    $desc = $_POST['description'];
    $tier = $_POST['tier'] ?? null;
    $champs = $_POST['champ_images'] ?? [];

    if (isset($_POST['edit_id']) && $_POST['edit_id'] !== '') {
        // 수정 처리
        $edit_id = $_POST['edit_id'];

        $sql = "UPDATE SYNERGY SET NAME = :name, DESCRIPTION = :description, TIER = :tier WHERE ID = :id";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":name", $name);
        oci_bind_by_name($stid, ":description", $desc);
        oci_bind_by_name($stid, ":tier", $tier);
        oci_bind_by_name($stid, ":id", $edit_id);
        oci_execute($stid);

        $sql = "DELETE FROM SYNERGY_CHAMPION WHERE SYNERGY_ID = :id";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":id", $edit_id);
        oci_execute($stid);

        foreach ($champs as $champ) {
            $champ = trim($champ);
            if ($champ !== '') {
                $sql = "INSERT INTO SYNERGY_CHAMPION (SYNERGY_ID, CHAMP_ID) VALUES (:sid, :cid)";
                $stid = oci_parse($conn, $sql);
                oci_bind_by_name($stid, ":sid", $edit_id);
                oci_bind_by_name($stid, ":cid", $champ);
                oci_execute($stid);
            }
        }

        oci_commit($conn);
        echo "<script>alert('✅ 수정 완료'); location.href='manage_synergy.php';</script>";
        exit;
    } else {
        // 이름 중복 확인
        $check_sql = "SELECT COUNT(*) AS CNT FROM SYNERGY WHERE NAME = :name";
        $check_stid = oci_parse($conn, $check_sql);
        oci_bind_by_name($check_stid, ":name", $name);
        oci_execute($check_stid);
        $row = oci_fetch_assoc($check_stid);
        if ($row['CNT'] > 0) {
            echo "<script>alert('⚠ 같은 이름의 시너지가 이미 존재합니다.'); location.href='manage_synergy.php';</script>";
            exit;
        }

        $sql = "SELECT SYNERGY_SEQ.NEXTVAL AS NEW_ID FROM DUAL";
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);
        $row = oci_fetch_assoc($stid);
        $newId = $row['NEW_ID'];

        $sql = "INSERT INTO SYNERGY (ID, NAME, DESCRIPTION, TIER)
                VALUES (:id, :name, :description, :tier)";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":id", $newId);
        oci_bind_by_name($stid, ":name", $name);
        oci_bind_by_name($stid, ":description", $desc);
        oci_bind_by_name($stid, ":tier", $tier);
        oci_execute($stid);

        if (!empty($champs)) {
            foreach ($champs as $champName) {
                $champName = trim($champName);
                if ($champName !== '') {
                    $sqlChamp = "INSERT INTO SYNERGY_CHAMPION (SYNERGY_ID, CHAMP_ID) VALUES (:sid, :cid)";
                    $stidChamp = oci_parse($conn, $sqlChamp);
                    oci_bind_by_name($stidChamp, ":sid", $newId);
                    oci_bind_by_name($stidChamp, ":cid", $champName);
                    oci_execute($stidChamp);
                }
            }
        }

        oci_commit($conn);
        echo "<script>alert('✅ 시너지 추가 완료'); location.href='manage_synergy.php';</script>";
        exit;
    }
}

$sql = "SELECT S.*, C.CHAMP_ID
        FROM SYNERGY S
        LEFT JOIN SYNERGY_CHAMPION C ON S.ID = C.SYNERGY_ID
        ORDER BY S.ID";

$stid = oci_parse($conn, $sql);
oci_execute($stid);
$synergies = [];
while (($row = oci_fetch_assoc($stid)) !== false) {
    $sid = $row['ID'];
    if (!isset($synergies[$sid])) {
        $synergies[$sid] = [
            'ID' => $sid,
            'NAME' => $row['NAME'],
            'DESCRIPTION' => $row['DESCRIPTION'],
            'TIER' => $row['TIER'],
            'CHAMPS' => []
        ];
    }
    if (!empty($row['CHAMP_ID'])) {
        $synergies[$sid]['CHAMPS'][] = $row['CHAMP_ID'];
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>시너지 관리</title>
  <link rel="stylesheet" href="../main.css">
</head>
<body>
<div class="container">
<a href="dashboard.php" style="color: white;">← 관리자 대시보드로</a>
  <h2>시너지 관리</h2>

<form method="POST" id="synergy-form" style="margin-bottom: 2rem;">
  <input type="hidden" name="edit_id" id="edit-id">
  <input type="text" name="name" id="input-name" placeholder="시너지 이름" required style="width: 200px;">
  <br><br>
  <textarea name="description" id="input-desc" placeholder="설명" required style="width: 400px; height: 160px;"></textarea>
  <br><br>
  <textarea name="tier" id="input-tier" placeholder="티어 설명" style="width: 400px; height: 80px;"></textarea>
  <br><br>

  <label>챔피언 이미지 이름 입력:</label>
  <div id="image-inputs">
    <input type="text" name="champ_images[]" placeholder="예: 니코" required>
  </div>
  <button type="button" onclick="addImageInput()">➕ 이미지 추가</button>
  <br><br>

  <button type="submit" id="form-submit-btn">➕ 추가</button>
</form>

  <table border="1" cellpadding="8">
    <thead>
      <tr>
        <th>ID</th>
        <th>이름</th>
        <th>설명</th>
        <th>티어</th>
        <th>이미지</th>
        <th>수정</th>
        <th>삭제</th>
      </tr>
    </thead>
    <tbody>
<?php $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/images/champs"; ?>
<?php foreach ($synergies as $sid => $syn): ?>
<?php $champImgs = isset($syn['CHAMPS']) ? $syn['CHAMPS'] : []; ?>
<tr>
  <td><?= htmlspecialchars($syn['ID']) ?></td>
  <td><?= htmlspecialchars($syn['NAME']) ?></td>
  <td style="white-space: pre-wrap;"><?= htmlspecialchars($syn['DESCRIPTION'] ?? '') ?></td>
  <td><?= htmlspecialchars($syn['TIER'] ?? '') ?></td>
  <td>
    <?php if (!empty($champImgs)): ?>
      <?php foreach ($champImgs as $img): ?>
        <?php $img_path = $upload_dir . '/' . $img . '.png'; ?>
        <?php if (file_exists($img_path)): ?>
        <img src="/images/champs/<?= urlencode($img) ?>.png" alt="<?= htmlspecialchars($img) ?>" style="width:30px;height:30px;border-radius:5px;">
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </td>
  <td>
    <button type="button"
      onclick='editSynergy(
        <?= json_encode($syn["ID"]) ?>,
        <?= json_encode($syn["NAME"]) ?>,
        <?= json_encode($syn["DESCRIPTION"]) ?>,
        <?= json_encode($syn["TIER"]) ?>,
        <?= json_encode($champImgs) ?>
      )'>✏ 수정</button>
  </td>
  <td>
    <form method="POST" onsubmit="return confirm('정말 삭제할까요?');">
      <input type="hidden" name="delete_id" value="<?= $syn['ID'] ?>">
      <button type="submit">🗑 삭제</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
function addImageInput(value = '') {
  const container = document.getElementById('image-inputs');
  const input = document.createElement('input');
  input.type = 'text';
  input.name = 'champ_images[]';
  input.placeholder = '예: 니코';
  input.required = true;
  input.value = value;
  container.appendChild(document.createElement('br'));
  container.appendChild(input);
}

function editSynergy(id, name, desc, tier, champs) {
  document.getElementById('edit-id').value = id;
  document.getElementById('input-name').value = name;
  document.getElementById('input-desc').value = desc;
  document.getElementById('input-tier').value = tier || '';

  const container = document.getElementById('image-inputs');
  container.innerHTML = '';
  champs.forEach(function(champ) {
    addImageInput(champ);
  });

  document.getElementById('form-submit-btn').innerText = '✏ 수정';
}
</script>
</body>
</html>
