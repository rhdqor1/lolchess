<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("❌ 로그인 후 이용 가능합니다.");
}
?>

<?php include '../db_connect.php'; ?>
<link rel="stylesheet" href="style.css">
<div class="container">
  <h2>📝 글 작성</h2>
  <form method="post" action="post_store.php" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="제목" required><br><br>
    <textarea name="content" placeholder="내용" rows="10" cols="50" required></textarea><br><br>
    <input type="file" name="post_image" accept="image/*" onchange="previewImage(event)"><br><br>
    <img id="preview" width="400"><br><br>
    <!-- ✅ 관리자만 보이는 공지 등록 토글 -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
      <button type="button" onclick="toggleExtra()">📌 추가 옵션 보기</button><br><br>

      <div id="extra-options" style="display: none;">
        <label>
          <input type="checkbox" name="notice" value="Y"> 공지로 등록
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