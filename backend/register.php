<?php

session_start();

require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register_form.php");
    exit;
}

$nama = trim($_POST["nama"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($nama === "" || $email === "" || $password === "") {
    exit("Nama, email, dan password wajib diisi.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Format email tidak valid.");
}

if (strlen($password) < 8) {
    exit("Password harus terdiri dari minimal 8 karakter.");
}

try {
    // Periksa apakah email sudah terdaftar
    $sql = "SELECT ID_User FROM t_user WHERE Email = :email LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([":email" => $email]);

    if ($stmt->fetch()) {
        exit("Email sudah terdaftar. Silakan gunakan email lain atau login.");
    }

    // Simpan pengguna baru dengan password yang di-hash
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO t_user (Nama, Email, Password)
            VALUES (:nama, :email, :password)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":nama" => $nama,
        ":email" => $email,
        ":password" => $password_hash
    ]);

    header("Location: login_form.php?registered=1");
    exit;

} catch (PDOException $e) {
    error_log("Registrasi Skillora gagal: " . $e->getMessage());
    http_response_code(500);
    exit("Registrasi gagal. Periksa kembali data dan struktur database.");
}
