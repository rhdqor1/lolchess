<?php
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
?>
<style>
  body {
    margin: 0;
    background: #121212;
    color: #f0f0f0;
    font-family: Arial, sans-serif;
  }

  .header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #1a1a1a;
    padding: 1rem 2rem;
  }

  .logo {
  font-size: 24px;
  font-weight: bold;
  color: #87CEFA;
  text-decoration: none;
  transition: color 0.2s ease;
  display: flex;
  align-items: center;
  height: 100%;
  }
  
  .logo:hover {
  color: #ffffff;
  }


  .user-area {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
  }

  .welcome-text {
    font-size: 0.95rem;
    color: #ffffff;
  }

  .btn-red {
    background: #e54040;
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: background 0.2s ease;
  }

  .btn-red:hover {
    background: #ff5a5a;
  }

  .nav-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #1e1e1e;
    padding: 0.8rem 2rem;
    margin-bottom: 2rem;
  }

  .nav-links {
    display: flex;
    gap: 1.2rem;
  }

  .nav-links a {
    color: white;
    text-decoration: none;
    font-weight: 500;
  }

  .nav-links a:hover {
    color: #87CEFA;
  }

  .admin-link {
    color: #ffcc00;
    font-weight: bold;
    text-decoration: none;
  }
  
  nav {
  background: #1e1e1e;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1.2rem;
  padding: 1rem 0;
}

nav a {
  color: white;
  text-decoration: none;
  font-family: 'Noto Sans KR', 'Segoe UI', 'Malgun Gothic', sans-serif;
  font-weight: 500;
  font-size: 16px;
  letter-spacing: -0.3px;
}

nav a:hover {
  color: #87CEFA;
}



</style>

<!-- 줄 1: 로고 + 유저 -->
<header class="header-top">
  <a href="../main.php" class="logo">LoLCHESS.GG</a>
  <div class="user-area">
    <?php if (!$user_id): ?>
      <a href="../login.php" class="btn-red">로그인</a>
    <?php else: ?>
      <span class="welcome-text">환영합니다, <?= htmlspecialchars($user_name) ?>님</span>
      <a href="../my_page.php" class="btn-red">회원정보</a>
      <a href="../logout.php" class="btn-red">로그아웃</a>
    <?php endif; ?>
  </div>
</header>


<!-- 줄 2: 메뉴 + 관리자 페이지 -->
<nav class="nav-bar">
  <div class="nav-links">
    <a href="../detail.php">추천메타</a>
    <a href="../champions.php">챔피언</a>
    <a href="../synergy.php">시너지</a>
    <a href="../items.php">아이템</a>
    <a href="../augments.php">증강체</a>
    <a href="../index.php">커뮤니티</a>
  </div>
  <div class="admin-right">
    <?php if ($user_role === 'admin'): ?>
      <a href="../admin/dashboard.php" class="../admin-link">🔧관리자 페이지</a>
    <?php endif; ?>
  </div>
</nav>
