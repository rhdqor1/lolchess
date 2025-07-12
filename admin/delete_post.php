<?php
session_start();
include '../db_connect.php';
require_once '../delete_function.php';
$sql = "SELECT USER_ROLE FROM USERS WHERE USER_ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $_SESSION['user_id']);
oci_execute($stid);
$row = oci_fetch_assoc($stid);
if (!$row || $row['USER_ROLE'] !== 'admin') {
    die("❌ 관리자 권한이 없습니다.");
}
$id = $_GET['id'];
delete_fuc($id);
header("Location: manage_content.php");
exit;
?>