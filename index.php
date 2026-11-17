
<?php
session_start();

require_once __DIR__ . "/config/database.php";

// ======================================
// 1. VALIDASI LOGIN
// ======================================
if (empty($_SESSION["ID_User"])) {
    header("Location: backend/login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

$nama = $_SESSION["Nama"] ?? "Mahasiswa";
$inisial = strtoupper(substr(trim($nama), 0, 1));

$profile = [
    "Nama" => $nama,
    "Email" => "",
    "Universitas" => "",
    "Jurusan" => "",
    "Semester" => ""
];

$totalAssessment = 0;
$totalSelesai = 0;
$totalSkill = 0;
$totalRekomendasi = 0;

$latestAssessment = null;
$skillCategories = [];
$topCareers = [];
$totalPertanyaan = 0;
$totalTerjawab = 0;
$dashboardError = false;

try {

    // ======================================
    // 2. DATA PROFIL PENGGUNA
    // ======================================
    $stmt = $conn->prepare("
        SELECT
            u.Nama,
            u.Email,
            p.Universitas,
            p.Jurusan,
            p.Semester

        FROM T_User u

        LEFT JOIN T_Profile p
            ON p.ID_User = u.ID_User

        WHERE u.ID_User = :id_user

        LIMIT 1
    ");

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $dataProfile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dataProfile) {
        $profile = array_merge($profile, $dataProfile);

        $nama = $dataProfile["Nama"];
        $inisial = strtoupper(
            substr(trim($nama), 0, 1)
        );

        $_SESSION["Nama"] = $nama;
    }

    // ======================================
    // 3. STATISTIK ASSESSMENT
    // ======================================
    $stmt = $conn->prepare("
        SELECT
            COUNT(*) AS total_assessment,

            SUM(
                CASE
                    WHEN Status = 'Completed'
                    THEN 1
                    ELSE 0
                END
            ) AS total_selesai

        FROM T_Assessment

        WHERE ID_User = :id_user
    ");

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $statAssessment = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalAssessment = (int) (
        $statAssessment["total_assessment"] ?? 0
    );

    $totalSelesai = (int) (
        $statAssessment["total_selesai"] ?? 0
    );

    // ======================================
    // 4. JUMLAH SKILL
    // ======================================
    $totalSkill = (int) $conn->query("
        SELECT COUNT(*) FROM T_Skill
    ")->fetchColumn();

    // ======================================
    // 5. ASSESSMENT TERBARU
    // ======================================
    $stmt = $conn->prepare("
        SELECT
            ID_Assessment,
            Tanggal,
            Status,
            Started_at,
            Completed_at

        FROM T_Assessment

        WHERE ID_User = :id_user

        ORDER BY ID_Assessment DESC

        LIMIT 1
    ");

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $latestAssessment = $stmt->fetch(PDO::FETCH_ASSOC);

    // ======================================
    // 6. PROGRESS JAWABAN TERBARU
    // ======================================
    if ($latestAssessment) {

        $id_latest = (int) $latestAssessment["ID_Assessment"];

        // SIMPAN ID ASSESSMENT KE SESSION
        $_SESSION["ID_Assessment"] = $id_latest;

        $stmt = $conn->prepare("
            SELECT COUNT(*)

            FROM T_Assessment_Question

            WHERE ID_Assessment = :id_assessment
        ");

        $stmt->execute([
            ":id_assessment" => $id_latest
        ]);

        $totalPertanyaan = (int) $stmt->fetchColumn();

        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT a.ID_Assessment_Question)

            FROM T_Assessment_Answer a

            INNER JOIN T_Assessment_Question q
                ON q.ID_Assessment_Question =
                   a.ID_Assessment_Question

                AND q.ID_Assessment = a.ID_Assessment

            INNER JOIN T_Question_Option o
                ON o.ID_Option = a.ID_Option

                AND o.ID_Assessment_Question =
                    a.ID_Assessment_Question

            WHERE a.ID_Assessment = :id_assessment
        ");

        $stmt->execute([
            ":id_assessment" => $id_latest
        ]);

        $totalTerjawab = (int) $stmt->fetchColumn();
    }

    // ======================================
    // 7. HASIL ASSESSMENT TERBARU
    // ======================================
    $completedId = 0;

    $stmt = $conn->prepare("
        SELECT a.ID_Assessment

        FROM T_Assessment a

        INNER JOIN T_Hasil_Assessment h
            ON h.ID_Assessment = a.ID_Assessment

        WHERE a.ID_User = :id_user
          AND a.Status = 'Completed'

        ORDER BY a.ID_Assessment DESC

        LIMIT 1
    ");

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $completedId = (int) $stmt->fetchColumn();

    // ======================================
    // 8. RINGKASAN KETERAMPILAN
    // ======================================
    if ($completedId > 0) {

        $stmt = $conn->prepare("
            SELECT
                s.Kategori,
                SUM(h.Score) AS Total_Score

            FROM T_Hasil_Assessment h

            INNER JOIN T_Skill s
                ON s.ID_Skill = h.ID_Skill

            WHERE h.ID_Assessment = :id_assessment

            GROUP BY s.Kategori

            ORDER BY Total_Score DESC
        ");

        $stmt->execute([
            ":id_assessment" => $completedId
        ]);

        $skillCategories = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ======================================
    // 9. REKOMENDASI KARIER
    // ======================================
    if ($completedId > 0) {

        $stmt = $conn->prepare("
            SELECT COUNT(*)

            FROM T_Rekomendasi_Karier

            WHERE ID_Assessment = :id_assessment
        ");

        $stmt->execute([
            ":id_assessment" => $completedId
        ]);

        $totalRekomendasi = (int) $stmt->fetchColumn();

        $stmt = $conn->prepare("
            SELECT
                k.ID_Karir,
                k.Nama_Karier,
                k.Bidang,
                k.Deskripsi,
                r.Match_Score,
                r.Ranking

            FROM T_Rekomendasi_Karier r

            INNER JOIN T_Karir k
                ON k.ID_Karir = r.ID_Karir

            WHERE r.ID_Assessment = :id_assessment

            ORDER BY r.Ranking ASC

            LIMIT 3
        ");

        $stmt->execute([
            ":id_assessment" => $completedId
        ]);

        $topCareers = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

} catch (Throwable $e) {

    error_log(
        "Dashboard Skillora: " . $e->getMessage()
    );

    $dashboardError = true;
}

// ======================================
// 10. PERSENTASE PROGRESS
// ======================================
$progress = 0;

if ($totalPertanyaan > 0) {
    $progress = round(
        ($totalTerjawab / $totalPertanyaan) * 100
    );
}

$progress = max(0, min(100, $progress));

$maxSkillScore = 0;

foreach ($skillCategories as $skill) {
    $maxSkillScore = max(
        $maxSkillScore,
        (float) $skill["Total_Score"]
    );
}

$maxSkillScore = max(1, $maxSkillScore);

// ======================================
// 11. VIDEO PEMBELAJARAN
// ======================================
$videos = [
    [
        "category" => "DESAIN PRODUK",
        "title" => "UI/UX Design Fundamental",
        "description" => "Pelajari user research, wireframing, prototyping, dan dasar pengalaman pengguna.",
        "duration" => "Materi UI/UX",
        "rating" => "Sumber belajar",
        "search" => "UI UX design fundamental tutorial Indonesia"
    ],
    [
        "category" => "DATA & ANALYTICS",
        "title" => "Data Analytics Essentials",
        "description" => "Kenali dasar analisis data, SQL, visualisasi, dan pengambilan keputusan.",
        "duration" => "Materi Data",
        "rating" => "Sumber belajar",
        "search" => "Data analytics SQL Python tutorial Indonesia"
    ],
    [
        "category" => "WEB DEVELOPMENT",
        "title" => "Modern Web Development",
        "description" => "Pelajari HTML, CSS, JavaScript, dan konsep pengembangan website modern.",
        "duration" => "Materi Web",
        "rating" => "Sumber belajar",
        "search" => "HTML CSS JavaScript web development tutorial Indonesia"
    ]
];

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta name="theme-color" content="#f5faf8">

<title>Dashboard | SKILLORA</title>

<link rel="stylesheet" href="assets/css/global.css">

<link rel="stylesheet" href="assets/css/dashboard.css">

</head>

<body>

<!-- =====================================
     NAVBAR
====================================== -->

<header class="navbar">

    <a href="index.php" class="brand">
        SKILLORA
    </a>

    <nav class="nav-links">

        <a href="index.php" class="active">
            <span>⌂</span> Beranda
        </a>

        <a href="backend/assesment/start.php">
            <span>▣</span> Assessment
        </a>

        <a href="backend/assesment/rekomendasi_karier.php">
            <span>◎</span> Rekomendasi Karier
        </a>

        <a href="#notifikasi">
            <span>♧</span> Notifikasi
        </a>

    </nav>

    <form class="search-box"
          action="backend/assesment/rekomendasi_karier.php"
          method="get">

        <span>⌕</span>

        <input
            type="search"
            name="q"
            placeholder="Cari karier, skill, atau pelatihan..."
            aria-label="Cari karier, skill, atau pelatihan"
        >

    </form>

    <div class="user-area">

        <a href="backend/profil.php" class="user-profile">

            <span class="avatar">
                <?= e($inisial) ?>
            </span>

            <span class="user-text">
                <strong><?= e($nama) ?></strong>
                <small>Mahasiswa</small>
            </span>

        </a>

        <a href="backend/logout.php"
           class="logout"
           title="Keluar dari akun">

            ↪

        </a>

    </div>

</header>


<main class="dashboard-container">

<!-- =====================================
     HERO SECTION
====================================== -->

<section class="hero">

    <div class="hero-content">

        <div class="welcome-label">
            <span class="dot"></span>
            Selamat Datang di Skillora
        </div>

        <h1>
            Selamat Datang Kembali,
            <?= e(explode(" ", trim($nama))[0]) ?>!
        </h1>

        <p class="hero-description">
            Kenali potensimu, kembangkan keterampilanmu,
            dan temukan arah karier terbaikmu melalui
            asesmen komprehensif terarah bersama Skillora.
        </p>

        <div class="hero-actions">

            <a href="backend/assesment/start.php"
               class="btn-primary">

                Mulai Assessment
                <span>→</span>

            </a>

            <a href="backend/profil.php"
               class="btn-white">

                Lihat Profil Saya

            </a>

        </div>

    </div>

    <div class="hero-image">

        <img
            src="assets/images/maskot_landing.png"
            alt="Maskot Skillora"
        >

    </div>

</section>


<!-- =====================================
     STATISTIK
====================================== -->

<section class="stats-grid">

    <article class="stat-card">

        <div class="stat-top">
            <span class="stat-label">ASSESSMENT</span>
            <span class="stat-icon">◉</span>
        </div>

        <div class="stat-number">
            <?= $totalSelesai ?>

            <small>
                / <?= $totalAssessment ?> Selesai
            </small>
        </div>

        <p>
            Total assessment yang sudah diselesaikan
        </p>

    </article>


    <article class="stat-card">

        <div class="stat-top">
            <span class="stat-label">SKILL TERPETAKAN</span>
            <span class="stat-icon">↗</span>
        </div>

        <div class="stat-number">
            <?= $totalSkill ?>
        </div>

        <p>
            Keterampilan yang tersedia dalam Skillora
        </p>

    </article>


    <article class="stat-card">

        <div class="stat-top">
            <span class="stat-label">REKOMENDASI KARIER</span>
            <span class="stat-icon">▥</span>
        </div>

        <div class="stat-number">
            <?= $totalRekomendasi ?>
        </div>

        <p>
            Rekomendasi dari assessment terakhir
        </p>

    </article>

</section>


<!-- =====================================
     MAIN CONTENT
====================================== -->

<section class="content-grid">

    <div class="left-column">

        <!-- PROGRESS ASSESSMENT -->

        <article class="panel progress-panel" id="notifikasi">

            <div class="panel-heading">

                <div>
                    <h2>Progress Pengembangan Diri</h2>

                    <p>
                        Pantau perkembangan assessment-mu.
                    </p>
                </div>

                <span class="level-badge">
                    <?= $latestAssessment
                        ? e($latestAssessment["Status"])
                        : "Belum Memulai" ?>
                </span>

            </div>


            <?php if ($latestAssessment): ?>

                <div class="progress-info">

                    <strong>
                        Progress Assessment Terakhir
                    </strong>

                    <strong class="progress-percent">
                        <?= $progress ?>%
                    </strong>

                </div>

                <div class="progress-track">

                    <div
                        class="progress-fill"
                        style="width: <?= $progress ?>%">
                    </div>

                </div>

                <div class="progress-caption">

                    <span>
                        <?= $totalTerjawab ?>
                        dari
                        <?= $totalPertanyaan ?>
                        pertanyaan terjawab
                    </span>

                    <span>
                        <?= e($latestAssessment["Status"]) ?>
                    </span>

                </div>

                <div class="information-box">

                    <span class="info-icon">i</span>

                    <div>

                        <?php if (
                            $latestAssessment["Status"] === "Completed"
                        ): ?>

                            <strong>
                                Assessment berhasil diselesaikan!
                            </strong>

                            <p>
                                Kamu dapat melihat hasil pemetaan
                                keterampilan dan rekomendasi karier.
                            </p>

                        <?php else: ?>

                            <strong>
                                Lanjutkan assessment-mu.
                            </strong>

                            <p>
                                Selesaikan seluruh pertanyaan agar
                                Skillora dapat menampilkan hasil
                                pemetaan keterampilan.
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="progress-footer">

                    <span>
                        Assessment terakhir:
                        <strong>
                            <?= e($latestAssessment["Tanggal"] ?? "-") ?>
                        </strong>
                    </span>

                    <?php if (
                        $latestAssessment["Status"] === "Completed"
                    ): ?>

                        <a href="backend/hasil.php">
                            Lihat Hasil →

                        </a>

                    <?php else: ?>

                        <a href="backend/assesment/kerjakan.php">
                            Lanjutkan Assessment →

                        </a>

                    <?php endif; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <span class="empty-icon">◎</span>

                    <h3>Belum ada assessment</h3>

                    <p>
                        Mulai assessment pertamamu untuk mengetahui
                        keterampilan dan potensi yang kamu miliki.
                    </p>

                    <a href="backend/assesment/start.php"
                       class="btn-primary">

                        Mulai Sekarang →

                    </a>

                </div>

            <?php endif; ?>

        </article>


        <!-- RINGKASAN KETERAMPILAN -->

        <article class="panel skill-panel">

            <div class="panel-heading">

                <div>
                    <h2>Ringkasan Keterampilan</h2>

                    <p>
                        Skor analitik berdasarkan kategori keterampilan.
                    </p>
                </div>

                <a href="backend/hasil.php"
                   class="small-link">

                    Lihat Hasil Lengkap

                </a>

            </div>


            <?php if (!empty($skillCategories)): ?>

                <?php foreach (
                    $skillCategories as $index => $skill
                ): ?>

                    <?php

                    $score = (float) $skill["Total_Score"];

                    $width = (
                        $score / $maxSkillScore
                    ) * 100;

                    $warna = [
                        "#238b80",
                        "#16b98a",
                        "#129a94",
                        "#1596ad",
                        "#58ad9f"
                    ];

                    $barColor = $warna[
                        $index % count($warna)
                    ];

                    ?>

                    <div class="skill-item">

                        <div class="skill-header">

                            <strong>
                                <?= e(
                                    $skill["Kategori"]
                                    ?: "Kategori lainnya"
                                ) ?>
                            </strong>

                            <span>
                                <?= number_format($score, 1) ?> poin
                            </span>

                        </div>

                        <div class="skill-track">

                            <div
                                class="skill-fill"
                                style="
                                    width: <?= $width ?>%;
                                    background: <?= $barColor ?>;
                                ">
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state skill-empty">

                    <span class="empty-icon">✧</span>

                    <h3>Hasil keterampilan belum tersedia</h3>

                    <p>
                        Selesaikan assessment terlebih dahulu
                        untuk melihat ringkasan keterampilan.
                    </p>

                </div>

            <?php endif; ?>

        </article>

    </div>


    <!-- KOLOM KANAN -->

    <div class="right-column">

        <section class="recommendation-section">

            <div class="section-title-row">

                <div>
                    <h2>Rekomendasi Karier</h2>

                    <p>
                        Pilihan karier berdasarkan hasil assessment.
                    </p>
                </div>

                <a href="backend/assesment/rekomendasi_karier.php">
                    Lihat Semua
                </a>

            </div>


            <?php if (!empty($topCareers)): ?>

                <?php foreach (
                    $topCareers as $index => $career
                ): ?>

                    <?php
                    $careerIcons = ["▣", "▥", "</>"];
                    ?>

                    <article class="career-card">

                        <div class="career-top">

                            <div class="career-icon">
                                <?= $careerIcons[
                                    $index % count($careerIcons)
                                ] ?>
                            </div>

                            <div class="career-title">

                                <h3>
                                    <?= e($career["Nama_Karier"]) ?>
                                </h3>

                                <span>
                                    <?= e($career["Bidang"]) ?>
                                </span>

                            </div>

                            <span class="match-badge">
                                <?= number_format(
                                    (float) $career["Match_Score"],
                                    0
                                ) ?>% Cocok
                            </span>

                        </div>

                        <p class="career-description">
                            <?= e($career["Deskripsi"]) ?>
                        </p>

                        <div class="career-footer">

                            <span>
                                Rekomendasi #<?= (int) $career["Ranking"] ?>
                            </span>

                            <a href="backend/assesment/rekomendasi_karier.php">
                                Lihat Detail →
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state career-empty">

                    <span class="empty-icon">◎</span>

                    <h3>Rekomendasi belum tersedia</h3>

                    <p>
                        Selesaikan assessment agar sistem dapat
                        menampilkan pilihan karier yang relevan.
                    </p>

                    <a href="backend/assesment/start.php"
                       class="small-link">

                        Mulai Assessment →

                    </a>

                </div>

            <?php endif; ?>

        </section>

    </div>

</section>


<!-- =====================================
     VIDEO PEMBELAJARAN
====================================== -->

<section class="learning-section" id="learning">

    <div class="learning-heading">

        <div>
            <h2>Video Pembelajaran</h2>

            <p>
                Sumber belajar untuk memperkuat keterampilan
                dan mempersiapkan perjalanan kariermu.
            </p>
        </div>

        <a href="#learning">
            Jelajahi Materi <span>→</span>
        </a>

    </div>


    <div class="learning-grid">

        <?php foreach ($videos as $video): ?>

            <article class="learning-card">

                <div class="learning-top">

                    <span class="learning-category">
                        <?= e($video["category"]) ?>
                    </span>

                    <span class="learning-duration">
                        <?= e($video["duration"]) ?>
                    </span>

                </div>

                <h3>
                    <?= e($video["title"]) ?>
                </h3>

                <p>
                    <?= e($video["description"]) ?>
                </p>

                <div class="learning-footer">

                    <span>
                        <?= e($video["rating"]) ?>
                    </span>

                    <a
                        href="https://www.youtube.com/results?search_query=<?= urlencode($video["search"]) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="video-button">

                        Cari Video ↗

                    </a>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

</section>

</main>


<!-- =====================================
     FOOTER
====================================== -->

<footer class="footer">

    <div class="footer-brand">

        <strong>SKILLORA</strong>

        <span>
            © <?= date("Y") ?>.
            Kenali Potensimu, Bangun Masa Depanmu.
        </span>

    </div>

    <div class="footer-links">

        <a href="backend/assesment/start.php">
            Panduan Assessment
        </a>

        <a href="#learning">
            Materi Pembelajaran
        </a>

        <a href="backend/logout.php">
            Keluar
        </a>

    </div>

</footer>

</body>
</html>
