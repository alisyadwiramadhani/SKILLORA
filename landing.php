
<?php
session_start();

$sudahLogin = isset($_SESSION["ID_User"]);

$linkLogin = "backend/login_form.php";
$linkDaftar = "backend/register_form.php";
$linkDashboard = "index.php";

$linkMulai = $sudahLogin ? $linkDashboard : $linkLogin;

// Ganti dengan nama file maskot yang kamu simpan di folder assets.
$maskot = "assets/maskot_landing.png";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Skillora — Kenali Potensimu, Temukan Kariermu</title>
    <meta name="description" content="Kenali minat dan keterampilanmu, lalu temukan rekomendasi karier bersama Skillora.">

    <style>
        :root {
            --teal: #568b8c;
            --teal-dark: #278d8d;
            --teal-light: #dce9e8;
            --text: #292d2d;
            --muted: #526b6d;
            --white: #ffffff;
            --cream: #f3e6cf;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--text);
            background: var(--white);
            line-height: 1.5;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .container {
            width: min(1400px, 94%);
            margin: 0 auto;
        }

        /* NAVBAR */
       .navbar {
    margin: 15px auto 0;
    width: calc(100% - 20px);
    min-height: 130px;
    border: none;
    border-radius: 16px;
    background: #fff;
}

        .nav-inner {
            min-height: 128px;
            padding: 20px 3.5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-shrink: 0;
        }

        .brand img {
            width: 82px;
            height: 82px;
            object-fit: contain;
        }

        .brand-name {
            color: #347C79 !important;
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -1px;
            white-space: nowrap;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: clamp(18px, 2.5vw, 42px);
        }

        .nav-link {
            font-size: 19px;
            white-space: nowrap;
            transition: color 0.2s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--teal-dark);
        }

        .nav-button {
            min-width: 115px;
            padding: 10px 25px;
            border: 1.3px solid var(--teal);
            border-radius: 28px;
            text-align: center;
            font-size: 18px;
            transition: 0.2s ease;
        }

        .nav-button.login {
            color: var(--teal-dark);
            background: #fff;
        }

        .nav-button.register {
            color: #fff;
            background: var(--teal);
        }

        .nav-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px #568b8c20;
        }

        /* HERO */
        .hero {
            padding: 60px 0 10px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 35px;
        }

        .hero-content {
            padding: 0 0 0 3.5%;
        }

        .welcome-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            padding: 6px 17px;
            border-radius: 25px;
            background: #d6e2e1;
            color: var(--teal-dark);
            font-size: 17px;
        }

        .hero h1 {
            max-width: 690px;
            margin-bottom: 24px;
            color: var(--teal-dark);
            font-size: clamp(42px, 4.7vw, 68px);
            line-height: 1.2;
            font-weight: 750;
            letter-spacing: -1.5px;
        }

        .hero-description {
            max-width: 570px;
            margin-bottom: 25px;
            color: #168d91;
            font-size: clamp(19px, 2vw, 26px);
            line-height: 1.35;
        }

        .primary-button {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            min-width: 245px;
            padding: 11px 22px;
            border-radius: 18px;
            color: #fff;
            background: var(--teal);
            font-size: 18px;
            transition: 0.2s ease;
        }

        .primary-button:hover {
            background: #427879;
            transform: translateY(-2px);
        }

        .button-arrow {
            font-size: 30px;
            line-height: 1;
        }

        /* MASKOT */
        .hero-visual {
            position: relative;
            min-width: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 490px;
        }

        .mascot {
            display: block;
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 550px;
            height: 500px;
            object-fit: contain;
            filter: drop-shadow(0 12px 10px #244e4b12);
            animation: mascot-float 4s ease-in-out infinite;
        }

        .decoration {
            position: absolute;
            z-index: 0;
            color: var(--teal);
            font-weight: 700;
            user-select: none;
        }

        .decoration.chart {
            top: 12%;
            left: 5%;
            font-size: 66px;
        }

        .decoration.bulb {
            top: 1%;
            right: 12%;
            font-size: 58px;
        }

        .decoration.people {
            left: 0;
            top: 48%;
            font-size: 49px;
        }

        .decoration.briefcase {
            right: 1%;
            bottom: 20%;
            font-size: 55px;
        }

        .decoration.star {
            left: 5%;
            top: 34%;
            font-size: 30px;
        }

        @keyframes mascot-float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        /* KEUNGGULAN */
        /* KEUNGGULAN SKILLORA */
.benefits {
    padding: 20px 0 55px;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 24px;
    align-items: center;
}

.benefit {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.benefit-icon {
    width: 46px;
    height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 13px;
    background: #eaf5f3;
    color: #347c79;
}

.benefit-icon svg {
    display: block;
    width: 23px;
    height: 23px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.benefit h3 {
    margin-bottom: 4px;
    color: #234b49;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.4;
}

.benefit p {
    color: #647775;
    font-size: 12px;
    line-height: 1.55;
}

        /* SECTION INFORMASI */
        .info-section {
            padding: 65px 0;
            background: #f7fbfa;
        }

        .section-heading {
            max-width: 650px;
            margin: 0 auto 35px;
            text-align: center;
        }

        .section-heading h2 {
            margin-bottom: 12px;
            color: var(--teal-dark);
            font-size: clamp(28px, 3vw, 38px);
        }

        .section-heading p {
            color: var(--muted);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .feature-card {
            padding: 28px 24px;
            border: 1px solid #e1ece9;
            border-radius: 18px;
            background: #fff;
        }

        .feature-card .emoji {
            display: block;
            margin-bottom: 12px;
            font-size: 34px;
        }

        .feature-card h3 {
            margin-bottom: 8px;
            color: var(--teal-dark);
        }

        .feature-card p {
            color: var(--muted);
            font-size: 14px;
        }

        /* FOOTER */
        footer {
            padding: 25px 15px;
            color: var(--muted);
            background: #fff;
            text-align: center;
            font-size: 13px;
        }

        /* RESPONSIVE */
       @media (max-width: 1050px) {
    .benefits-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
    }

    .feature-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .benefits-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px 14px;
    }

    .benefit {
        align-items: flex-start;
        gap: 9px;
    }

    .benefit-icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
    }

    .benefit-icon svg {
        width: 21px;
        height: 21px;
    }

    .benefit h3 {
        font-size: 12px;
    }

    .benefit p {
        font-size: 11px;
    }

    .info-section {
        padding: 42px 0;
    }

    .feature-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .feature-card {
        padding: 22px 20px;
    }
}

@media (max-width: 390px) {
    .benefits-grid {
        grid-template-columns: 1fr;
    }

    .benefit h3 {
        font-size: 13px;
    }

    .benefit p {
        font-size: 12px;
    }
}

            /* IKON KEUNGGULAN */
            .benefit-icon {
                width: 58px;
                height: 58px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                border-radius: 16px;
                background: #EAF5F3;
                color: #347C79;
            }

            .benefit-icon svg {
                width: 27px;
                height: 27px;
                stroke: currentColor;
                fill: none;
                stroke-width: 1.8;
                stroke-linecap: round;
                stroke-linejoin: round;
            }

            /* KARTU FITUR */
            .feature-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 22px;
            }

            .feature-card {
                padding: 25px 23px;
                border: 1px solid #e2eeeb;
                border-radius: 16px;
                background: #ffffff;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .feature-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 22px rgba(52, 124, 121, 0.08);
            }

            .feature-icon {
                width: 44px;
                height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 16px;
                border-radius: 12px;
                background: #eaf5f3;
                color: #347c79;
            }

            .feature-icon svg {
                display: block;
                width: 22px;
                height: 22px;
                stroke: currentColor;
                fill: none;
                stroke-width: 1.7;
                stroke-linecap: round;
                stroke-linejoin: round;
            }

            .feature-card h3 {
                margin-bottom: 8px;
                color: #234b49;
                font-size: 16px;
                font-weight: 600;
            }

            .feature-card p {
                color: #647775;
                font-size: 13px;
                line-height: 1.7;
            }
    </style>
</head>

<body>

<!-- NAVBAR -->
<header class="navbar">
    <div class="nav-inner">

        <a href="landing.php" class="brand" aria-label="Skillora Beranda">
            <!-- Ganti path ini jika nama file logo berbeda -->
            <img src="assets/Logo.png"
                 alt="Logo Skillora"
                 onerror="this.style.display='none'">

            <span class="brand-name">Skillora</span>
        </a>

        <nav class="nav-menu" aria-label="Navigasi utama">
            <a href="#beranda" class="nav-link active">Beranda</a>
            <a href="#tentang" class="nav-link">Tentang</a>
            <a href="#cara-kerja" class="nav-link">Cara Kerja</a>
            <a href="#karier" class="nav-link">Karier</a>

            <?php if ($sudahLogin): ?>
                <a href="<?= $linkDashboard ?>" class="nav-button login">
                    Dashboard
                </a>
                <a href="backend/logout.php" class="nav-button register">
                    Keluar
                </a>
            <?php else: ?>
                <a href="<?= $linkLogin ?>" class="nav-button login">
                    Masuk
                </a>
                <a href="<?= $linkDaftar ?>" class="nav-button register">
                    Daftar
                </a>
            <?php endif; ?>
        </nav>

    </div>
</header>

<main>

    <!-- HERO -->
    <section class="hero" id="beranda">
        <div class="container hero-grid">

            <div class="hero-content">
                <div class="welcome-pill">
                    ✨ Selamat datang di Skillora
                </div>

                <h1>
                    Kenali Potensimu,<br>
                    Temukan Karier<br>
                    yang tepat
                </h1>

                <p class="hero-description">
                    Skillora membantu kamu menemukan kecocokan antara
                    minat, keterampilan, dan peluang karier di masa depan.
                </p>

                <a href="<?= $linkMulai ?>" class="primary-button">
                    <span>Mulai Assessment</span>
                    <span class="button-arrow" aria-hidden="true">→</span>
                </a>
            </div>

            <div class="hero-visual">
                <span class="decoration chart" aria-hidden="true">↗</span>
                <span class="decoration bulb" aria-hidden="true">♟</span>
                <span class="decoration people" aria-hidden="true">♟</span>
                <span class="decoration briefcase" aria-hidden="true">▣</span>
                <span class="decoration star" aria-hidden="true">☆</span>

                <!-- MASKOT UTAMA -->
                <img
                    class="mascot"
                    src="<?= htmlspecialchars($maskot, ENT_QUOTES, 'UTF-8') ?>"
                    alt="Maskot pinguin Skillora"
                    onerror="this.onerror=null; this.style.display='none'; document.getElementById('mascot-error').style.display='block';"
                >

                <p id="mascot-error"
                   style="display:none; color:#568b8c; text-align:center;">
                    Gambar maskot tidak ditemukan.<br>
                    Periksa nama file di folder assets.
                </p>
            </div>

        </div>
    </section>

    
<!-- KEUNGGULAN SKILLORA -->
<section class="benefits" aria-label="Keunggulan Skillora">
    <div class="container benefits-grid">

        <article class="benefit">
            <div class="benefit-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 12l6-6"/>
                    <path d="M15 6h3v3"/>
                </svg>
            </div>
            <div>
                <h3>Kenali Minatmu</h3>
                <p>Temukan bidang yang sesuai dengan ketertarikanmu.</p>
            </div>
        </article>

        <article class="benefit">
            <div class="benefit-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 20V12"/>
                    <path d="M10 20V5"/>
                    <path d="M16 20V9"/>
                    <path d="M22 20V3"/>
                    <path d="M2 20h21"/>
                </svg>
            </div>
            <div>
                <h3>Pahami Keterampilan</h3>
                <p>Kenali kemampuan dan potensi terbaikmu.</p>
            </div>
        </article>

        <article class="benefit">
            <div class="benefit-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="7" width="18" height="14" rx="2"/>
                    <path d="M8 7V4h8v3"/>
                    <path d="M3 12h18"/>
                    <path d="M10 12v2h4v-2"/>
            </svg>
            </div>
            <div>
                <h3>Temukan Karier</h3>
                <p>Jelajahi pilihan karier yang relevan.</p>
            </div>
        </article>

        <article class="benefit">
            <div class="benefit-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 17l6-6 4 3 7-8"/>
                    <path d="M14 6h6v6"/>
                    <path d="M4 21h16"/>
            </svg>
            </div>
            <div>
                <h3>Raih Masa Depan</h3>
                <p>Susun langkah menuju karier impianmu.</p>
            </div>
        </article>
    </div>
</section>


    <!-- TENTANG SKILLORA -->
    <section class="info-section" id="tentang">
        <div class="container">
            <div class="section-heading">
                <h2>Temukan Potensi Terbaikmu</h2>
                <p>
                    Kenali kemampuan diri untuk membantu mengeksplorasi
                    pilihan karier yang lebih sesuai.
                </p>
            </div>

            
<div class="feature-grid">
    <article class="feature-card">
        <div class="feature-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="4" y="3" width="16" height="18" rx="2"/>
                <path d="M8 8h8"/>
                <path d="M8 12h8"/>
                <path d="M8 16h5"/>
            </svg>
        </div>
        <h3>Assessment Keterampilan</h3>
        <p>
            Jawab pertanyaan untuk mengenali profil
            keterampilan dan potensi dirimu.
        </p>
    </article>

    <article class="feature-card">
        <div class="feature-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 19V5"/>
                <path d="M4 19h17"/>
                <rect x="7" y="12" width="3" height="4" rx="0.5"/>
                <rect x="13" y="8" width="3" height="8" rx="0.5"/>
                <rect x="19" y="4" width="2" height="12" rx="0.5"/>
            </svg>
        </div>
        <h3>Hasil yang Terarah</h3>
        <p>
            Pahami hasil assessment untuk melihat
            kekuatan dan area pengembanganmu.
        </p>
    </article>

    <article class="feature-card" id="karier">
        <div class="feature-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
                <circle cx="12" cy="12" r="5"/>
                <circle cx="12" cy="12" r="1.5"/>
                <path d="M13 11l6-6"/>
                <path d="M15 5h4v4"/>
        </svg>
        </div>
        <h3>Rekomendasi Karier</h3>
        <p>
            Eksplorasi pilihan karier yang sesuai
            dengan profil keterampilanmu.
        </p>
    </article>
</div>

        </div>
    </section>

    <!-- CARA KERJA -->
    <section class="info-section" id="cara-kerja">
        <div class="container">
            <div class="section-heading">
                <h2>Mulai dalam Tiga Langkah</h2>
                <p>
                    Perjalanan mengenali potensi dan pilihan karier
                    dimulai dari langkah sederhana.
                </p>
            </div>

            
<div class="feature-grid">
    <article class="feature-card">
        <div class="feature-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
        </svg>
        </div>
        <h3>01. Masuk atau Daftar</h3>
        <p>Buat akun untuk mulai menggunakan Skillora.</p>
    </article>

    <article class="feature-card">
        <div class="feature-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M14 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9z"/>
                <path d="M14 3v7h7"/>
                <path d="M8 14h8"/>
                <path d="M8 18h6"/>
        </svg>
        </div>
        <h3>02. Kerjakan Assessment</h3>
        <p>Jawab pertanyaan untuk memetakan keterampilanmu.</p>
    </article>

    <article class="feature-card">
        <div class="feature-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <path d="M8 12l3 3 5-6"/>
        </svg>
        </div>
        <h3>03. Lihat Rekomendasi</h3>
        <p>Pelajari hasil dan pilihan karier yang tersedia.</p>
    </article>
</div>

        </div>
    </section>

</main>

<footer>
    &copy; <?= date("Y") ?> Skillora. Kenali potensimu, temukan arah kariermu.
</footer>

</body>
</html>
