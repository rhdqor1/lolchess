<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['user_id'];
    $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = $_POST['nickname'];
    $email = $_POST['email'];

    $sql = "INSERT INTO users (user_id, user_password, user_name, user_email, user_role, user_join_date)
            VALUES (:id, :pw, :name, :email, 'user', SYSDATE)";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $id);
    oci_bind_by_name($stid, ":pw", $pw);
    oci_bind_by_name($stid, ":name", $name);
    oci_bind_by_name($stid, ":email", $email);
    oci_execute($stid);
    oci_commit($conn);

    header("Location: login.php");
    exit;
}
?>
<link rel="stylesheet" href="style.css">
<div class="container">
    <h2>회원가입</h2>
    <form method="post">
        아이디: <input type="text" name="user_id" required>
        비밀번호: <input type="password" name="password" required>
        이름(닉네임): <input type="text" name="nickname" required>
        이메일: <input type="email" name="email" required>
        <input type="submit" value="가입하기">
    </form>
    <a href="login.php">로그인하기</a>
</div>