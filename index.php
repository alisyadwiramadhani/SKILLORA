
<?php
session_start();

if (!isset($_SESSION["ID_User"])) {
    header("Location: backend/login_form.php");
    exit;
}

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

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #176b5b;
            --primary-dark: #105447;
            --primary-light: #e5f4ef;
            --text: #263b36;
            --muted: #667873;
            --background: #f4f8f7;
            --white: #ffffff;
            --border: #e1ebe7;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
        }

        a {
            text-decoration: none;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: linear-gradient(
                110deg,
                var(--primary-dark),
                var(--primary)
            );
            color: white;
            padding: 17px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
            flex-wrap: wrap;
            box-shadow: 0 4px 18px rgba(16, 84, 71, 0.12);
        }

        .brand {
            color: white;
            font-size: 29px;
            font-weight: 800;
            letter-spacing: -1px;
            white-space: nowrap;
        }

        .nav-links {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #f0fffa;
            padding: 10px 13px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .logout {
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 10px;
            padding: 9px 15px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .logout:hover {
            background: white;
            color: var(--primary-dark);
        }

        main {
            max-width: 1240px;
            width: 90%;
            margin: 38px auto 55px;
        }

        .welcome {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(220px, 0.7fr);
            align-items: center;
            gap: 25px;
            background: linear-gradient(120deg, #e4f5ee, #ffffff);
            border: 1px solid #d6eae3;
            border-radius: 25px;
            padding: 48px;
            margin-bottom: 42px;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            display: inline-block;
            color: var(--primary);
            background: #d8eee5;
            padding: 7px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .welcome h1 {
            color: var(--primary-dark);
            font-size: clamp(30px, 4vw, 46px);
            line-height: 1.2;
            letter-spacing: -1px;
            margin-bottom: 14px;
            overflow-wrap: anywhere;
        }

        .subtitle {
            color: #42665a;
            font-size: clamp(17px, 2vw, 21px);
            margin-bottom: 15px;
        }

        .description {
            max-width: 650px;
            color: #52645e;
            font-size: 15px;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 27px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            background: var(--primary);
            color: white;
            padding: 12px 19px;
            border: 1px solid var(--primary);
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .button-outline {
            background: white;
            color: var(--primary);
            border-color: #cfe4da;
        }

        .button-outline:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        .hero-visual {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 240px;
        }

        .visual-card {
            width: min(100%, 280px);
            padding: 25px;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid #dcece5;
            border-radius: 24px;
            box-shadow: 0 15px 40px rgba(29, 88, 68, 0.09);
            transform: rotate(2deg);
        }

        .visual-icon {
            width: 66px;
            height: 66px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-light);
            border-radius: 19px;
            font-size: 34px;
            margin-bottom: 17px;
        }

        .visual-card h2 {
            color: var(--primary-dark);
            font-size: 21px;
            margin-bottom: 5px;
        }

        .visual-card p {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 18px;
        }

        .skill-line {
            height: 8px;
            background: #e5efea;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 10px;
        }

        .skill-line span {
            display: block;
            width: 78%;
            height: 100%;
            background: #249879;
            border-radius: inherit;
        }

        .section-heading {
            margin-bottom: 22px;
        }

        .section-heading h2 {
            font-size: 27px;
            color: #204a40;
            margin-bottom: 5px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 15px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 27px;
            min-height: 300px;
            box-shadow: 0 5px 20px rgba(30, 72, 60, 0.035);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 13px 30px rgba(30, 72, 60, 0.09);
        }

        .icon {
            width: 59px;
            height: 59px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-light);
            border-radius: 17px;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .card:nth-child(2) .icon {
            background: #e5f3f8;
        }

        .card:nth-child(3) .icon {
            background: #f0eafa;
        }

        .card h3 {
            font-size: 21px;
            color: #214a40;
            margin-bottom: 10px;
        }

        .card p {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 24px;
            flex-grow: 1;
        }

        .card .button {
            margin-top: auto;
        }

        footer {
            text-align: center;
            padding: 25px 20px;
            background: #eaf3f0;
            color: #657870;
            font-size: 13px;
        }

        @media (max-width: 950px) {
            .navbar {
                justify-content: center;
            }

            .welcome {
                grid-template-columns: 1fr;
                padding: 35px;
            }

            .hero-visual {
                min-height: auto;
                padding: 10px 0;
            }

            .visual-card {
                transform: none;
            }

            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 16px 4%;
                gap: 12px;
            }

            .brand {
                font-size: 27px;
            }

            .nav-links {
                width: 100%;
                gap: 4px;
            }

            .nav-links a {
                padding: 8px;
                font-size: 12px;
            }

            .logout {
                font-size: 12px;
                padding: 8px 12px;
            }

            main {
                width: 92%;
                margin-top: 23px;
            }

            .welcome {
                padding: 27px 21px;
                border-radius: 19px;
                margin-bottom: 32px;
            }

            .welcome h1 {
                font-size: 30px;
            }

            .description {
                font-size: 14px;
            }

            .hero-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .hero-actions .button {
                width: 100%;
            }

            .section-heading h2 {
                font-size: 23px;
            }

            .cards {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .card {
                min-height: 0;
                padding: 24px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            *,
            *::before,
            *::after {
                transition: none !important;
                animation: none !important;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <a class="brand" href="index.php">🌿 Skillora</a>

    <nav class="nav-links" aria-label="Navigasi utama">
        <a href="index.php">⌂ Beranda</a>
        <a href="backend/assesment/start.php">📝 Assessment</a>
        <a href="backend/assesment/hitung_skor_otomatis.php">📊 Hasil</a>
        <a href="backend/assesment/rekomendasi_karier.php">💼 Rekomendasi</a>
    </nav>

    <a class="logout" href="backend/logout.php">↪ Logout</a>
</header>

<main>
    <section class="welcome">
        <div class="welcome-content">
            <p class="eyebrow">Selamat datang di Skillora</p>

            <h1>Halo, <?= $nama ?>! 👋</h1>

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
                    ✨ Mulai Assessment <span>→</span>
                </a>

                <a class="button button-outline"
                   href="backend/assesment/rekomendasi_karier.php">
                    Lihat Rekomendasi
                </a>
            </div>
        </div>

        <div class="hero-visual" aria-label="Ilustrasi pengembangan skill">
            <div class="visual-card">
                <div class="visual-icon">🌱</div>
                <h2>Grow Your Skills</h2>
                <p>Kenali kemampuanmu dan kembangkan potensimu selangkah demi selangkah.</p>

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
        <p>Pilih fitur untuk mengenali potensimu dan menemukan arah karier.</p>
    </section>

    <section class="cards">
        <article class="card">
            <div class="icon">📝</div>
            <h3>Assessment</h3>
            <p>
                Jawab pertanyaan untuk mengenali profil keterampilan
                dan potensi yang kamu miliki.
            </p>
            <a class="button" href="backend/assesment/start.php">
                Mulai Assessment <span>→</span>
            </a>
        </article>

        <article class="card">
            <div class="icon">📊</div>
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

        <article class="card">
            <div class="icon">💼</div>
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
    </section>
</main>

<footer>
    &copy; <?= date("Y") ?> Skillora.
    Kenali potensimu, temukan arah kariermu.
</footer>

</body>
</html>
