<?php
session_start();

if (isset($_SESSION["ID_User"])) {
    echo "Session aktif!";
} else {
    echo "Session belum aktif.";
}
