<?php
session_start();
require_once 'db_connect.php';

$filter = $_GET['grade'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = [];
if ($filter === 'silver') {
  $where[] = "SUBSTR(ID, 1, 1) = '1'";
} elseif ($filter === 'gold') {
  $where[] = "SUBSTR(ID, 1, 1) = '2'";
} elseif ($filter === 'prism') {
  $where[] = "SUBSTR(ID, 1, 1) = '3'";
}
if (!empty($search)) {
  $where[] = "LOWER(NAME) LIKE '%' || LOWER(:search) || '%'";
}

$whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT * FROM AUGMENTS $whereClause ORDER BY ID";
$stid = oci_parse($conn, $sql);

if (!empty($search)) {
  oci_bind_by_name($stid, ':search', $search);
}
oci_execute($stid);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>증강체 목록</title>
  <style>
  body {
  background: #121212;
  color: #f0f0f0;
  font-family: sans-serif;
  padding: 0rem;
}

.tabs {
  margin-bottom: 1rem;
}

.tabs a {
  color: white;
  margin-right: 1rem;
  text-decoration: none;
  padding: 0.5rem 1rem;
  background: #333;
  border-radius: 5px;
}

.tabs a.active {
  background: #87CEFA;
  color: #000;
}

.search-form {
  margin-bottom: 1.5rem;
}

.search-form input[type="text"] {
  padding: 0.5rem;
  border-radius: 5px;
  border: none;
  width: 200px;
  margin-right: 0.5rem;
}

.search-form button {
  padding: 0.5rem 1rem;
  border: none;
  background: #87CEFA;
  color: black;
  border-radius: 5px;
  cursor: pointer;
}

.augment-list {
  margin-top: 1rem;
}

.augment-item {
  display: flex;
  align-items: center;
  background: #1e1e1e;
  margin-bottom: 1rem;
  padding: 1rem;
  border-radius: 10px;
}

.augment-item img {
  width: 48px;
  height: 48px;
  margin-right: 1rem;
}

.augment-info {
  display: flex;
  flex-direction: column;
}

.augment-name {
  font-size: 1.1rem;
  font-weight: bold;
  color: #87CEFA;
}
.page-container {
  margin: 2rem;
}
</style>
</head>
<body>

<?php include 'header.php'; ?>
<div class="page-container">
<div class="tabs">
  <a href="?grade=all" class="<?= $filter === 'all' ? 'active' : '' ?>">전체</a>
  <a href="?grade=silver" class="<?= $filter === 'silver' ? 'active' : '' ?>">실버</a>
  <a href="?grade=gold" class="<?= $filter === 'gold' ? 'active' : '' ?>">골드</a>
  <a href="?grade=prism" class="<?= $filter === 'prism' ? 'active' : '' ?>">프리즘</a>
</div>

<form method="get" class="search-form">
  <input type="hidden" name="grade" value="<?= htmlspecialchars($filter) ?>">
  <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="증강체 이름 검색">
  <button type="submit">검색</button>
</form>

<div class="augment-list">
  <?php while ($row = oci_fetch_assoc($stid)): ?>
    <div class="augment-item">
      <img src="/images/augments/<?= $row['ID'] ?>.png" alt="<?= htmlspecialchars($row['NAME']) ?>">
      <div class="augment-info">
        <div class="augment-name"><?= htmlspecialchars($row['NAME']) ?></div>
        <div class="augment-desc"><?= htmlspecialchars($row['DESCRIPTION']) ?></div>
      </div>
    </div>
  <?php endwhile; ?>
</div>
</div>
</body>
</html>

<?php
oci_free_statement($stid);
oci_close($conn);
?>
