<?php

session_start();

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM T_User WHERE Email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["Password"])) {

    $_SESSION["ID_User"] = $user["ID_User"];
    $_SESSION["Nama"] = $user["Nama"];
    $_SESSION["Email"] = $user["Email"];

    header("Location: ../index.php");
exit;
    } 
    else {echo "Password salah.";}
    } 
    else {

        echo "Email tidak ditemukan.";

    }

    $stmt->close();
}

$conn->close();
?>