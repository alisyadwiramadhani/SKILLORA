<?php
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION["ID_User"])) {
    header("Location: backend/login_form.php");
    exit;
}

$nama = htmlspecialchars($_SESSION["Nama"] ?? "Pengguna", ENT_QUOTES, "UTF-8");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Skillora</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            color: #263b36;
        }

        header {
            background: #176b5b;
            color: white;
            padding: 20px 7%;
        }

        header h1 {
            margin: 0;
            font-size: 26px;
        }

        header p {
            margin-bottom: 0;
        }

        main {
            max-width: 1000px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .welcome, .card {
            background: white;
            padding: 25px;
            border-radius: 14px;
            margin-bottom: 22px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
        }

        .welcome h2 {
            margin-top: 0;
            color: #176b5b;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card h3 {
            margin-top: 0;
        }

        .button {
            display: inline-block;
            background: #176b5b;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 10px;
        }

        .button:hover {
            background: #105447;
        }

        .logout {
            display: inline-block;
            color: white;
            margin-top: 12px;
        }

        footer {
            text-align: center;
            color: #65756f;
            padding: 25px;
        }
    </style>
</head>

<body>

<header>
    <h1>Skillora</h1>
    <p>Kenali potensimu, temukan arah kariermu.</p>
    <a class="logout" href="backend/logout.php">Logout</a>
</header>

<main>
    <section class="welcome">
        <h2>Halo, <?= $nama ?>! 👋</h2>
        <p>Selamat datang di Skillora.</p>
        <p>
            Kenali keterampilanmu melalui assessment dan temukan
            rekomendasi karier yang sesuai dengan potensimu.
        </p>
    </section>

    <section class="cards">
        <div class="card">
            <h3>📝 Assessment</h3>
            <p>Jawab pertanyaan untuk mengetahui profil keterampilanmu.</p>
            <a class="button" href="backend/assesment/start.php">
                Mulai Assessment
            </a>
        </div>

        <div class="card">
            <h3>📊 Hasil Assessment</h3>
            <p>Lihat hasil pemetaan keterampilanmu setelah assessment selesai.</p>
            <a class="button" href="backend/assesment/hitung_skor_otomatis.php">
                Lihat Hasil
            </a>
        </div>

        <div class="card">
            <h3>💼 Rekomendasi Karier</h3>
            <p>Temukan pilihan karier berdasarkan skor keterampilanmu.</p>
            <a class="button" href="backend/assesment/rekomendasi_karier.php">
                Lihat Rekomendasi
            </a>
        </div>
    </section>
</main>

<footer>
    &copy; <?= date("Y") ?> Skillora
</footer>

</body>
</html>