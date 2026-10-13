
<?php
$host = "localhost";
$dbname = "skillora";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi database gagal.");
}

$conn->set_charset("utf8mb4");