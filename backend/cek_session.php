<?php

session_start();

if (isset($_SESSION["ID_User"])) {

    echo "Session aktif!<br>";
    echo "ID User: " . $_SESSION["ID_User"] . "<br>";
    echo "Nama: " . $_SESSION["Nama"] . "<br>";
    echo "Email: " . $_SESSION["Email"];

} else {
    echo "Session belum aktif.";
}