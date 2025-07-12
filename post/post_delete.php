<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("❌ 로그인 후 이용 가능합니다.");
}
?>

<?php
include '../db_connect.php';
require_once '../delete_function.php';
$id = $_POST['id'] ?? $_GET['id'] ?? null;
if (!$id) die("❌ 잘못된 요청입니다.");
delete_fuc($id);
header("Location: ../index.php");
exit;
?>
