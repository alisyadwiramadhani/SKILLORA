<?php

session_start();

require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        exit("Email dan password wajib diisi.");
    }

    try {
        $sql = "SELECT * FROM T_User WHERE Email = :email LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ":email" => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            if (password_verify($password, $user["Password"])) {

                session_regenerate_id(true);

                $_SESSION["ID_User"] = $user["ID_User"];
                $_SESSION["Nama"] = $user["Nama"];
                $_SESSION["Email"] = $user["Email"];

                header("Location: ../index.php");
                exit;

            } else {
    exit("Email atau password salah.");
}

} else {
    exit("Email atau password salah.");
}

    } catch (PDOException $e) {
        error_log("Login Skillora gagal: " . $e->getMessage());
        http_response_code(500);
        exit("Terjadi kesalahan saat login. Periksa konfigurasi database.");
    }

} else {
    header("Location: login_form.php");
    exit;
}
