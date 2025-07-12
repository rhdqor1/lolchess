<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("권한 없음");
}
include '../db_connect.php';
$id = $_GET['id'];
$sql = "UPDATE USERS SET USER_ROLE = 'user' WHERE USER_ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $id);
oci_execute($stid);
oci_commit($conn);

if ($_SESSION['user_id'] === $id) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

header("Location: manage_users.php");
exit;
