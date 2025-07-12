<?php
session_start();
include '../db_connect.php';

// ID 생성: 빈 자리 우선
function getNextId($conn, $prefix) {
    $min = $prefix;
    $max = $prefix + 999;
    $sql = "
        SELECT MIN(a.id + 1) AS NEXT_ID
        FROM AUGMENTS a
        WHERE a.id + 1 NOT IN (SELECT id FROM AUGMENTS)
        AND a.id BETWEEN :min AND :max
    ";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":min", $min);
    oci_bind_by_name($stmt, ":max", $max);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    return $row['NEXT_ID'] ?? $min;
}

// 추가 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['desc']);
    $grade = $_POST['grade'];
    $grade_prefix = ['silver' => 1000, 'gold' => 2000, 'pris' => 3000][$grade] ?? 1000;
    $id = getNextId($conn, $grade_prefix);

    if (empty($name) || empty($desc) || empty($_FILES['image']['name']) || empty($grade)) {
        die("❌ 모든 필드를 입력하세요.");
    }

    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/images/Augments";
    $filename = $id . ".png";
    $tmp = $_FILES['image']['tmp_name'];
    $path = "images/Augments/" . $filename;
	$absolute_path = $upload_dir . "/" . $filename;
    if (!move_uploaded_file($tmp, $absolute_path)) {
        die("❌ 이미지 업로드 실패");
    }

    $sql = "INSERT INTO AUGMENTS (ID, NAME, DESCRIPTION, IMAGE_PATH)
            VALUES (:id, :name, :description, :path)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $id);
    oci_bind_by_name($stmt, ":name", $name);
    oci_bind_by_name($stmt, ":description", $desc);
    oci_bind_by_name($stmt, ":path", $path);

    if (oci_execute($stmt)) {
        oci_commit($conn);
        echo "<script>alert('✅ 등록 완료!'); location.href='manage_augment.php';</script>";
        exit;
    } else {
        $e = oci_error($stmt);
        echo "❌ DB 저장 실패: " . $e['message'];
    }
}

// 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $image_path = $_POST['delete_image_path'] ?? null;

    if (!empty($image_path)) {
		$upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/images/Augments/";
		$filename = basename($image_path);
        $absolute_path = $upload_dir . $filename;
        if (file_exists($absolute_path)) {
            unlink($absolute_path);
        }
    }

    $sql = "DELETE FROM AUGMENTS WHERE ID = :id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $id);
    oci_execute($stid);
    oci_commit($conn);

    echo "<script>alert('🗑 삭제 완료'); location.href='manage_augment.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>증강체 관리</title>
  <link rel="stylesheet" href="/tt/style.css">
</head>
<body>
<a href="dashboard.php" style="color: white;">← 관리자 대시보드로</a>
<div class="container">
  <h2>🧬 증강체 생성</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="이름" required><br><br>
    <textarea name="desc" placeholder="설명" rows="5" required></textarea><br><br>
    <input type="file" name="image" accept="image/png" required><br><br>

    <label><input type="radio" name="grade" value="silver" checked> 실버</label>
    <label><input type="radio" name="grade" value="gold"> 골드</label>
    <label><input type="radio" name="grade" value="pris"> 프리즘</label><br><br>

    <button type="submit">➕ 추가하기</button>
  </form>

  <hr>
  <h2>📋 등록된 증강체 목록</h2>

  <form method="get" style="margin-bottom: 20px;">
  <input type="text" name="search" placeholder="증강체 검색" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
  <button type="submit">🔍 검색</button>
</form>

<h3>📂 등급별 보기</h3>
  <label><input type="radio" name="filter_grade" value="all" checked> 전체</label>
  <label><input type="radio" name="filter_grade" value="silver"> 실버</label>
  <label><input type="radio" name="filter_grade" value="gold"> 골드</label>
  <label><input type="radio" name="filter_grade" value="pris"> 프리즘</label>
  <br><br>

  <?php
  
$search = $_GET['search'] ?? '';
if ($search) {
  $sql = "SELECT * FROM AUGMENTS WHERE NAME LIKE :term ORDER BY ID ASC";
  $stid = oci_parse($conn, $sql);
  $term = '%' . $search . '%';
  oci_bind_by_name($stid, ":term", $term);
} else {
  $sql = "SELECT * FROM AUGMENTS ORDER BY ID ASC";
  $stid = oci_parse($conn, $sql);
}

  oci_execute($stid);
  while ($row = oci_fetch_assoc($stid)):
    $grade_type = '';
    if ($row['ID'] >= 1000 && $row['ID'] < 2000) $grade_type = 'silver';
    elseif ($row['ID'] >= 2000 && $row['ID'] < 3000) $grade_type = 'gold';
    elseif ($row['ID'] >= 3000 && $row['ID'] < 4000) $grade_type = 'pris';
  ?>
    <div class="augment-box" data-grade="<?= $grade_type ?>" style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
      <strong><?= htmlspecialchars($row['NAME']) ?></strong><br>
      <?= htmlspecialchars($row['DESCRIPTION']) ?><br>
      <img src="/images/Augments/<?php echo $row['ID']; ?>.png" style="max-height:100px;"><br>
      ID: <?= $row['ID'] ?><br>
      <form method="post" onsubmit="return confirm('정말 삭제하시겠습니까?');">
        <input type="hidden" name="delete_id" value="<?= $row['ID'] ?>">
        <input type="hidden" name="delete_image_path" value="<?= $row['IMAGE_PATH'] ?>">
        <button type="submit">🗑 삭제</button>
      </form>
    </div>
  <?php endwhile; ?>
</div>

<script>
  const radios = document.querySelectorAll('input[name="filter_grade"]');
  radios.forEach(radio => {
    radio.addEventListener('change', () => {
      const selected = radio.value;
      document.querySelectorAll('.augment-box').forEach(box => {
        const grade = box.dataset.grade;
        box.style.display = (selected === 'all' || grade === selected) ? "block" : "none";
      });
    });
  });
</script>
</body>
</html>
