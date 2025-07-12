<?php
session_start();
$id = $_GET['id'] ?? null;
if ($_SERVER["REQUEST_METHOD"] === "GET" && !$id) {
    die("❌ 잘못된 접근입니다.");
}
include '../db_connect.php';
?>
<link rel="stylesheet" href="../style.css">

<script>
const postId = <?= json_encode($id) ?>;
const session_user = "<?= $_SESSION['user_id'] ?? '' ?>";
const session_role = "<?= $_SESSION['user_role'] ?? '' ?>";

function loadComments(sort) {
  document.getElementById("comment-list").innerHTML = "불러오는 중...";
  fetch("../comment/fetch_comment_list.php?post_id=" + postId + "&sort=" + sort)
    .then(res => res.text())
    .then(html => {
      document.getElementById("comment-list").innerHTML = html;
    });
}

function likePost() {
  fetch("../comment/like_post_ajax.php", {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({ post_id: postId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) alert("추천 완료!");
    else alert(data.message);
  });
}

function likeComment(id) {
  fetch("../comment/like_comment_ajax.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ comment_id: id })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) loadComments("latest");
    else alert(data.message);
  });
}

function deleteComment(id) {
  if (!confirm("정말 삭제하시겠습니까?")) return;
  fetch("../comment/comment_delete_ajax.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) loadComments("latest");
    else alert(data.message);
  });
}

function editComment(id) {
  const content = prompt("댓글 수정:");
  if (!content) return;
  fetch("../comment/comment_update_ajax.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id, content })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) loadComments("latest");
    else alert(data.message);
  });
}

window.onload = () => {
  const urlParams = new URLSearchParams(location.search);
  const sort = urlParams.get("sort") || "latest";
  loadComments(sort);

  const form = document.getElementById("commentForm");
  if (form) {
    form.addEventListener("submit", function(e) {
      e.preventDefault();
      const content = this.querySelector("textarea").value;
      const post_id = this.querySelector("input[name='post_id']").value;

      fetch("../comment/comment_store_ajax.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ post_id, content })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          loadComments("latest");
          this.reset();
        } else {
          alert(data.message);
        }
      });
    });
  }
};
</script>



<div class="container">
  <a href="../index.php">← 뒤로가기</a><br><br>
  <?php
  $sql = "SELECT P.*, U.USER_NAME
FROM POST P
JOIN USERS U ON P.POST_USER_ID = U.USER_ID
WHERE P.POST_ID = :id";
  $stid = oci_parse($conn, $sql);
  oci_bind_by_name($stid, ":id", $id);
  oci_execute($stid);
  $post = oci_fetch_assoc($stid);
  if (!$post) die("해당 글이 존재하지 않습니다.");

  $update_sql = "UPDATE POST SET POST_VIEW_COUNT = NVL(POST_VIEW_COUNT, 0) + 1 WHERE POST_ID = :id";
$update_stmt = oci_parse($conn, $update_sql);
oci_bind_by_name($update_stmt, ":id", $id);
oci_execute($update_stmt);

$content = $post['POST_CONTENT'];
if ($content instanceof OCILob) $content = $content->load();

 
  $like_sql = "SELECT COUNT(*) AS LIKE_COUNT FROM POST_LIKE WHERE POST_ID = :id";
  $like_stmt = oci_parse($conn, $like_sql);
  oci_bind_by_name($like_stmt, ":id", $id);
  oci_execute($like_stmt);
  $like_row = oci_fetch_assoc($like_stmt);
  $like_count = $like_row['LIKE_COUNT'];
  ?>
  <h2><?= htmlspecialchars($post['POST_TITLE']) ?></h2>
<!-- 🔽 이미지 표시 코드 추가 -->
<?php if (!empty($post['POST_IMAGE'])): ?>
  <img src="<?= htmlspecialchars($post['POST_IMAGE']) ?>" style="max-width:100%;"><br><br>
<?php endif; ?>
<div class="post-content">
  <?= nl2br(htmlspecialchars($content)) ?>
</div>
  <p>작성자: <?= $post['USER_NAME'] ?> | 작성일: <?= $post['POST_CREATED_AT'] ?> | <?= $like_count ?>명 추천함 | 조회수: <?= $post['POST_VIEW_COUNT'] ?></p>

<?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $post['POST_USER_ID']): ?>
  <form method="POST" action="post_delete.php" onsubmit="return confirm('정말 삭제하시겠습니까?');">
    <input type="hidden" name="id" value="<?= $post['POST_ID'] ?>">
    <button type="submit">🗑 글 삭제</button>
  </form>
  <form method="GET" action="post_edit.php" style="display:inline;">
    <input type="hidden" name="id" value="<?= $post['POST_ID'] ?>">
    <button type="submit">✏ 글 수정</button>
  </form>
<?php endif; ?>

  <input type="hidden" name="post_id" value="<?= $post['POST_ID'] ?>">
<button onclick="likePost()">👍 추천</button>
</form>

  <h3>💬 댓글</h3>
<div>
  <button onclick="loadComments('latest')">🕒 최신순</button>
  <button onclick="loadComments('popular')">🔥 인기순</button>
  <div id="comment-list">불러오는 중...</div>
</div>




  <?php if (isset($_SESSION['user_id'])): ?>
    <form id="commentForm">
      <input type="hidden" name="post_id" value="<?= $id ?>">
      <textarea name="content" required></textarea>
      <button type="submit">댓글 작성</button>
    </form>

  <?php endif; ?>
</div>
