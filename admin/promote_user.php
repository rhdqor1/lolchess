<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("권한 없음");
}
include '../db_connect.php';
$id = $_GET['id'];
$sql = "UPDATE USERS SET USER_ROLE = 'admin' WHERE USER_ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $id);
oci_execute($stid);
oci_commit($conn);
header("Location: manage_users.php");
exit;
