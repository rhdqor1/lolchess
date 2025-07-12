<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['user_id'];
    $pw = $_POST['password'];

    $sql = "SELECT * FROM users WHERE user_id = :id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $id);
    oci_execute($stid);

    if ($row = oci_fetch_assoc($stid)) {
        if (password_verify($pw, $row['USER_PASSWORD'])) {
            $_SESSION['user_id'] = $row['USER_ID'];
            $_SESSION['user_name'] = $row['USER_NAME'];
            $_SESSION['user_role'] = $row['USER_ROLE'];
            header("Location: index.php");
            exit;
        } else {
            $error = "비밀번호가 틀렸습니다.";
        }
    } else {
        $error = "존재하지 않는 아이디입니다.";
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- 🔷 클릭 가능한 로고 -->
    <div class="logo-top">
        <a href="main.php">LoLCHESS.GG</a>
    </div>

    <!-- 🔐 로그인 박스 -->
    <div class="login-container">
        <h2>로그인</h2>
        <?php if (!empty($error)) echo "<p class='error-msg'>$error</p>"; ?>
        <form method="post">
            <input type="text" name="user_id" placeholder="아이디" required>
            <input type="password" name="password" placeholder="비밀번호" required>
            <input type="submit" value="로그인">
        </form>
        <a href="register.php">회원가입</a>
    </div>

</body>
</html>
