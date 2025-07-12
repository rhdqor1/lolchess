<?php
require_once '../db_connect.php';
session_start();

$id = $_GET['id'] ?? '';
if (!$id) die('잘못된 접근입니다');

$sql = "SELECT * FROM POST WHERE POST_ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':id', $id);
oci_execute($stid);
$post = oci_fetch_assoc($stid);
if (!$post) die('해당 글이 존재하지 않습니다.');

$content = $post['POST_CONTENT'];
if ($content instanceof OCILob) {
    $content = $content->load();
}
?>

<link rel="stylesheet" href="style.css">
<div class="container">
  <h2>📝 글 작성</h2>
<form action="post_update.php" method="post" enctype="multipart/form-data">
  <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
    <input type="text" name="title" placeholder="제목" value="<?= htmlspecialchars($post['POST_TITLE']) ?>" required><br><br>
	<textarea name="content" placeholder="내용" rows="10" cols="50" required><?= htmlspecialchars($content) ?></textarea><br><br>
    <input type="file" name="post_image" accept="image/*" onchange="previewImage(event)"><br><br>
	<?php if (!empty($post['POST_IMAGE'])): ?>
    <p>사진 미리보기:</p>
    <img id="preview" src="<?= htmlspecialchars($post['POST_IMAGE']) ?>" width="400"><br><br>
     <?php endif; ?>
    <!-- ✅ 관리자만 보이는 공지 등록 토글 -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
      <button type="button" onclick="toggleExtra()">📌 추가 옵션 보기</button><br><br>

      <div id="extra-options" style="display: none;">
        <label>
          <input type="checkbox" name="notice" value="Y" <?= $post['IS_NOTICE'] === 'Y' ? 'checked' : '' ?>> 공지로 등록
        </label><br><br>
      </div>
    <?php endif; ?>
    <button type="submit">작성하기</button>
  </form>
</div>
<script>
function toggleExtra() {
  const box = document.getElementById("extra-options");
  box.style.display = (box.style.display === "none") ? "block" : "none";
}
function previewImage(event) {
  const input = event.target;
  const preview = document.getElementById("preview");

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>