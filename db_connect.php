<?php
putenv("NLS_LANG=KOREAN_KOREA.UTF8");
$use_oracle = true;

if ($use_oracle) {
    $conn = oci_connect("dbuser212358", "ce1234", "//earth.gwangju.ac.kr:1521/orcl");
    if (!$conn) {
        $e = oci_error();
        die("Oracle DB 연결 실패: " . $e['message']);
    }
} else {
    $conn = null;
}
?>