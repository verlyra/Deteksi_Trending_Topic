<?php
// 2311501601 Verly Rahma Aulia

$host = "localhost";
$user = "root"; 
$pass = ""; 
$dbName = "twitter";
$conn = mysqli_connect($host, $user, $pass);


if (!$conn) {
    die("Koneksi MySql Gagal !!<br>" . mysqli_connect_error());
}
//echo "<h6>Koneksi MySql Berhasil !!<br></h6>";

$sql = mysqli_select_db($conn, $dbName);
if (!$sql) {
    die ("Koneksi Database Gagal !!".mysqli_error($conn));
}
//echo ("<h6>Koneksi Database Berhasil !!</h6>");
?>
