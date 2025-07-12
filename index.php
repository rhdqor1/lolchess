<?php
session_start();
include 'db_connect.php';
?>
 <?php include 'header.php'; ?>
<link rel="stylesheet" href="style.css">
<div class="container">
  <h2>📚 글 목록</h2>

  <button onclick="loadPosts('latest')">🕒 최신순</button>
  <button onclick="loadPosts('popular')">🔥 인기순</button><br><br>

  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="post/post_create.php">➕ 글 작성</a><br>
    <a href="logout.php">🚪 로그아웃</a><br><br>
    <a href="my_page.php">👤 마이페이지</a><br><br>
  <?php else: ?>
    <a href="login.php">🔑 로그인</a><br><br>
  <?php endif; ?>

  <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <a href="admin/dashboard.php">🔧 관리자 페이지</a><br><br>
  <?php endif; ?>

<form id="searchForm" style="margin-bottom: 20px;">
  <input type="text" name="keyword" placeholder="검색어 입력">
  <select name="type">
    <option value="title">제목</option>
    <option value="title_content">제목+내용</option>
    <option value="author">작성자</option>
  </select>
  <button type="submit">검색</button>
</form>


  <div id="post-list">불러오는 중...</div>
</div>

<script>
function loadPosts(sort) {
  history.replaceState({sort}, "", "?sort=" + sort);
  fetch("fetch_post_list.php?sort=" + sort)
    .then(res => res.text())
    .then(html => {
      document.getElementById("post-list").innerHTML = html;
    });
}

window.onload = () => {
  const urlParams = new URLSearchParams(location.search);
  const sort = urlParams.get("sort") || "latest";
  loadPosts(sort);
};

window.onpopstate = (event) => {
  const sort = (event.state && event.state.sort) || "latest";
  loadPosts(sort);
};
document.getElementById("searchForm").addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const keyword = formData.get("keyword");
  const type = formData.get("type");

  fetch(`fetch_post_list.php?keyword=${encodeURIComponent(keyword)}&type=${type}`)
    .then(res => res.text())
    .then(html => {
      document.getElementById("post-list").innerHTML = html;
    });
});
</script>
