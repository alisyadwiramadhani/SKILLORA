
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah pengguna sudah login
if (empty($_SESSION["ID_User"])) {
    header("Location: backend/login_form.php");
    exit;
}

// Ambil nama pengguna dari session
$nama = htmlspecialchars(
    $_SESSION["Nama"] ?? "Pengguna",
    ENT_QUOTES,
    "UTF-8"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#10594e">
    <title>Dashboard | Skillora</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">


</head>

<body>

<header class="navbar">
    <a class="brand" href="index.php">Skillora</a>

    <nav class="nav-links" aria-label="Navigasi utama">
        <a href="index.php">⌂ Beranda</a>
        <a href="backend/assesment/start.php">Assessment</a>
        <a href="backend/assesment/hitung_skor_otomatis.php">Hasil</a>
        <a href="backend/assesment/rekomendasi_karier.php">Rekomendasi</a>
    </nav>

    <a class="logout" href="backend/logout.php">↪ Logout</a>
</header>

<main>
    <section class="welcome">
        <div class="welcome-content">
            <p class="eyebrow">Selamat datang di Skillora</p>

            <h1>Halo, <?= $nama ?>!</h1>

            <p class="subtitle">
                Kenali potensimu, temukan arah kariermu.
            </p>

            <p class="description">
                Melalui assessment dan analisis keterampilan, Skillora
                membantu kamu mengenali potensi diri dan menemukan
                rekomendasi karier yang sesuai dengan hasil assessment-mu.
            </p>

            <div class="hero-actions">
                <a class="button" href="backend/assesment/start.php">
                    Mulai Assessment <span>→</span>
                </a>

                <a class="button button-outline"
                   href="backend/assesment/rekomendasi_karier.php">
                    Lihat Rekomendasi
                </a>
            </div>
        </div>

        <div class="hero-visual" aria-label="Ilustrasi pengembangan skill">
            <div class="visual-card">
                <div class="visual-icon"></div>
                <h2>Grow Your Skills</h2>
                <p>
                    Kenali kemampuanmu dan kembangkan potensimu
                    selangkah demi selangkah.
                </p>

                <strong style="font-size: 13px; color: #42665a;">
                    Pengembangan diri dimulai di sini
                </strong>

                <div class="skill-line">
                    <span></span>
                </div>
            </div>
        </div>
    </section>

    <section class="section-heading">
        <h2>Jelajahi Skillora</h2>
        <p>
            Pilih fitur untuk mengenali potensimu dan menemukan arah karier.
        </p>
    </section>

    <section class="cards">
        <article class="card">
            <div class="icon"></div>
            <h3>Assessment</h3>
            <p>
                Jawab pertanyaan untuk mengenali profil keterampilan
                dan potensi yang kamu miliki.
            </p>
            <a class="button" href="backend/assesment/start.php">
                Mulai Assessment <span>→</span>
            </a>
        </article>
        </article>

        <article class="card">
            <div class="icon"></div>
            <h3>Hasil Assessment</h3>
            <p>
                Lihat hasil pemetaan keterampilan berdasarkan
                jawaban assessment yang telah kamu kerjakan.
            </p>
            <a class="button"
               href="backend/assesment/hitung_skor_otomatis.php">
                Lihat Hasil <span>→</span>
            </a>
        </article>
        </article>

        <article class="card">
            <div class="icon"></div>
            <h3>Rekomendasi Karier</h3>
            <p>
                Jelajahi pilihan karier berdasarkan hasil
                dan skor keterampilanmu.
            </p>
            <a class="button"
               href="backend/assesment/rekomendasi_karier.php">
                Lihat Rekomendasi <span>→</span>
            </a>
        </article>
        </article>
    </section>
</main>

<footer>
    &copy; <?= date("Y") ?> Skillora.
    Kenali potensimu, temukan arah kariermu.
    &copy; <?= date("Y") ?> Skillora.
    Kenali potensimu, temukan arah kariermu.
</footer>

</body>
</html>