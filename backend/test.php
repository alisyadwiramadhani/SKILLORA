<?php

require_once "../config/database.php";

$result = $conn->query("SELECT COUNT(*) AS total FROM T_User");

$data = $result->fetch_assoc();

echo "Backend Skillora aktif.<br>";
echo "Jumlah user: " . $data["total"];