<?php
putenv("NLS_LANG=KOREAN_KOREA.UTF8");
$use_oracle = true;

if ($use_oracle) {
   $conn = oci_connect("your_username", "your_password", "localhost/XE");
    if (!$conn) {
        $e = oci_error();
        die("Oracle DB 연결 실패: " . $e['message']);
    }
} else {
    $conn = null;
}
?>
