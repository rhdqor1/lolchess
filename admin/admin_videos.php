<?php
// ─────────────────────────────────────────────────────────────────
// admin_videos.php
//  - 이 파일 하나로 영상 등록(INSERT), 삭제(DELETE), 목록 조회(LIST)를 처리합니다.
//  - 같은 폴더에 style.css가 있어야 스타일이 적용됩니다.
//  - 경로: C:\xampp\htdocs\hjs\팀플\videos\admin_videos.php
// ─────────────────────────────────────────────────────────────────

// 1) Oracle DB 연결 (환경에 맞게 수정하세요)
$conn = oci_connect("dbuser212358", "ce1234", "earth.gwangju.ac.kr/orcl");
if (!$conn) {
    $e = oci_error();
    die("<div class=\"error-msg\">DB 연결 실패: " . htmlspecialchars($e['message'], ENT_QUOTES, "UTF-8") . "</div>");
}

// 2) 메시지 초기화
$msg      = "";
$msg_type = ""; // "success" 또는 "error" 구분

// 3) 삭제 요청 처리 (GET 파라미터 delete_id)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id > 0) {
        $sql_del   = "DELETE FROM videos WHERE ID = :id";
        $stid_del  = oci_parse($conn, $sql_del);
        oci_bind_by_name($stid_del, ":id", $delete_id);
        if (!oci_execute($stid_del)) {
            $e        = oci_error($stid_del);
            $msg      = "영상 삭제 중 오류가 발생했습니다: " . htmlspecialchars($e['message'], ENT_QUOTES, "UTF-8");
            $msg_type = "error";
        } else {
            $msg      = "영상이 성공적으로 삭제되었습니다.";
            $msg_type = "success";
        }
        oci_free_statement($stid_del);
    }
}

// 4) 등록 요청 처리 (POST로 title, youtube_url이 넘어오면 INSERT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['youtube_url'])) {
    $title       = trim($_POST['title']);
    $youtube_url = trim($_POST['youtube_url']);

    if ($title === "" || $youtube_url === "") {
        $msg      = "제목과 YouTube URL을 모두 입력해주세요.";
        $msg_type = "error";
    } else {
        $sql_ins  = "INSERT INTO videos (TITLE, YOUTUBE_URL) VALUES (:title, :url)";
        $stid_ins = oci_parse($conn, $sql_ins);
        oci_bind_by_name($stid_ins, ":title", $title);
        oci_bind_by_name($stid_ins, ":url",   $youtube_url);

        if (!oci_execute($stid_ins)) {
            $e        = oci_error($stid_ins);
            $msg      = "영상 등록 중 오류가 발생했습니다: " . htmlspecialchars($e['message'], ENT_QUOTES, "UTF-8");
            $msg_type = "error";
        } else {
            $msg      = "영상이 성공적으로 등록되었습니다.";
            $msg_type = "success";
        }
        oci_free_statement($stid_ins);
    }
}

// 5) DB에서 전체 영상 목록 조회 (최신순)
$sql_list = "SELECT ID, TITLE, YOUTUBE_URL FROM videos ORDER BY CREATED_AT DESC";
$stid_list = oci_parse($conn, $sql_list);
oci_execute($stid_list);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
<a href="dashboard.php">← 관리자 대시보드로</a>
  <title>영상 관리자</title>
  <!-- style.css를 같은 폴더에 두었을 때 참조 경로 -->
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <h1>영상 관리자</h1>

  <!-- 6) 메시지 출력 -->
  <?php if ($msg !== ""): ?>
    <?php if ($msg_type === "success"): ?>
      <div class="msg"><?= htmlspecialchars($msg, ENT_QUOTES, "UTF-8") ?></div>
    <?php else: ?>
      <div class="error-msg"><?= htmlspecialchars($msg, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- 7) 영상 등록 폼 -->
  <h2>영상 추가</h2>
  <form action="admin_videos.php" method="post">
    <label for="title">제목:</label>
    <input type="text" id="title" name="title" placeholder="영상 제목을 입력하세요." required>

    <label for="youtube_url">YouTube URL:</label>
    <input type="text" id="youtube_url" name="youtube_url"
           placeholder="예: https://youtu.be/VIDEO_ID 또는 https://www.youtube.com/watch?v=VIDEO_ID"
           required>

    <input type="submit" value="등록">
  </form>

  <!-- 8) 등록된 영상 목록 출력 -->
  <h2>등록된 영상 목록</h2>
  <div class="video-grid">
    <?php while ($row = oci_fetch_array($stid_list, OCI_ASSOC+OCI_RETURN_NULLS)): ?>
      <?php
        $id       = $row['ID'];
        $title    = $row['TITLE'];
        $clob     = $row['YOUTUBE_URL'];

        // CLOB → PHP 문자열 변환
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
        // ID 뒤 파라미터 제거
        if ($video_id !== "") {
            $video_id = preg_replace("/[^A-Za-z0-9_\-]/", "", $video_id);
        }
        // 최종 embed URL
        $embed_url = ($video_id !== "")
                   ? "https://www.youtube.com/embed/" . $video_id
                   : "";
      ?>

      <div class="video-item">
        <!-- 영상 제목 -->
        <h3><?= htmlspecialchars($title, ENT_QUOTES, "UTF-8") ?></h3>

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

        <!-- 삭제 버튼 (delete_id 파라미터로 삭제 요청) -->
        <a class="delete-btn"
           href="admin_videos.php?delete_id=<?= $id ?>"
           onclick="return confirm('정말 삭제하시겠습니까?');">
          삭제
        </a>
      </div>
    <?php endwhile; ?>
  </div>

  <?php
  // 9) 자원 해제
  oci_free_statement($stid_list);
  oci_close($conn);
  ?>
</body>
</html>
