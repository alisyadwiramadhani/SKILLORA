<?php

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama = $_POST["nama"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO T_User (Nama, Email, Password)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("sss", $nama, $email, $password_hash);

    if ($stmt->execute()) {
        echo "Registrasi berhasil!";
    } else {
        echo "Registrasi gagal: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>